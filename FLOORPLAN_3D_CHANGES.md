# 🪑 Sơ đồ bàn 3D (Three.js) — Bản ghi thay đổi để merge/push lại

> Mục đích: nếu sau này code bị ghi đè / cần merge với bản cuối, dán lại các thay đổi
> dưới đây là chạy được ngay. Có 2 cách: **(A) git apply patch** hoặc **(B) dán thủ công**.

## 0. Lệnh cần chạy sau khi áp dụng
```bash
npm install three            # thêm thư viện three.js
npm run build                # (hoặc npm run dev) build lại để Vite manifest có floorplan.js
php artisan db:seed --class=BanSeeder   # (tùy chọn) tạo 17 bàn B001–B017 khớp model 3D
```

## Cách A — Dùng patch (nhanh nhất cho các file ĐÃ tracked)
```bash
git apply floorplan_3d.patch
```
Patch này gồm: `routes/web.php`, `vite.config.js`, `app/Http/Controllers/BanController.php`,
`database/seeders/BanSeeder.php`, `resources/views/layouts/app.blade.php`,
`resources/views/customer/menu.blade.php`, `package.json`.
> Vẫn phải tạo tay 3 file MỚI ở mục 1 (patch không chứa file untracked).

---

# Cách B — Dán thủ công từng file

## 1. FILE MỚI (tạo mới, dán full nội dung)

### 1a. `public/models/floorplan.glb`  ⚠️ FILE NHỊ PHÂN
Đây là model 3D lightweight (0.15 MB) export từ Blender. **Không dán bằng text được** —
giữ nguyên file này (copy kèm khi push). Nếu mất, export lại từ Blender bản nhẹ
(chỉ sàn/tường/cầu thang + 17 bàn proxy đặt tên `BAN_B001..B017`, bỏ texture).

