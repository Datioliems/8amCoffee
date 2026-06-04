/* =====================================================================
 *  8AM COFFEE — Cau noi (bridge) Arduino UNO <-> API Laravel
 *
 *  UNO khong co mang, nen chuong trinh nay chay tren MAY PC cam UNO:
 *    - Doc dong "UID:xxxx" do UNO gui qua cong COM.
 *    - Goi API Laravel /api/arduino/* (kem header thiet bi).
 *    - Gui ket qua ve UNO de hien LCD (L1:/L2:).
 *    - Ban phim PC: go lenh thao tac tren the vua quet:
 *         P <sdt>     -> phat the            (vd: P 0901234567)
 *         T <so_tien> -> tich diem           (vd: T 50000)
 *         D <so_diem> -> doi diem            (vd: D 200)
 *
 *  Cau hinh qua bien moi truong (hoac sua mac dinh ben duoi):
 *    PORT=COM3  BAUD=9600  API_BASE=...  DEVICE_ID=ARD001  DEVICE_TOKEN=...
 *
 *  Chay:  cd arduino/bridge && npm install && npm start
 *  (Windows vi du:  set PORT=COM3 && set API_BASE=http://localhost:8000/api/arduino && npm start)
 * ===================================================================== */

import { SerialPort } from 'serialport';
import { ReadlineParser } from '@serialport/parser-readline';
import readline from 'node:readline';

const PORT         = process.env.PORT         || 'COM3';
const BAUD         = parseInt(process.env.BAUD || '9600', 10);
const API_BASE     = process.env.API_BASE     || 'http://localhost:8000/api/arduino';
const DEVICE_ID    = process.env.DEVICE_ID    || 'ARD001';
const DEVICE_TOKEN = process.env.DEVICE_TOKEN || 'ARD-DEMO-KEY-001';

let lastUid = '';

// LCD 1602 khong hien thi dau tieng Viet -> bo dau truoc khi gui.
const ascii = (s) => (s || '').toString()
  .normalize('NFD').replace(/[̀-ͯ]/g, '')
  .replace(/đ/g, 'd').replace(/Đ/g, 'D');

const port = new SerialPort({ path: PORT, baudRate: BAUD });
const parser = port.pipe(new ReadlineParser({ delimiter: '\n' }));

function lcd(l1, l2 = '') {
  port.write(`L1:${ascii(l1).slice(0, 16)}\n`);
  port.write(`L2:${ascii(l2).slice(0, 16)}\n`);
}

async function api(path, body) {
  try {
    const r = await fetch(API_BASE + path, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Device-Id': DEVICE_ID,
        'X-Device-Token': DEVICE_TOKEN,
      },
      body: JSON.stringify(body),
    });
    const data = await r.json().catch(() => ({}));
    return { status: r.status, data };
  } catch (e) {
    return { status: 0, data: { message: 'Khong ket noi duoc API: ' + e.message } };
  }
}

// Khi UNO gui du lieu.
parser.on('data', async (raw) => {
  const line = raw.trim();
  if (line === 'READY') {
    console.log('[UNO] San sang. Cho quet the...');
    lcd('8AM Coffee', 'San sang');
    return;
  }
  if (!line.startsWith('UID:')) return;

  lastUid = line.slice(4).trim();
  console.log(`\n[Quet] UID = ${lastUid}`);
  const { status, data } = await api('/quet', { uid: lastUid });

  if (status === 200 && data.found) {
    console.log(`  => ${data.ten_kh || data.ma_the} | ${data.diem} diem | hang ${data.hang_the}`);
    lcd(data.ten_kh || 'Khach', `${data.diem} diem`);
  } else if (status === 200) {
    console.log('  => The trang (chua dang ky). Go: P <sdt> de phat the.');
    lcd('The chua DK', 'Go P <sdt>');
  } else {
    console.log(`  => Loi (${status}): ${data.message || ''}`);
    lcd('Loi tra cuu', data.message || '');
  }
});

// Ban phim PC: lenh P/T/D tren the vua quet.
const rl = readline.createInterface({ input: process.stdin });
rl.on('line', async (input) => {
  const parts = input.trim().split(/\s+/);
  const cmd = (parts.shift() || '').toUpperCase();
  const arg = parts.join(' ');
  if (!cmd) return;
  if (!lastUid) { console.log('Hay quet 1 the truoc da.'); return; }

  if (cmd === 'P') {
    const { data } = await api('/phat-the', { uid: lastUid, sdt: arg });
    console.log('  ' + (data.message || JSON.stringify(data)));
    lcd('Phat the', data.ok ? `+${data.diem} diem` : 'That bai');
  } else if (cmd === 'T') {
    const { data } = await api('/tich-diem', { uid: lastUid, so_tien: Number(arg) });
    console.log('  ' + (data.message || JSON.stringify(data)));
    lcd('Tich diem', data.ok ? `${data.diem} diem` : 'That bai');
  } else if (cmd === 'D') {
    const { data } = await api('/doi-diem', { uid: lastUid, so_diem: Number(arg) });
    console.log('  ' + (data.message || JSON.stringify(data)));
    lcd('Doi diem', data.ok ? `Giam ${data.tien_giam}d` : 'That bai');
  } else {
    console.log('Lenh: P <sdt> | T <so_tien> | D <so_diem>');
  }
});

port.on('open', () => console.log(`Mo ${PORT} @ ${BAUD}. API: ${API_BASE} (thiet bi ${DEVICE_ID}).`));
port.on('error', (e) => console.error('Loi cong serial:', e.message));
