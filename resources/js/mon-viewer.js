// ─────────────────────────────────────────────────────────────
// Viewer 3D cho TỪNG MÓN — modal, LAZY. Tải .glb từ CDN (R2/Worker).
// Nền trong suốt · đổ bóng tiếp đất · model bay lơ lửng · tiêu đề font hiển thị.
// Dùng: window.viewMon3D(url, tenMon)
// ─────────────────────────────────────────────────────────────
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/examples/jsm/loaders/DRACOLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

let modal, canvas, titleEl, loadingEl;
let renderer, scene, camera, controls, loader, clock;
let currentModel = null, ground = null, floatAmp = 0, running = false, rafId = null;

function buildModal() {
    modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;z-index:80;display:none;align-items:center;justify-content:center;background:rgba(20,12,10,.45);backdrop-filter:blur(3px);padding:16px';
    modal.innerHTML = `
      <div style="position:relative;width:100%;max-width:520px;background:#FCFAFA;border-radius:22px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.35)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f0eae7">
          <h3 data-title class="am-display" style="margin:0;font-size:26px;line-height:1;color:#1A1A1A"></h3>
          <button data-close style="border:0;background:#F2F2F2;width:34px;height:34px;border-radius:999px;font-size:20px;cursor:pointer;color:#522C25;line-height:1">&times;</button>
        </div>
        <div data-stage style="position:relative;background:
              radial-gradient(120% 90% at 50% 18%, #ffffff 0%, #f4efec 55%, #ece4df 100%)">
          <div data-canvas style="width:100%;height:62vh;max-height:460px"></div>
          <div data-loading style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#8B5A2B;font-size:14px">Đang tải mô hình 3D…</div>
        </div>
        <p style="margin:0;padding:11px 20px;font-size:12px;color:#522C25;background:#fff">Kéo để xoay · cuộn để phóng to · chạm 2 ngón để di chuyển.</p>
      </div>`;
    document.body.appendChild(modal);
    canvas = modal.querySelector('[data-canvas]');
    titleEl = modal.querySelector('[data-title]');
    loadingEl = modal.querySelector('[data-loading]');
    modal.querySelector('[data-close]').addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    window.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
}

function initScene() {
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });   // alpha => nền trong suốt
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    canvas.appendChild(renderer.domElement);

    scene = new THREE.Scene();          // background = null => trong suốt
    camera = new THREE.PerspectiveCamera(45, 1, 0.01, 1000);
    clock = new THREE.Clock();

    scene.add(new THREE.HemisphereLight(0xffffff, 0x8a8a8a, 1.0));
    const dir = new THREE.DirectionalLight(0xffffff, 1.4);
    dir.position.set(4, 9, 5);
    dir.castShadow = true;
    dir.shadow.mapSize.set(1024, 1024);
    dir.shadow.bias = -0.0005;
    scene.add(dir);
    window.__monDir = dir;

    // Mặt phẳng nhận bóng (trong suốt, chỉ hiện vệt bóng)
    ground = new THREE.Mesh(
        new THREE.PlaneGeometry(200, 200),
        new THREE.ShadowMaterial({ opacity: 0.28 })
    );
    ground.rotation.x = -Math.PI / 2;
    ground.receiveShadow = true;
    scene.add(ground);

    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 1.4;

    const draco = new DRACOLoader();
    draco.setDecoderPath('/draco/');
    loader = new GLTFLoader();
    loader.setDRACOLoader(draco);

    window.addEventListener('resize', onResize);
}

function onResize() {
    if (!renderer || modal.style.display === 'none') return;
    const w = canvas.clientWidth, h = canvas.clientHeight;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
}

function clearModel() {
    if (!currentModel) return;
    scene.remove(currentModel);
    currentModel.traverse((o) => {
        if (o.geometry) o.geometry.dispose();
        if (o.material) (Array.isArray(o.material) ? o.material : [o.material]).forEach((m) => m.dispose());
    });
    currentModel = null;
}

function loadModel(url) {
    loadingEl.style.display = 'flex';
    loadingEl.textContent = 'Đang tải mô hình 3D…';
    loader.load(url, (gltf) => {
        clearModel();
        currentModel = gltf.scene;
        currentModel.traverse((o) => { if (o.isMesh) { o.castShadow = true; o.receiveShadow = false; } });
        scene.add(currentModel);

        // Canh giữa + fit camera
        const box = new THREE.Box3().setFromObject(currentModel);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        currentModel.position.sub(center);                 // tâm về gốc toạ độ
        const r = Math.max(size.x, size.y, size.z) || 1;

        floatAmp = size.y * 0.06;                            // biên độ bay
        ground.position.y = -size.y / 2 - r * 0.04;          // sàn ngay dưới đáy model
        const dir = window.__monDir;
        const sc = r * 1.6;
        dir.shadow.camera.left = -sc; dir.shadow.camera.right = sc;
        dir.shadow.camera.top = sc; dir.shadow.camera.bottom = -sc;
        dir.shadow.camera.near = 0.1; dir.shadow.camera.far = r * 30;
        dir.shadow.camera.updateProjectionMatrix();

        camera.position.set(r * 1.1, r * 0.8, r * 1.5);
        camera.near = r / 100; camera.far = r * 50; camera.updateProjectionMatrix();
        controls.target.set(0, 0, 0); controls.update();

        loadingEl.style.display = 'none';
    }, undefined, (err) => {
        console.error('Lỗi tải model món:', err);
        loadingEl.textContent = 'Không tải được mô hình 3D.';
    });
}

function animate() {
    if (!running) return;
    rafId = requestAnimationFrame(animate);
    const t = clock.getElapsedTime();
    if (currentModel) {
        currentModel.position.y = Math.sin(t * 1.6) * floatAmp;   // bay lơ lửng
    }
    controls.update();
    renderer.render(scene, camera);
}

function close() {
    if (!modal) return;
    modal.style.display = 'none';
    running = false;
    if (rafId) cancelAnimationFrame(rafId);
    // Giải phóng model + xóa cache để không tích lũy bộ nhớ sau khi xem nhiều món.
    clearModel();
    THREE.Cache.clear();
    if (renderer) renderer.renderLists.dispose();
}

window.viewMon3D = function (url, name) {
    if (!url) return;
    if (!modal) buildModal();
    if (!renderer) initScene();
    titleEl.textContent = name || 'Mô hình 3D';
    modal.style.display = 'flex';
    requestAnimationFrame(() => {
        onResize();
        loadModel(url);
        running = true;
        clock.start();
        animate();
    });
};