### 1b. `resources/js/floorplan.js`
```js
// ───────────────────────────────────────────────────────────────
// Sơ đồ bàn 3D "Live" – Three.js
// Load /models/floorplan.glb (Draco), tô màu bàn theo trạng thái,
// click chọn bàn, đổi bàn, tự refresh trạng thái mỗi 5s.
// ───────────────────────────────────────────────────────────────
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/examples/jsm/loaders/DRACOLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const STATUS = {
    trong:     { color: 0x22c55e, label: 'Trống' },
    co_khach:  { color: 0xef4444, label: 'Có khách' },
    dat_truoc: { color: 0xf59e0b, label: 'Đặt trước' },
    dong:      { color: 0x9ca3af, label: 'Đóng' },
};
const SELECTED_COLOR = 0x2563eb;

function initFloorplan(root) {
    const canvasWrap = root.querySelector('#fp-canvas');
    const infoEl     = root.querySelector('#fp-info');
    const apiUrl     = root.dataset.tablesUrl;
    const moveTpl    = root.dataset.moveUrl;            // .../move/__FROM__/__TO__
    const modelUrl   = root.dataset.modelUrl;
    const csrf       = document.querySelector('meta[name=csrf-token]')?.content;

    // ── scene / camera / renderer ──────────────────────────────
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf6f3f2);

    let aspect = canvasWrap.clientWidth / canvasWrap.clientHeight;
    const D = 13;
    const camera = new THREE.OrthographicCamera(-D * aspect, D * aspect, D, -D, 0.1, 2000);
    camera.position.set(40, 40, 40);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(canvasWrap.clientWidth, canvasWrap.clientHeight);
    canvasWrap.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.maxPolarAngle = Math.PI / 2.05;

    scene.add(new THREE.AmbientLight(0xffffff, 0.85));
    const dir = new THREE.DirectionalLight(0xffffff, 1.1);
    dir.position.set(25, 50, 20);
    scene.add(dir);

    // ── state ──────────────────────────────────────────────────
    const tables = {};      // ma_ban -> mesh
    // Bàn hiện tại của khách (truyền từ trang menu) -> tô xanh dương + làm bàn nguồn khi đổi
    let selected = root.dataset.currentTable || null;
    if (root.dataset.currentTable) window.fpCurrentTable = root.dataset.currentTable;
    let statusData = {};    // ma_ban -> {trang_thai, so_ban, vi_tri, orders}

    // ── load model ─────────────────────────────────────────────
    const draco = new DRACOLoader();
    draco.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.6/');
    const loader = new GLTFLoader();
    loader.setDRACOLoader(draco);

    loader.load(modelUrl, (gltf) => {
        const model = gltf.scene;
        model.traverse((o) => {
            if (!o.isMesh) return;
            if (o.name.startsWith('BAN_B')) {
                const ma = o.name.replace('BAN_', '');
                o.material = new THREE.MeshStandardMaterial({ color: 0xcccccc, emissive: 0x000000 });
                o.userData.maBan = ma;
                tables[ma] = o;
            } else {
                o.material = new THREE.MeshStandardMaterial({ color: 0xe8e2dd, roughness: 0.95 });
            }
        });
        scene.add(model);

        const box = new THREE.Box3().setFromObject(model);
        const center = box.getCenter(new THREE.Vector3());
        model.position.sub(center);
        controls.target.set(0, 0, 0);
        controls.update();

        fetchStatus();
    }, undefined, (err) => console.error('Lỗi tải model:', err));

    // ── tô màu bàn theo trạng thái ─────────────────────────────
    function colorTables() {
        for (const ma in tables) {
            const st = statusData[ma]?.trang_thai || 'trong';
            const conf = STATUS[st] || STATUS.trong;
            const isSel = ma === selected;
            tables[ma].material.color.setHex(isSel ? SELECTED_COLOR : conf.color);
            tables[ma].material.emissive.setHex(isSel ? 0x1e3a8a : 0x000000);
        }
    }

    async function fetchStatus() {
        try {
            const r = await fetch(apiUrl, { headers: { Accept: 'application/json' } });
            const arr = await r.json();
            statusData = {};
            arr.forEach((t) => (statusData[t.ma_ban] = t));
            colorTables();
            if (selected) showInfo(selected);
        } catch (e) { console.error(e); }
    }
    setInterval(fetchStatus, 5000);

    // ── raycast click / hover ──────────────────────────────────
    const ray = new THREE.Raycaster();
    const ndc = new THREE.Vector2();
    function pick(ev) {
        const rect = renderer.domElement.getBoundingClientRect();
        ndc.x = ((ev.clientX - rect.left) / rect.width) * 2 - 1;
        ndc.y = -((ev.clientY - rect.top) / rect.height) * 2 + 1;
        ray.setFromCamera(ndc, camera);
        const hits = ray.intersectObjects(Object.values(tables), false);
        return hits[0]?.object?.userData?.maBan || null;
    }
    renderer.domElement.addEventListener('click', (ev) => {
        const ma = pick(ev);
        if (ma) { selected = ma; colorTables(); showInfo(ma); }
    });
    renderer.domElement.addEventListener('pointermove', (ev) => {
        renderer.domElement.style.cursor = pick(ev) ? 'pointer' : 'grab';
    });

    // ── panel thông tin bàn ────────────────────────────────────
    function showInfo(ma) {
        const t = statusData[ma] || {};
        const conf = STATUS[t.trang_thai] || STATUS.trong;
        const hex = '#' + conf.color.toString(16).padStart(6, '0');
        const free = t.trang_thai === 'trong';
        infoEl.innerHTML = `
            <div class="text-lg font-bold text-gray-800">Bàn ${t.so_ban ?? ma}</div>
            <div class="text-xs text-gray-400">${t.vi_tri ?? ''} • ${ma}</div>
            <div class="mt-3">
              <span class="inline-block px-3 py-1 rounded-full text-white text-xs font-medium" style="background:${hex}">${conf.label}</span>
            </div>
            <div class="mt-2 text-sm text-gray-600">${t.orders ?? 0} order đang mở</div>
            <div class="mt-4 flex flex-col gap-2">
              <button id="fp-choose" class="px-3 py-2 rounded-lg bg-green-600 text-white text-sm ${free ? '' : 'opacity-40 pointer-events-none'}">Chọn bàn này</button>
              <button id="fp-move"   class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm">Đổi sang bàn này</button>
            </div>`;
        infoEl.querySelector('#fp-choose').onclick = () => {
            window.fpCurrentTable = ma;
            alert('Đã chọn bàn ' + (t.so_ban ?? ma) + ' làm bàn hiện tại.');
        };
        infoEl.querySelector('#fp-move').onclick = () => moveTo(ma);
    }

    async function moveTo(toMa) {
        const from = window.fpCurrentTable;
        if (!from) { alert('Chưa có bàn nguồn. Hãy bấm "Chọn bàn này" ở bàn đang ngồi trước.'); return; }
        if (from === toMa) { alert('Đang là bàn hiện tại.'); return; }
        const url = moveTpl.replace('__FROM__', from).replace('__TO__', toMa);
        const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
        if (r.ok) { window.fpCurrentTable = toMa; await fetchStatus(); alert('Đã đổi sang bàn ' + toMa); }
        else { alert('Đổi bàn thất bại.'); }
    }

    // ── lọc theo tầng (dựa vào vi_tri) ─────────────────────────
    root.querySelectorAll('[data-floor]').forEach((btn) => {
        btn.onclick = () => {
            const f = btn.dataset.floor;
            for (const ma in tables) {
                tables[ma].visible = f === 'all' || (statusData[ma]?.vi_tri || '').includes(f);
            }
        };
    });

    function onResize() {
        const w = canvasWrap.clientWidth, h = canvasWrap.clientHeight;
        aspect = w / h;
        camera.left = -D * aspect; camera.right = D * aspect; camera.top = D; camera.bottom = -D;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    }
    window.addEventListener('resize', onResize);

    (function animate() {
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    })();
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('floorplan-root');
    if (root) initFloorplan(root);
});
```

