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
    const modelUrl   = root.dataset.modelUrl;

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
    draco.setDecoderPath('/draco/');   // host nội bộ — chạy được kể cả offline/bị chặn CDN
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

        // căn giữa & lấy tâm để OrbitControls xoay quanh
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
    setInterval(fetchStatus, 5000);   // live update

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
    // Nhân viên chỉ cần biết: số ghế + bàn trống/có khách (không chọn/đổi bàn).
    function showInfo(ma) {
        const t = statusData[ma] || {};
        const conf = STATUS[t.trang_thai] || STATUS.trong;
        const hex = '#' + conf.color.toString(16).padStart(6, '0');
        infoEl.innerHTML = `
            <div class="text-lg font-bold text-gray-800">Bàn ${t.so_ban ?? ma}</div>
            <div class="text-xs text-gray-400">${t.vi_tri ?? ''} • ${ma}</div>
            <div class="mt-3">
              <span class="inline-block px-3 py-1 rounded-full text-white text-xs font-medium" style="background:${hex}">${conf.label}</span>
            </div>
            <div class="mt-3 text-sm text-gray-700">Số ghế: <b>${t.so_ghe ?? '—'}</b></div>
            <div class="mt-1 text-sm text-gray-600">${t.orders ?? 0} order đang mở</div>`;
    }

    // ── lọc theo tầng (dựa vào vi_tri) + hiệu ứng camera ───────
    const camGoal = { target: null, zoom: null };

    function setActiveBtn(active) {
        root.querySelectorAll('[data-floor]').forEach((b) => {
            const on = b === active;
            b.classList.toggle('bg-gray-800', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('bg-white', !on);
            b.classList.toggle('border', !on);
        });
    }

    function focusFloor(f) {
        const shown = [];
        for (const ma in tables) {
            const vis = f === 'all' || (statusData[ma]?.vi_tri || '').includes(f);
            tables[ma].visible = vis;
            if (vis) shown.push(tables[ma]);
        }
        if (f === 'all' || !shown.length) {
            camGoal.target = new THREE.Vector3(0, 0, 0);
            camGoal.zoom = 1;
            return;
        }
        // Camera "bay" tới tầng được chọn (pan + zoom mượt)
        const box = new THREE.Box3();
        shown.forEach((m) => box.expandByObject(m));
        camGoal.target = box.getCenter(new THREE.Vector3());
        const size = box.getSize(new THREE.Vector3());
        const extent = Math.max(size.x, size.z, 6);
        camGoal.zoom = Math.min(2.6, Math.max(1.1, 24 / extent));
    }

    root.querySelectorAll('[data-floor]').forEach((btn) => {
        btn.onclick = () => {
            setActiveBtn(btn);
            focusFloor(btn.dataset.floor);
        };
    });

    // ── resize / loop ──────────────────────────────────────────
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
        // Hiệu ứng chuyển tầng: lerp mượt target + zoom của camera
        if (camGoal.target) controls.target.lerp(camGoal.target, 0.1);
        if (camGoal.zoom != null) {
            camera.zoom += (camGoal.zoom - camera.zoom) * 0.1;
            camera.updateProjectionMatrix();
        }
        controls.update();
        renderer.render(scene, camera);
    })();
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('floorplan-root');
    if (root) initFloorplan(root);
});
