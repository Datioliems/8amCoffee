// Tạo map giả lập cho CN002 (không cần Blender) -> public/models/cafe_CN002.glb
// Sàn + 3 tường + 6 bàn (box) đặt tên BAN_B101..B106 để showroom.js click được.
import { Document, NodeIO } from '@gltf-transform/core';

const doc = new Document();
const buffer = doc.createBuffer();
const scene = doc.createScene('CN002');

// 6 mặt của hộp đơn vị (24 đỉnh, có normal)
const FACES = [
    { n: [0, 0, 1],  v: [[-1,-1, 1],[ 1,-1, 1],[ 1, 1, 1],[-1, 1, 1]] },
    { n: [0, 0,-1],  v: [[ 1,-1,-1],[-1,-1,-1],[-1, 1,-1],[ 1, 1,-1]] },
    { n: [1, 0, 0],  v: [[ 1,-1, 1],[ 1,-1,-1],[ 1, 1,-1],[ 1, 1, 1]] },
    { n: [-1,0, 0],  v: [[-1,-1,-1],[-1,-1, 1],[-1, 1, 1],[-1, 1,-1]] },
    { n: [0, 1, 0],  v: [[-1, 1, 1],[ 1, 1, 1],[ 1, 1,-1],[-1, 1,-1]] },
    { n: [0,-1, 0],  v: [[-1,-1,-1],[ 1,-1,-1],[ 1,-1, 1],[-1,-1, 1]] },
];

function boxArrays(cx, cy, cz, sx, sy, sz) {
    const pos = [], nor = [], idx = [];
    let base = 0;
    for (const f of FACES) {
        for (const c of f.v) {
            pos.push(cx + c[0] * sx / 2, cy + c[1] * sy / 2, cz + c[2] * sz / 2);
            nor.push(...f.n);
        }
        idx.push(base, base + 1, base + 2, base, base + 2, base + 3);
        base += 4;
    }
    return { pos, nor, idx };
}

function addBox(name, cx, cy, cz, sx, sy, sz, mat) {
    const { pos, nor, idx } = boxArrays(cx, cy, cz, sx, sy, sz);
    const p = doc.createAccessor().setType('VEC3').setArray(new Float32Array(pos)).setBuffer(buffer);
    const n = doc.createAccessor().setType('VEC3').setArray(new Float32Array(nor)).setBuffer(buffer);
    const i = doc.createAccessor().setType('SCALAR').setArray(new Uint16Array(idx)).setBuffer(buffer);
    const prim = doc.createPrimitive().setAttribute('POSITION', p).setAttribute('NORMAL', n).setIndices(i).setMaterial(mat);
    const mesh = doc.createMesh(name).addPrimitive(prim);
    const node = doc.createNode(name).setMesh(mesh);
    scene.addChild(node);
}

const mFloor = doc.createMaterial('floor').setBaseColorFactor([0.82, 0.80, 0.78, 1]).setRoughnessFactor(0.9).setMetallicFactor(0);
const mWall  = doc.createMaterial('wall').setBaseColorFactor([0.90, 0.86, 0.80, 1]).setRoughnessFactor(0.9).setMetallicFactor(0);
const mTbl   = doc.createMaterial('tbl').setBaseColorFactor([0.55, 0.36, 0.20, 1]).setRoughnessFactor(0.6).setMetallicFactor(0);
const mChair = doc.createMaterial('chair').setBaseColorFactor([0.12, 0.12, 0.13, 1]).setRoughnessFactor(0.7).setMetallicFactor(0);

// Y-up: sàn ở mặt phẳng XZ, chiều cao theo Y
addBox('C2_floor', 6, -0.075, 4, 12, 0.15, 8, mFloor);
addBox('C2_wall_back',  6, 1.5, 7.9, 12, 3.0, 0.2, mWall);
addBox('C2_wall_left',  0.1, 1.5, 4, 0.2, 3.0, 8, mWall);
addBox('C2_wall_right', 11.9, 1.5, 4, 0.2, 3.0, 8, mWall);

const tables = [[3, 2.6], [6, 2.6], [9, 2.6], [3, 5.4], [6, 5.4], [9, 5.4]];
tables.forEach(([x, z], k) => {
    const ma = 'B10' + (k + 1);
    addBox('BAN_' + ma, x, 0.37, z, 0.9, 0.74, 0.9, mTbl);       // mặt bàn (mesh tên BAN_B10x)
    addBox(ma + '_ch1', x - 0.6, 0.22, z, 0.22, 0.45, 0.42, mChair);
    addBox(ma + '_ch2', x + 0.6, 0.22, z, 0.22, 0.45, 0.42, mChair);
});

await new NodeIO().write('public/models/cafe_CN002.glb', doc);
console.log('OK cafe_CN002.glb · tables: ' + tables.map((_, k) => 'BAN_B10' + (k + 1)).join(', '));