### 1c. `resources/views/staff/floorplan.blade.php`  (trang sơ đồ cho nhân viên — tùy chọn)
```blade
@extends('layouts.app')

@section('title', 'Sơ đồ bàn 3D - 8AM Coffee')
@section('page-title', 'Sơ đồ bàn 3D (Live)')

@section('content')
<div id="floorplan-root"
     data-model-url="{{ asset('models/floorplan.glb') }}"
     data-tables-url="{{ route('floorplan.tables') }}"
     data-move-url="{{ route('floorplan.move', ['from' => '__FROM__', 'to' => '__TO__']) }}">

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <button data-floor="all"     class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-sm">Tất cả</button>
        <button data-floor="Tầng 1"  class="px-3 py-1.5 rounded-lg bg-white border text-sm">Tầng 1</button>
        <button data-floor="Tầng 2"  class="px-3 py-1.5 rounded-lg bg-white border text-sm">Tầng 2</button>
        <button data-floor="Tầng 3"  class="px-3 py-1.5 rounded-lg bg-white border text-sm">Tầng 3</button>
        <div class="ml-auto flex items-center gap-3 text-xs text-gray-600">
            <span class="flex items-center gap-1"><i class="inline-block w-3 h-3 rounded-full" style="background:#22c55e"></i> Trống</span>
            <span class="flex items-center gap-1"><i class="inline-block w-3 h-3 rounded-full" style="background:#ef4444"></i> Có khách</span>
            <span class="flex items-center gap-1"><i class="inline-block w-3 h-3 rounded-full" style="background:#f59e0b"></i> Đặt trước</span>
            <span class="flex items-center gap-1"><i class="inline-block w-3 h-3 rounded-full" style="background:#2563eb"></i> Đang chọn</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div id="fp-canvas" class="lg:col-span-3 rounded-2xl bg-white border border-gray-200 overflow-hidden" style="height:72vh"></div>
        <div id="fp-info" class="rounded-2xl bg-white border border-gray-200 p-4 text-sm text-gray-400">Chọn một bàn để xem chi tiết…</div>
    </div>
</div>

@vite('resources/js/floorplan.js')
@endsection
```

## 2. FILE SỬA (chỉnh sửa)

