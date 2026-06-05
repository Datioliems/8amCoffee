// ───────────────────────────────────────────────────────────────
// Showroom 3D tương tác – menu3
// • Mỗi bàn có 1 GHIM ĐỊNH VỊ (vòng tròn rỗng + chóp nhọn) kiểu hologram
//   màu đậm, luôn hướng về camera, nhìn xuyên tường.
// • Click ghim → thẻ: ẢNH QUANG CẢNH TẦNG + số bàn + số ghế + trạng thái.
//   (Tầng 1 phân biệt gần nhà = stard_1_in, xa nhà = Stard_1_out)
// • "Xác nhận đổi" → chuyển toàn bộ hoá đơn sang bàn đó & sang trang bàn mới.
// ───────────────────────────────────────────────────────────────
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/examples/jsm/loaders/DRACOLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const STATUS = {
    trong:     { color: 0x12b85a, label: 'Trống' },
    co_khach:  { color: 0xd61f2b, label: 'Có khách' },
    dat_truoc: { color: 0xe09600, label: 'Đặt trước' },
    dong:      { color: 0x7a8290, label: 'Đóng' },
};
const SEL = 0x1769d6;
const IN_X = 5;   // ngưỡng X (toạ độ gốc) phân biệt gần nhà / xa nhà ở Tầng 1

