@extends('layouts.app')
@section('title', 'Thẻ thành viên')
@section('page-title', 'Thẻ thành viên & điểm')

@php
    $tt = ['hoat_dong' => ['Hoạt động','bg-emerald-50 text-emerald-700'], 'khoa' => ['Đã khóa','bg-[#F1ECEA] text-[#522C25]/70'], 'mat' => ['Báo mất','bg-red-50 text-[#BB0011]']];
@endphp

@section('content')
<div class="mx-auto max-w-6xl space-y-5">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#BB0011]">{{ session('error') }}</div>
    @endif

    <div class="flex justify-end">
        <a href="{{ route('loyalty.tiers') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-[#522C25]/20 px-3 py-2 text-sm font-semibold text-[#522C25] hover:bg-[#F2F2F2]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
            Cấu hình hạng
        </a>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Tổng thẻ</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($thongKe['tong_the'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Đang hoạt động</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ number_format($thongKe['hoat_dong'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Tổng điểm đang lưu hành</p>
            <p class="mt-1 text-2xl font-semibold text-[#8B5A2B]">{{ number_format($thongKe['tong_diem'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Phát thẻ + tìm kiếm --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <form method="POST" action="{{ route('loyalty.issue') }}" class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            @csrf
            <p class="mb-2 text-sm font-semibold text-[#8B5A2B]">Phát thẻ mới</p>

            {{-- UID + đọc thẻ tự động qua đầu đọc (Web Serial) --}}
            <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">UID thẻ (hex)</label>
            <div class=”flex gap-2”>
                <input type=”text” name=”uid” id=”uid-input” required placeholder=”Quẹt thẻ hoặc gõ UID”
                       class=”w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm font-mono transition”>
                {{-- Nút NFC: ẩn bằng style inline (không phụ thuộc Tailwind purge),
                     JS chỉ hiện khi trình duyệt hỗ trợ NDEFReader (Android Chrome) --}}
                <button type=”button” id=”nfc-scan” style=”display:none”
                        class=”shrink-0 rounded-lg border border-emerald-600/30 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100”>
                    NFC
                </button>
                <button type=”button” id=”rfid-connect”
                        class=”shrink-0 rounded-lg border border-[#8B5A2B]/30 bg-[#FFF7E8] px-3 py-2 text-xs font-semibold text-[#8B5A2B] hover:bg-[#FCEFD6]”>
                    Kết nối đầu đọc
                </button>
            </div>
            <p id=”rfid-status” class=”mt-1 text-[11px] text-[#522C25]/55”>Bấm “Kết nối đầu đọc” rồi quẹt thẻ — UID tự điền (Chrome/Edge, đầu đọc cắm USB).</p>

            {{-- Chọn khách đủ điều kiện — gõ tên/SĐT, hiện gợi ý (autocomplete) --}}
            <label class="mt-3 mb-1 block text-[11px] font-semibold text-[#522C25]/55">Khách đủ điều kiện (gõ tên hoặc SĐT)</label>
            <div class="relative" id="kh-ac">
                <input type="text" id="kh-search" autocomplete="off" placeholder="Gõ tên hoặc số điện thoại khách..."
                       class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                <input type="hidden" name="ma_kh" id="kh-ma" value="">
                <div id="kh-list" class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-[#522C25]/15 bg-white shadow-lg">
                    @forelse($eligible as $e)
                        <button type="button" class="kh-item block w-full border-b border-[#522C25]/5 px-3 py-2 text-left text-sm hover:bg-[#FFF7E8]"
                                data-ma="{{ $e['ma_kh'] }}"
                                data-label="{{ $e['ten_kh'] ?: $e['ma_kh'] }} · {{ $e['sdt_che'] }}"
                                data-search="{{ \Illuminate\Support\Str::lower(($e['ten_kh'] ?? '') . ' ' . ($e['sdt'] ?? '') . ' ' . ($e['sdt_che'] ?? '')) }}">
                            <span class="font-medium text-[#1A1A1A]">{{ $e['ten_kh'] ?: $e['ma_kh'] }}</span>
                            <span class="text-[#522C25]/60"> · {{ $e['sdt_che'] }}</span>
                            <span class="text-[#8B5A2B]"> · {{ number_format($e['tong'], 0, ',', '.') }}đ</span>
                        </button>
                    @empty
                        <div class="px-3 py-2 text-[11px] text-[#522C25]/45">Chưa có khách đủ điều kiện.</div>
                    @endforelse
                </div>
            </div>
            @if(empty($eligible))
                <p class="mt-1 text-[11px] text-[#522C25]/45">Chưa có khách đạt mốc {{ number_format((int) config('loyalty.issue_threshold'), 0, ',', '.') }}đ (hoặc tất cả đã có thẻ).</p>
            @endif

            {{-- Hoặc nhập SĐT thủ công --}}
            <details class="mt-2">
                <summary class="cursor-pointer text-[11px] text-[#8B5A2B]">Hoặc nhập SĐT thủ công</summary>
                <input type="text" name="sdt" inputmode="numeric" pattern="0[0-9]{9}" placeholder="0901234567"
                       class="mt-1 w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                <p class="mt-1 text-[11px] text-[#522C25]/45">Dùng khi khách đủ mốc nhưng không có trong danh sách.</p>
            </details>

            <button class="mt-3 w-full rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white hover:bg-black">Phát thẻ</button>
            <p class="mt-1 text-[11px] text-[#522C25]/45">Điểm khởi tạo = tổng chi tiêu ÷ {{ number_format((int) config('loyalty.earn_per_amount'), 0, ',', '.') }}đ.</p>
        </form>

        <form method="GET" class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="mb-2 text-sm font-semibold text-[#8B5A2B]">Tìm thẻ</p>
            <div class="flex flex-wrap items-end gap-2">
                <div class="grow">
                    <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">UID / Mã thẻ / SĐT</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="04A1B2C3 · TV000001 · 0901234567"
                           class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Trạng thái</label>
                    <select name="trang_thai" class="rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                        <option value="">Tất cả</option>
                        <option value="hoat_dong" @selected($trangThai==='hoat_dong')>Hoạt động</option>
                        <option value="khoa" @selected($trangThai==='khoa')>Đã khóa</option>
                        <option value="mat" @selected($trangThai==='mat')>Báo mất</option>
                    </select>
                </div>
                <button class="rounded-lg bg-[#8B5A2B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6F4621]">Lọc</button>
            </div>
        </form>
    </div>

    {{-- Bảng thẻ --}}
    <div class="overflow-hidden rounded-2xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Mã thẻ / UID</th>
                        <th class="px-4 py-3">Khách</th>
                        <th class="px-4 py-3 text-right">Điểm</th>
                        <th class="px-4 py-3">Hạng</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Ngày phát</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($cards as $card)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-[#1A1A1A]">{{ $card->ma_the }}</p>
                            <p class="font-mono text-[11px] text-[#522C25]/55">{{ $card->uid_rfid }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $card->khachHang?->ten_kh ?? ($card->ma_kh ?: '—') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-[#8B5A2B]">{{ number_format($card->diem_hien_tai, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-[#FFF7E8] px-2 py-0.5 text-[11px] font-semibold text-[#8B5A2B]">{{ $card->hang_the }}</span></td>
                        <td class="px-4 py-3">
                            @php($s = $tt[$card->trang_thai] ?? [$card->trang_thai,'bg-[#F1ECEA] text-[#522C25]/70'])
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $s[1] }}">{{ $s[0] }}</span>
                        </td>
                        <td class="px-4 py-3 text-[#522C25]/70">{{ $card->ngay_phat ? \Illuminate\Support\Carbon::parse($card->ngay_phat)->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('loyalty.show', $card->ma_the) }}" class="rounded-lg border border-[#522C25]/20 px-3 py-1.5 text-xs font-semibold text-[#522C25] hover:bg-[#F2F2F2]">Xem</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-16 text-center text-[#522C25]/55">Chưa có thẻ nào. Hãy phát thẻ ở khung trên.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $cards->links() }}</div>
</div>

<script>
// ── Đầu đọc RFID (Web Serial, tự kết nối khi cắm USB) + Autocomplete khách ──
document.addEventListener('DOMContentLoaded', () => {

    const uidInput  = document.getElementById('uid-input');
    const statusEl  = document.getElementById('rfid-status');

    /** Điền UID vào ô input + hiệu ứng xanh lá */
    const setUid = (uid) => {
        uidInput.value = uid;
        uidInput.classList.add('ring-2', 'ring-emerald-400');
        statusEl.textContent = 'Đã đọc UID ' + uid + ' ✓ — chọn khách rồi bấm Phát thẻ.';
        setTimeout(() => uidInput.classList.remove('ring-2', 'ring-emerald-400'), 1500);
    };

    // ===== A) WEB NFC (Android Chrome) =====
    (() => {
        const nfcBtn = document.getElementById('nfc-scan');
        if (!nfcBtn) return;

        if (!('NDEFReader' in window)) {
            // Trình duyệt không hỗ trợ Web NFC → ẩn nút, không báo lỗi
            return;
        }

        // Hiện nút vì trình duyệt hỗ trợ NFC
        nfcBtn.style.display = '';

        let nfcReader = null;
        let scanning  = false;

        nfcBtn.addEventListener('click', async () => {
            if (scanning) {
                // Bấm lần 2 → dừng quét
                nfcReader = null;
                scanning  = false;
                nfcBtn.textContent   = 'NFC';
                statusEl.textContent = 'Đã dừng quét NFC.';
                return;
            }

            try {
                nfcReader = new NDEFReader();
                await nfcReader.scan();
                scanning             = true;
                nfcBtn.textContent   = 'Dừng NFC';
                statusEl.textContent = 'Đang chờ thẻ NFC — chạm thẻ vào lưng điện thoại...';

                nfcReader.onreading = (event) => {
                    // serialNumber trả về dạng "04:a3:b2:c1" → chuẩn hoá thành "04A3B2C1"
                    const uid = (event.serialNumber || '')
                        .replace(/:/g, '')
                        .toUpperCase();
                    if (uid) {
                        setUid(uid);
                        // Tự dừng sau khi đọc được 1 thẻ
                        scanning           = false;
                        nfcBtn.textContent = '📱 NFC';
                    }
                };

                nfcReader.onreadingerror = () => {
                    statusEl.textContent = 'Không đọc được thẻ — thử chạm lại.';
                };

            } catch (e) {
                scanning           = false;
                nfcBtn.textContent = '📱 NFC';
                if (e.name === 'NotAllowedError') {
                    statusEl.textContent = 'Bạn cần cho phép quyền NFC — kiểm tra cài đặt trình duyệt.';
                } else if (e.name === 'NotSupportedError') {
                    statusEl.textContent = 'Thiết bị không có NFC hoặc NFC chưa bật.';
                } else {
                    statusEl.textContent = 'Lỗi NFC: ' + e.message;
                }
            }
        });
    })();

    // ===== B) ĐẦU ĐỌC RFID USB (Web Serial) =====
    (() => {
        const btn = document.getElementById('rfid-connect');
        if (!btn) return;

        if (!('serial' in navigator)) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            statusEl.textContent = 'Trình duyệt không hỗ trợ Web Serial — dùng Chrome/Edge, hoặc gõ UID thủ công.';
            return;
        }

        let reading = false;

        async function startReading(port) {
            if (reading) return;
            try { await port.open({ baudRate: 9600 }); } catch (e) { /* InvalidStateError = đã mở sẵn */ }
            if (!port.readable) {
                statusEl.textContent = 'Mở cổng thất bại — hãy ĐÓNG Arduino Serial Monitor / bridge (đang giữ cổng COM) rồi thử lại.';
                return;
            }
            reading = true;
            btn.textContent = 'Đang đọc thẻ ●';
            btn.disabled = true;
            statusEl.textContent = 'Đã kết nối đầu đọc — quẹt thẻ...';
            try {
                const decoder = new TextDecoderStream();
                port.readable.pipeTo(decoder.writable).catch(() => {});
                const reader = decoder.readable.getReader();
                let buf = '';
                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    buf += value;
                    let i;
                    while ((i = buf.indexOf('\n')) >= 0) {
                        const line = buf.slice(0, i).trim();
                        buf = buf.slice(i + 1);
                        if (line.startsWith('UID:')) setUid(line.slice(4).trim().toUpperCase());
                    }
                }
            } catch (e) {
                statusEl.textContent = 'Mất kết nối đầu đọc: ' + e.message;
            }
            reading = false;
            btn.disabled = false;
            btn.textContent = 'Kết nối đầu đọc';
        }

        // Tự kết nối lại đầu đọc ĐÃ cấp quyền (khi tải trang).
        navigator.serial.getPorts().then((ports) => {
            if (ports.length && !reading) startReading(ports[0]);
        }).catch(() => {});

        // Tự kết nối khi CẮM USB (thiết bị đã từng cấp quyền).
        navigator.serial.addEventListener('connect', (e) => { if (!reading) startReading(e.target); });
        navigator.serial.addEventListener('disconnect', () => {
            reading = false; btn.disabled = false; btn.textContent = 'Kết nối đầu đọc';
            statusEl.textContent = 'Đầu đọc đã rút. Cắm lại sẽ tự kết nối.';
        });

        // Bấm: ưu tiên dùng lại cổng ĐÃ cấp quyền (không hiện hộp thoại); chưa có thì mới xin chọn.
        btn.addEventListener('click', async () => {
            try {
                const granted = await navigator.serial.getPorts();
                const port = granted.length ? granted[0] : await navigator.serial.requestPort();
                startReading(port);
            } catch (e) {
                statusEl.textContent = (e && e.name === 'NotFoundError')
                    ? 'Bạn chưa chọn cổng — bấm lại, CLICK dòng USB-SERIAL (COMx) rồi bấm nút "Kết nối" trong hộp thoại.'
                    : 'Không kết nối được: ' + ((e && e.message) || e);
            }
        });
    })();

    // ===== B) AUTOCOMPLETE khách đủ điều kiện =====
    (() => {
        const wrap = document.getElementById('kh-ac');
        const input = document.getElementById('kh-search');
        const list = document.getElementById('kh-list');
        const hidden = document.getElementById('kh-ma');
        if (!wrap || !input || !list) return;
        const items = Array.from(list.querySelectorAll('.kh-item'));

        const filter = () => {
            const q = input.value.trim().toLowerCase();
            items.forEach((it) => {
                const ok = q === '' || (it.dataset.search || '').includes(q);
                it.style.display = ok ? '' : 'none';
            });
        };

        input.addEventListener('focus', () => { list.classList.remove('hidden'); filter(); });
        input.addEventListener('input', () => { hidden.value = ''; list.classList.remove('hidden'); filter(); });
        items.forEach((it) => it.addEventListener('click', () => {
            hidden.value = it.dataset.ma;
            input.value = it.dataset.label;
            list.classList.add('hidden');
        }));
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) list.classList.add('hidden'); });
    })();
});
</script>
@endsection