### 2a. `vite.config.js` — thêm floorplan.js vào input
```diff
-            input: ['resources/css/app.css', 'resources/js/app.js'],
+            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/floorplan.js'],
```

### 2b. `routes/web.php`
**(i)** Trong group khách `Route::prefix('order')->name('customer.')`, ngay sau dòng `statusJson`:
```php
    // Sơ đồ bàn 3D cho khách (public, chi nhánh suy từ bàn)
    Route::get('/{ma_ban}/tables',     [BanController::class, 'apiTablesByBan'])->name('tables');
    Route::post('/{ma_ban}/move/{to}', [BanController::class, 'moveByBan']     )->name('move');
```
**(ii)** Trong group `auth.staff`, ngay sau route `dashboard`:
```php
    // SƠ ĐỒ BÀN 3D (Three.js)
    Route::get('/floorplan',                   [BanController::class, 'floorplan'])->name('floorplan');
    Route::get('/floorplan/tables',            [BanController::class, 'apiTables'])->name('floorplan.tables');
    Route::post('/floorplan/move/{from}/{to}', [BanController::class, 'moveTable'])->name('floorplan.move');
```
> `BanController` đã được `use` sẵn ở đầu file `routes/web.php`.

### 2c. `app/Http/Controllers/BanController.php` — THAY TOÀN BỘ bằng
```php
<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use Illuminate\Support\Facades\DB;

class BanController extends Controller
{
    private const ACTIVE_ORDER = ['cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu'];

    // ── STAFF ──
    public function index()
    {
        $maChiNhanh = session('ma_chi_nhanh');
        $bans = Ban::where('ma_chi_nhanh', $maChiNhanh)->orderBy('so_ban')->get();
        $orderCounts = $this->orderCounts($maChiNhanh);
        return view('staff.ban-list', compact('bans', 'orderCounts'));
    }

    public function floorplan() { return view('staff.floorplan'); }

    public function apiTables() { return $this->tablesJson(session('ma_chi_nhanh')); }

    public function moveTable(string $from, string $to) { return $this->doMove($from, $to, session('ma_chi_nhanh')); }

    // ── CUSTOMER (chi nhánh suy từ bàn) ──
    public function apiTablesByBan(string $maBan)
    {
        $ban = Ban::findOrFail($maBan);
        return $this->tablesJson($ban->ma_chi_nhanh);
    }

    public function moveByBan(string $maBan, string $to)
    {
        $ban = Ban::findOrFail($maBan);
        return $this->doMove($maBan, $to, $ban->ma_chi_nhanh);
    }

    // ── SHARED ──
    private function tablesJson(?string $branch)
    {
        $counts = $this->orderCounts($branch);
        $bans = Ban::where('ma_chi_nhanh', $branch)->orderBy('so_ban')->get();
        return response()->json($bans->map(fn ($b) => [
            'ma_ban'     => $b->ma_ban,
            'so_ban'     => $b->so_ban,
            'vi_tri'     => $b->vi_tri,
            'trang_thai' => $b->trang_thai,
            'orders'     => (int) ($counts[$b->ma_ban] ?? 0),
        ])->values());
    }

    private function doMove(string $from, string $to, ?string $branch)
    {
        if ($from === $to) return response()->json(['ok' => false, 'msg' => 'Trùng bàn'], 422);

        DB::transaction(function () use ($from, $to, $branch) {
            DB::table('ORDERS')->where('ma_chi_nhanh', $branch)->where('ma_ban', $from)
                ->whereIn('trang_thai', self::ACTIVE_ORDER)->update(['ma_ban' => $to]);
            Ban::where('ma_ban', $to)->update(['trang_thai' => 'co_khach']);
            $conLai = DB::table('ORDERS')->where('ma_ban', $from)
                ->whereIn('trang_thai', self::ACTIVE_ORDER)->count();
            Ban::where('ma_ban', $from)->update(['trang_thai' => $conLai ? 'co_khach' : 'trong']);
        });

        return response()->json(['ok' => true]);
    }

    private function orderCounts(?string $branch)
    {
        return DB::table('ORDERS')->where('ma_chi_nhanh', $branch)
            ->whereIn('trang_thai', self::ACTIVE_ORDER)
            ->select('ma_ban', DB::raw('COUNT(*) as cnt'))
            ->groupBy('ma_ban')->pluck('cnt', 'ma_ban');
    }
}
```