function initShowroom(root) {
    const wrap        = root.querySelector('#sr-canvas');
    const infoEl      = root.querySelector('#sr-info');
    const modelUrl    = root.dataset.modelUrl;
    const apiUrl      = root.dataset.tablesUrl;
    const moveTpl     = root.dataset.moveUrl;
    const redirectTpl = root.dataset.redirectUrl;
    const current     = root.dataset.currentTable;
    const imgBase     = (root.dataset.imgBase || '').replace(/\/$/, '');
    const defPhoto    = root.dataset.photoUrl || '';
    const csrf        = document.querySelector('meta[name=csrf-token]')?.content;
    const maOrder     = document.querySelector('meta[name=ma-order]')?.content;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x161616);
    const cam = new THREE.PerspectiveCamera(45, wrap.clientWidth / wrap.clientHeight, 0.05, 4000);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(wrap.clientWidth, wrap.clientHeight);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    wrap.appendChild(renderer.domElement);

    const controls = new OrbitControls(cam, renderer.domElement);
    controls.enableDamping = true;
    controls.maxPolarAngle = Math.PI / 2.05;

    scene.add(new THREE.HemisphereLight(0xeaf2ff, 0x35302c, 0.85));            // trời / đất
    scene.add(new THREE.AmbientLight(0xffffff, 0.35));
    const sun = new THREE.DirectionalLight(0xfff3e0, 1.5); sun.position.set(25, 50, 18); scene.add(sun);
    const fill = new THREE.DirectionalLight(0x9fc1ff, 0.45); fill.position.set(-22, 18, -12); scene.add(fill);
    const rim = new THREE.DirectionalLight(0xffffff, 0.4); rim.position.set(0, 22, -30); scene.add(rim);

    const markers = {};   // ma -> {grp, main, glow, baseY, phase, origX}
    const heads = [];      // mesh click targets
    let statusData = {};
    let selected = current || null;
    let modelR = 10, centerX = 0;
    const clock = new THREE.Clock();

    const draco = new DRACOLoader();
    draco.setDecoderPath('/draco/');   // host nội bộ — chạy được kể cả offline/bị chặn CDN
    const loader = new GLTFLoader();
    loader.setDRACOLoader(draco);

    // ── ghim định vị: vòng tròn rỗng (torus) + chóp nhọn ───────
    function makeMarker(ma, r) {
        const s = Math.max(0.42, r * 0.026);
        const main = new THREE.MeshBasicMaterial({ color: 0x12b85a, transparent: true, opacity: 0.96, depthTest: false, depthWrite: false });
        const glow = new THREE.MeshBasicMaterial({ color: 0x12b85a, transparent: true, opacity: 0.35, blending: THREE.AdditiveBlending, depthTest: false, depthWrite: false });

        const ringGeo = new THREE.TorusGeometry(s * 0.5, s * 0.2, 18, 36);
        const coneGeo = new THREE.ConeGeometry(s * 0.52, s * 1.0, 30);

        const ring = new THREE.Mesh(ringGeo, main); ring.position.y = s * 0.62;
        const cone = new THREE.Mesh(coneGeo, main); cone.rotation.x = Math.PI; cone.position.y = s * 0.10;
        const ringG = new THREE.Mesh(ringGeo, glow); ringG.position.y = s * 0.62; ringG.scale.setScalar(1.2);
        const coneG = new THREE.Mesh(coneGeo, glow); coneG.rotation.x = Math.PI; coneG.position.y = s * 0.10; coneG.scale.setScalar(1.2);

        [ringG, coneG, cone, ring].forEach((m) => { m.renderOrder = 999; });
        ring.userData.maBan = ma; cone.userData.maBan = ma;

        const grp = new THREE.Group();
        grp.add(ringG, coneG, cone, ring);
        return { grp, main, glow, click: [ring, cone] };
    }

    loader.load(modelUrl, (gltf) => {
        const model = gltf.scene;
        scene.add(model);

        const box = new THREE.Box3().setFromObject(model);
        const center = box.getCenter(new THREE.Vector3());
        const size = box.getSize(new THREE.Vector3());
        centerX = center.x;
        model.position.sub(center);
        model.updateWorldMatrix(true, true);

        // ── Làm đẹp: tô màu theo tầng + cây xanh ──────────────
        const minY = -size.y / 2, spanY = size.y || 1;
        const FLOOR_TINT = [0xe9d9bf, 0xd7e3f0, 0xdfeede];   // F1 ấm · F2 mát · F3 xanh nhạt
        const tmpBox = new THREE.Box3();
        model.traverse((o) => {
            if (!o.isMesh || !o.material) return;
            const hasTex = !!o.material.map;
            if (/leaves|leaf|tree|plant|foliage|cay/i.test(o.name)) {
                o.material = o.material.clone();
                if (o.material.emissive) { o.material.emissive.setHex(0x0e3d16); o.material.emissiveIntensity = 0.35; }
                if (!hasTex && o.material.color) o.material.color.setHex(0x2f9e44);
            } else if (!hasTex && o.material.color) {
                const cy = tmpBox.setFromObject(o).getCenter(new THREE.Vector3()).y;
                const band = Math.min(2, Math.max(0, Math.floor(((cy - minY) / spanY) * 3)));
                o.material = o.material.clone();
                o.material.color.setHex(FLOOR_TINT[band]);
            }
        });

        modelR = Math.max(size.x, size.y, size.z);
        cam.position.set(modelR * 0.85, modelR * 0.65, modelR * 0.85);
        cam.near = modelR / 200; cam.far = modelR * 20; cam.updateProjectionMatrix();
        controls.target.set(0, 0, 0); controls.update();

        model.traverse((o) => {
            if (o.isMesh && o.name.startsWith('BAN_B')) {
                const ma = o.name.replace('BAN_', '');
                const tb = new THREE.Box3().setFromObject(o);
                const c = tb.getCenter(new THREE.Vector3());
                const m = makeMarker(ma, modelR);
                const baseY = tb.max.y + modelR * 0.05;
                m.grp.position.set(c.x, baseY, c.z);
                m.baseY = baseY; m.phase = Math.random() * 6.28;
                m.origX = c.x + centerX;   // toạ độ X gốc (để phân biệt gần/xa nhà)
                scene.add(m.grp);
                markers[ma] = m;
                m.click.forEach((mesh) => heads.push(mesh));
            }
        });
        fetchStatus();
    }, undefined, (e) => console.error('Lỗi tải model:', e));

    function colorMarkers() {
        for (const ma in markers) {
            const st = statusData[ma]?.trang_thai || 'trong';
            const hex = (ma === selected) ? SEL : (STATUS[st] || STATUS.trong).color;
            markers[ma].main.color.setHex(hex);
            markers[ma].glow.color.setHex(hex);
            markers[ma].grp.scale.setScalar(ma === selected ? 1.5 : 1);
        }
    }

    async function fetchStatus() {
        try {
            const arr = await (await fetch(apiUrl, { headers: { Accept: 'application/json' } })).json();
            statusData = {};
            arr.forEach((t) => (statusData[t.ma_ban] = t));
            colorMarkers();
            if (selected) showInfo(selected);
        } catch (e) { console.error(e); }
    }
    setInterval(fetchStatus, 7000);

    // ── raycast lên ghim ───────────────────────────────────────
    const ray = new THREE.Raycaster();
    const ndc = new THREE.Vector2();
    function pick(ev) {
        const rect = renderer.domElement.getBoundingClientRect();
        ndc.x = ((ev.clientX - rect.left) / rect.width) * 2 - 1;
        ndc.y = -((ev.clientY - rect.top) / rect.height) * 2 + 1;
        ray.setFromCamera(ndc, cam);
        return ray.intersectObjects(heads, false)[0]?.object?.userData?.maBan || null;
    }
    renderer.domElement.addEventListener('click', (ev) => {
        const ma = pick(ev);
        if (ma) { selected = ma; colorMarkers(); showInfo(ma); }
    });
    renderer.domElement.addEventListener('pointermove', (ev) => {
        renderer.domElement.style.cursor = pick(ev) ? 'pointer' : 'grab';
    });

    // ── tag chọn tầng → hiện ảnh khung cảnh tầng (Stard) ở ô dưới ──
    const floorImgEl = root.querySelector('#sr-floor-img');
    const floorPhEl = root.querySelector('#sr-floor-ph');
    root.querySelectorAll('.sr-floor-tag').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!imgBase || !floorImgEl) return;
            floorImgEl.onload = () => { floorImgEl.classList.remove('hidden'); floorPhEl && floorPhEl.classList.add('hidden'); };
            floorImgEl.onerror = () => { floorImgEl.classList.add('hidden'); if (floorPhEl) { floorPhEl.textContent = 'Chưa có ảnh tầng này.'; floorPhEl.classList.remove('hidden'); } };
            floorImgEl.src = imgBase + '/' + btn.dataset.floorImg;
            root.querySelectorAll('.sr-floor-tag').forEach((b) => b.classList.remove('bg-white/30'));
            btn.classList.add('bg-white/30');
        });
    });

    function showInfo(ma) {
        const t = statusData[ma] || {};
        const conf = STATUS[t.trang_thai] || STATUS.trong;
        const hex = '#' + conf.color.toString(16).padStart(6, '0');
        const free = t.trang_thai === 'trong';
        const isCurrent = ma === current;
        const seats = Number(t.so_ghe || 0);
        const chairs = seats ? '🪑'.repeat(Math.min(seats, 10)) : '—';
        const photo = (t.anh_ban && imgBase) ? (imgBase + '/' + t.anh_ban) : (defPhoto || '');

        infoEl.innerHTML = `
          <div class="overflow-hidden rounded-2xl bg-white/5 ring-1 ring-white/10">
            ${photo ? `<img src="${photo}" alt="" class="h-36 w-full object-cover" onerror="this.onerror=null; this.src='${defPhoto}'; if(!'${defPhoto}') this.style.display='none';">` : ''}
            <div class="p-4">
              <div class="flex items-end justify-between">
                <div>
                  <div class="text-2xl font-bold text-white leading-none">Bàn ${t.so_ban ?? ma}</div>
                  <div class="mt-1 text-xs text-white/45">${t.vi_tri ?? ''} • ${ma}</div>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold text-white" style="background:${hex}">${conf.label}</span>
              </div>
              <div class="mt-4 rounded-xl bg-white/5 p-3">
                <div class="text-[11px] uppercase tracking-[0.15em] text-white/40">Số ghế</div>
                <div class="mt-1 flex items-center gap-3">
                  <span class="text-3xl font-extrabold text-white leading-none">${seats || '?'}</span>
                  <span class="text-xl leading-none">${chairs}</span>
                </div>
              </div>
              ${isCurrent ? '<div class="mt-3 text-xs text-[#4da3ff]">• Bạn đang ngồi ở bàn này.</div>' : ''}
              <button id="sr-confirm"
                class="mt-4 w-full rounded-xl px-3 py-3 text-sm font-semibold transition ${(free && !isCurrent) ? 'bg-[#1769d6] text-white hover:brightness-110' : 'bg-white/10 text-white/40 pointer-events-none'}">
                ${isCurrent ? 'Bạn đang ở bàn này' : (free ? 'Xác nhận đổi sang bàn này →' : 'Bàn đã có khách')}
              </button>
            </div>
          </div>`;
        const btn = infoEl.querySelector('#sr-confirm');
        if (btn && free && !isCurrent) btn.onclick = () => confirmMove(ma);
    }

    async function confirmMove(toMa) {
        if (toMa === current) return;
        const btn = infoEl.querySelector('#sr-confirm');
        if (btn) { btn.textContent = 'Đang đổi…'; btn.classList.add('pointer-events-none'); }
        try {
            const r = await fetch(moveTpl.replace('__TO__', toMa), {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok || data.ok === false) { throw new Error(data.msg || 'move failed'); }

            // Đơn đã gửi/đã xác nhận → cần nhân viên duyệt: KHÔNG chuyển ngay.
            if (data.pending) {
                alert(data.msg || 'Đã gửi yêu cầu đổi bàn, vui lòng chờ nhân viên duyệt.');
                if (btn) { btn.textContent = 'Đã gửi yêu cầu ✓'; }
                return;
            }
            // Giỏ đang chọn (chưa gửi) → đổi ngay.
            window.location.href = redirectTpl.replace('__TO__', toMa);
        } catch (e) { alert(e.message || 'Đổi bàn thất bại, thử lại nhé.'); showInfo(toMa); }
    }

    window.addEventListener('resize', () => {
        cam.aspect = wrap.clientWidth / wrap.clientHeight;
        cam.updateProjectionMatrix();
        renderer.setSize(wrap.clientWidth, wrap.clientHeight);
    });

    (function loop() {
        requestAnimationFrame(loop);
        const t = clock.getElapsedTime();
        for (const ma in markers) {
            const m = markers[ma];
            m.grp.quaternion.copy(cam.quaternion);                                    // luôn hướng camera (thấy lỗ tròn)
            m.grp.position.y = m.baseY + Math.sin(t * 2 + m.phase) * modelR * 0.012;   // nhún nhẹ
            m.glow.opacity = 0.28 + 0.18 * Math.sin(t * 3 + m.phase);                  // nhịp sáng hologram
        }
        controls.update();
        renderer.render(scene, cam);
    })();
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('showroom-root');
    if (root) initShowroom(root);
});