### 2d. `database/seeders/BanSeeder.php` — vòng lặp tạo 17 bàn theo tầng
```php
        $bans = [];
        for ($i = 1; $i <= 17; $i++) {
            if ($i <= 8)       $tang = 'Tầng 1';
            elseif ($i <= 13)  $tang = 'Tầng 2';
            else               $tang = 'Tầng 3';
            $bans[] = [
                'ma_ban'       => 'B'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'so_ban'       => $i,
                'vi_tri'       => $tang,
                'trang_thai'   => 'trong',
                'ma_chi_nhanh' => 'CN001',
            ];
        }
        DB::table('BAN')->insertOrIgnore($bans);
```

### 2e. `resources/views/layouts/app.blade.php` — thêm link sidebar (sau link "Tổng quan")
```blade
        <a href="{{ route('floorplan') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('floorplan') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('floorplan') ? 'bg-white/15' : 'bg-white' }}">▦</span>
            Sơ đồ 3D
        </a>
```

### 2f. `resources/views/customer/menu.blade.php`
**(i)** Trong `@push('head')` (chỗ có meta `ma-order`), thêm trước `@endpush`:
```blade
@vite('resources/js/floorplan.js')
```
**(ii)** Thay cả khối placeholder CSS `<div class="am-store-stage ...">…</div>` + thẻ `<p>Khối này đang là minh họa…</p>` bằng:
```blade
        <div id="floorplan-root"
             data-model-url="{{ asset('models/floorplan.glb') }}"
             data-tables-url="{{ route('customer.tables', $ban->ma_ban) }}"
             data-move-url="{{ route('customer.move', ['ma_ban' => $ban->ma_ban, 'to' => '__TO__']) }}"
             data-current-table="{{ $ban->ma_ban }}">
            <div class="mb-3 flex flex-wrap gap-1.5 text-[11px] text-[#522C25]/70">
                <button data-floor="all"    class="rounded-full bg-[#1A1A1A] px-2.5 py-1 text-white">Tất cả</button>
                <button data-floor="Tầng 1" class="rounded-full bg-white px-2.5 py-1 ring-1 ring-[#522C25]/15">Tầng 1</button>
                <button data-floor="Tầng 2" class="rounded-full bg-white px-2.5 py-1 ring-1 ring-[#522C25]/15">Tầng 2</button>
                <button data-floor="Tầng 3" class="rounded-full bg-white px-2.5 py-1 ring-1 ring-[#522C25]/15">Tầng 3</button>
            </div>
            <div id="fp-canvas" class="h-64 overflow-hidden rounded-3xl bg-[#F2F2F2]"></div>
            <div id="fp-info" class="mt-3 text-sm leading-6 text-[#522C25]/65">
                🔵 Bàn bạn đang ngồi · 🟢 Trống · 🔴 Có khách. Chạm vào một bàn để xem & đổi.
            </div>
        </div>
```

### 2g. `package.json` — thêm dependency
Chạy `npm install three` (sẽ tự thêm `"three"` vào `dependencies`).

---

## 3. Ghi chú quan trọng
- **Tên bàn trong model 3D** phải là `BAN_B001 … BAN_B017` và DB phải có đúng `ma_ban` đó (+ `vi_tri` "Tầng 1/2/3") thì màu & lọc tầng mới khớp.
- Mỗi lần đổi `vite.config.js` → phải `npm run build` (hoặc giữ `npm run dev` đang chạy).
- Draco decoder lấy từ CDN gstatic (cần có internet phía client). Muốn offline: copy thư mục
  `node_modules/three/examples/jsm/libs/draco/` vào `public/draco/` rồi đổi
  `setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.6/')`
  thành `setDecoderPath('/draco/')`.
- File KHÔNG nên commit (build lại được): `public/build/*` (Vite tự sinh khi `npm run build`).
