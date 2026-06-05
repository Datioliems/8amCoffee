@extends('layouts.app')
@section('page-title', 'Thanh toán - ' . ($order->ban ? 'Bàn ' . $order->ban->so_ban : 'Mang về'))

@section('content')
@php
    $bankPayload = implode('|', [
        '8AM COFFEE',
        'ORDER:' . $order->ma_order,
        'AMOUNT:' . (int) $tongTien,
        'CONTENT: THANH TOAN ' . $order->ma_order,
    ]);
    $momoPayload = 'MOMO|8AM COFFEE|ORDER:' . $order->ma_order . '|AMOUNT:' . (int) $tongTien;
@endphp

<div class="min-h-[calc(100vh-10rem)] py-4">
    <div id="payment-layout" class="mx-auto grid max-w-xl items-start gap-5 transition-all duration-300">
        <section class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-[0_18px_50px_rgba(82,44,37,0.08)] sm:p-5">
            <div class="flex flex-col gap-3 border-b border-[#522C25]/10 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#8B5A2B]/70">Thanh toán</p>
                    <h2 class="mt-1 text-lg font-semibold text-[#1A1A1A]">Đơn #{{ $order->ma_order }}</h2>
                    <p class="mt-1 text-sm text-[#522C25]/60">{{ $order->ban ? 'Bàn ' . $order->ban->so_ban : 'Mang về' }} · {{ $order->chiTietOrders->sum('so_luong') }} món</p>
                    <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->dung_coc_nhua ? 'bg-[#FFE3D6] text-[#9a3412]' : 'bg-[#E8F0DD] text-[#3f5325]' }}">
                        {{ $order->dung_coc_nhua ? 'Mang về (cốc nhựa)' : 'Uống tại bàn' }}
                    </span>
                </div>
                <div class="rounded-xl bg-[#FFF7E8] px-4 py-3 text-left sm:text-right">
                    <p class="text-xs font-medium text-[#8B5A2B]">Tổng cần thu</p>
                    <p class="text-2xl font-bold text-[#8B5A2B]">{{ number_format($tongTien, 0, ',', '.') }}đ</p>
                </div>
            </div>

            @if($order->hoaDon)
            {{-- ── Đơn đã thanh toán: hiện kết quả + in hóa đơn ── --}}
            <div class="mt-5 rounded-2xl border border-green-200 bg-green-50 p-5 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-600 text-2xl text-white">✓</div>
                <p class="mt-3 text-lg font-bold text-green-800">Đã thanh toán</p>
                <p class="mt-1 text-sm text-green-700">Hóa đơn {{ $order->hoaDon->ma_hoa_don }} · {{ number_format($order->hoaDon->tong_tien_sau_ck, 0, ',', '.') }}đ</p>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-center">
                    <a href="{{ route('invoice.sale', $order->ma_order) }}" target="_blank"
                       class="rounded-xl bg-[#8B5A2B] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#6F4621]">In hóa đơn</a>
                    <a href="{{ route('orders.index') }}"
                       class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/15 hover:bg-[#F2F2F2]">Về danh sách đơn</a>
                </div>
            </div>
            @else
            {{-- ── Danh sách món + TÁCH NHIỀU MÓN (chọn số lượng từng món) ── --}}
            <form action="{{ route('orders.split', $order->ma_order) }}" method="POST" class="mt-4">
                @csrf
                <div class="max-h-[260px] space-y-2 overflow-y-auto pr-1">
                    @foreach($order->chiTietOrders as $item)
                    <div class="rounded-xl border border-[#522C25]/10 bg-[#FAF7F2] p-3">
                        <div class="flex items-start justify-between gap-4 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-[#1A1A1A]">{{ $item->mon->ten_mon }} <span class="text-[#522C25]/60">x{{ $item->so_luong }}</span></p>
                                @if($item->ghi_chu)
                                    <p class="mt-1 text-xs text-[#522C25]/55">{{ $item->ghi_chu }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <p class="font-semibold text-[#522C25]">{{ number_format(($item->don_gia_tai_thoi_diem + $item->options->sum('gia_them')) * $item->so_luong, 0, ',', '.') }}đ</p>
                                <div class="flex items-center gap-1">
                                    <label class="text-[11px] font-semibold text-[#522C25]/55">Tách</label>
                                    <input type="number" name="tach[{{ $item->ma_mon }}]" min="0" max="{{ $item->so_luong }}" value="0"
                                           class="w-14 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-center text-sm focus:border-[#8B5A2B] focus:ring-[#8B5A2B]">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button class="mt-2 w-full rounded-lg bg-white py-2 text-sm font-semibold text-[#8B5A2B] ring-1 ring-[#8B5A2B]/20 transition hover:bg-[#FFF7E8]">
                    Tách các món đã chọn ra đơn mới
                </button>
                <p class="mt-1 text-[11px] text-[#522C25]/45">Nhập số lượng cần tách cho từng món (để 0 nếu giữ lại). Phải để lại ít nhất 1 món ở đơn gốc.</p>
            </form>

            {{-- ── Gộp đơn ── --}}
            <div class="mt-4 rounded-xl border border-[#522C25]/10 bg-[#FAF7F2] p-3">
                <form action="{{ route('orders.merge', $order->ma_order) }}" method="POST" class="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end">
                    @csrf
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/60">Gộp đơn khác vào đơn này</label>
                        <select name="target_order" class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm focus:border-[#8B5A2B] focus:ring-[#8B5A2B]">
                            <option value="">Chọn đơn cần gộp</option>
                            @foreach($mergeTargets as $target)
                                <option value="{{ $target->ma_order }}">
                                    {{ $target->ma_order }} - {{ $target->ban ? 'Bàn ' . $target->ban->so_ban : 'Mang về' }} - {{ $target->trang_thai }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">
                        Gộp đơn
                    </button>
                </form>
            </div>

            {{-- ── Thanh toán ── --}}
            <form method="POST" action="{{ route('payment.process', $order->ma_order) }}" class="mt-4 border-t border-[#522C25]/10 pt-4">
                @csrf

                {{-- ── Thẻ thành viên / Đổi điểm ── --}}
                <div id="loyalty-panel" class="mb-3 rounded-xl border border-[#522C25]/10 bg-[#FAF7F2] p-3"
                     data-total="{{ (int) $tongTien }}"
                     data-lookup="{{ route('payment.card-lookup', $order->ma_order) }}">
                    <p class="mb-2 text-xs font-semibold text-[#8B5A2B]">Thẻ thành viên / Đổi điểm</p>
                    <div class="relative" id="loy-ac">
                        <div class="flex gap-2">
                            <input type="text" id="loy-q" placeholder="Quẹt thẻ / gõ UID hoặc tên/SĐT" autocomplete="off"
                                   class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm focus:border-[#8B5A2B] focus:ring-[#8B5A2B]">
                            <button type="button" id="loy-connect" title="Kết nối đầu đọc RFID"
                                    class="shrink-0 rounded-lg border border-[#8B5A2B]/30 bg-[#FFF7E8] px-2.5 py-2 text-[11px] font-semibold text-[#8B5A2B] hover:bg-[#FCEFD6]">Đầu đọc</button>
                            <button type="button" id="loy-lookup"
                                    class="shrink-0 rounded-lg bg-[#1A1A1A] px-3 py-2 text-sm font-semibold text-white hover:bg-black">Tra cứu</button>
                        </div>
                        <div id="loy-list" class="absolute z-20 mt-1 hidden max-h-52 w-full overflow-auto rounded-lg border border-[#522C25]/15 bg-white shadow-lg">
                            @foreach(($cardHolders ?? []) as $h)
                                <button type="button" class="loy-item block w-full border-b border-[#522C25]/5 px-3 py-2 text-left text-sm hover:bg-[#FFF7E8]"
                                        data-uid="{{ $h['uid'] }}"
                                        data-search="{{ \Illuminate\Support\Str::lower(($h['ten_kh'] ?? '') . ' ' . ($h['sdt'] ?? '') . ' ' . ($h['uid'] ?? '')) }}">
                                    <span class="font-medium">{{ $h['ten_kh'] }}</span>
                                    <span class="text-[#522C25]/55"> · {{ $h['sdt_che'] }}</span>
                                    <span class="font-mono text-[11px] text-[#522C25]/45"> · {{ $h['ma_the'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <p id="loy-rfid-status" class="mt-1 text-[11px] text-[#522C25]/55"></p>
                    <p id="loy-msg" class="mt-1 text-[11px] text-[#BB0011]"></p>

                    <div id="loy-info" class="mt-2 hidden rounded-lg bg-white p-3 ring-1 ring-[#522C25]/10">
                        <p class="text-sm">Khách: <b id="loy-name">—</b>
                            · Số dư <b id="loy-diem">0</b> điểm
                            · Hạng <span id="loy-hang" class="rounded-full bg-[#FFF7E8] px-2 py-0.5 text-[11px] font-semibold text-[#8B5A2B]">—</span></p>
                        <p id="loy-uudai" class="mt-1 hidden text-[11px] font-semibold text-emerald-700"></p>
                        <div class="mt-2 flex flex-wrap items-end gap-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Đổi (điểm)</label>
                                <input type="number" id="loy-input" min="0" step="1" value="0"
                                       class="w-28 rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                            </div>
                            <button type="button" id="loy-max"
                                    class="rounded-lg bg-white px-3 py-2 text-sm font-semibold text-[#8B5A2B] ring-1 ring-[#8B5A2B]/25 hover:bg-[#FFF7E8]">Đổi tối đa</button>
                            <p class="text-sm">Giảm: <b id="loy-giam" class="text-[#BB0011]">0đ</b></p>
                        </div>
                        <button type="button" id="loy-clear" class="mt-2 text-[11px] text-[#522C25]/55 underline">Bỏ áp dụng thẻ</button>
                    </div>

                    <input type="hidden" name="ma_the" id="loy-ma-the" value="">
                    <input type="hidden" name="so_diem_doi" id="loy-so-diem" value="0">
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#522C25]/65">Chiết khấu (%)</label>
                        <input type="number" name="chiet_khau" value="0" min="0" max="100" step="1"
                               class="w-full rounded-xl border border-[#522C25]/15 px-4 py-2.5 text-sm focus:border-[#8B5A2B] focus:ring-[#8B5A2B]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#522C25]/65">Phương thức thanh toán</label>
                        <select name="phuong_thuc_tt" id="payment-method" class="w-full rounded-xl border border-[#522C25]/15 px-4 py-2.5 text-sm focus:border-[#8B5A2B] focus:ring-[#8B5A2B]">
                            <option value="tien_mat">Tiền mặt</option>
                            <option value="chuyen_khoan">Chuyển khoản</option>
                            <option value="momo">MoMo</option>
                            <option value="vnpay">VNPay</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="mt-4 w-full rounded-xl bg-[#8B5A2B] py-3 text-sm font-semibold text-white shadow-lg shadow-[#8B5A2B]/20 transition hover:bg-[#6F4621]">
                    Xác nhận thanh toán
                </button>

                @if($vnpayReady)
                {{-- Thanh toán online thật qua cổng VNPay (sandbox). Dùng cùng ô chiết khấu ở trên. --}}
                <button type="submit" formaction="{{ route('payment.vnpay.create', $order->ma_order) }}" formmethod="POST"
                        class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl border border-[#0b4ba3]/20 bg-[#0b4ba3] py-3 text-sm font-semibold text-white transition hover:bg-[#093d85]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    Thanh toán online qua VNPay
                </button>
                <p class="mt-1 text-center text-[11px] text-[#522C25]/45">Chuyển hướng sang cổng VNPay sandbox để thanh toán an toàn.</p>
                @endif
            </form>
            @endif
        </section>

        <aside id="qr-panel" class="hidden rounded-2xl border border-[#522C25]/10 bg-white p-5 shadow-[0_18px_50px_rgba(82,44,37,0.08)] lg:sticky lg:top-6">
            <p class="text-sm font-semibold text-[#1A1A1A]">Mã QR thanh toán</p>
            <p class="mt-1 text-xs text-[#522C25]/60" id="qr-copy">Quét mã để thanh toán đơn #{{ $order->ma_order }}.</p>

            <div class="mt-5 grid place-items-center rounded-2xl bg-[#FAF7F2] p-6">
                <div id="bank-qr" class="hidden rounded-xl bg-white p-4 shadow-sm">
                    {!! QrCode::format('svg')->size(220)->margin(1)->generate($bankPayload) !!}
                </div>
                <div id="momo-qr" class="hidden rounded-xl bg-white p-4 shadow-sm">
                    {!! QrCode::format('svg')->size(220)->margin(1)->generate($momoPayload) !!}
                </div>
            </div>

            <div class="mt-5 space-y-2 rounded-2xl bg-[#FFF7E8] p-4 text-sm text-[#522C25]">
                <p><span class="font-semibold">Nội dung:</span> THANH TOAN {{ $order->ma_order }}</p>
                <p><span class="font-semibold">Số tiền:</span> {{ number_format($tongTien, 0, ',', '.') }}đ</p>
            </div>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const layout = document.getElementById('payment-layout');
    const method = document.getElementById('payment-method');
    if (!method) return;   // đơn đã thanh toán: không có form, bỏ qua
    const panel = document.getElementById('qr-panel');
    const bankQr = document.getElementById('bank-qr');
    const momoQr = document.getElementById('momo-qr');
    const copy = document.getElementById('qr-copy');

    const syncQr = () => {
        const value = method.value;
        const showBank = value === 'chuyen_khoan' || value === 'vnpay';
        const showMomo = value === 'momo';
        const hasQr = showBank || showMomo;

        panel.classList.toggle('hidden', !hasQr);
        bankQr.classList.toggle('hidden', !showBank);
        momoQr.classList.toggle('hidden', !showMomo);

        layout.classList.toggle('max-w-xl', !hasQr);
        layout.classList.toggle('max-w-6xl', hasQr);
        layout.classList.toggle('lg:grid-cols-[minmax(0,1fr)_380px]', hasQr);

        copy.textContent = showMomo
            ? 'Quét mã MoMo để thanh toán đơn #{{ $order->ma_order }}.'
            : 'Quét mã chuyển khoản để thanh toán đơn #{{ $order->ma_order }}.';
    };

    method.addEventListener('change', syncQr);
    syncQr();
});
</script>

<script>
// ── Thẻ thành viên: tra cứu + tính giảm giá theo điểm ──
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('loyalty-panel');
    if (!panel) return;                       // đơn đã thanh toán → không có panel
    const total = parseInt(panel.dataset.total || '0', 10);
    const lookupUrl = panel.dataset.lookup;
    const $ = (id) => document.getElementById(id);
    const ckInput = document.querySelector('input[name="chiet_khau"]');

    let cfg = null;     // { point_value, min_redeem, max_redeem_pct }
    let balance = 0;

    const fmt = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ';
    const netTotal = () => {
        const ck = Math.min(100, Math.max(0, parseFloat(ckInput?.value || '0') || 0));
        return Math.round(total * (1 - ck / 100));
    };
    const maxRedeemable = () => {
        if (!cfg || cfg.point_value <= 0) return 0;
        const byBill = Math.floor(netTotal() * cfg.max_redeem_pct / 100 / cfg.point_value);
        return Math.max(0, Math.min(balance, byBill));
    };
    const clearHidden = () => { $('loy-ma-the').value = ''; $('loy-so-diem').value = 0; };

    async function lookup() {
        const q = $('loy-q').value.trim();
        $('loy-msg').textContent = '';
        if (!q) { $('loy-msg').textContent = 'Nhập UID thẻ hoặc SĐT.'; return; }
        try {
            const r = await fetch(lookupUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            const d = await r.json();
            if (!d.ok) { $('loy-msg').textContent = d.message || 'Không tìm thấy thẻ.'; $('loy-info').classList.add('hidden'); clearHidden(); return; }
            cfg = { point_value: d.point_value, min_redeem: d.min_redeem, max_redeem_pct: d.max_redeem_pct };
            balance = d.diem;
            $('loy-name').textContent = d.ten_kh || d.ma_the;
            $('loy-diem').textContent = d.diem;
            $('loy-hang').textContent = (d.hang_nhan || d.hang_the) + (d.he_so > 1 ? ' · tích x' + d.he_so : '');
            $('loy-ma-the').value = d.ma_the;
            $('loy-input').value = 0;
            $('loy-info').classList.remove('hidden');
            applyTierDiscount(d);   // tự áp ưu đãi giảm giá theo hạng
            recompute();
        } catch (e) { $('loy-msg').textContent = 'Lỗi tra cứu thẻ.'; }
    }

    // Tự điền chiết khấu (%) theo ưu đãi của hạng thẻ. Giảm theo TIỀN → quy ra % của hóa đơn.
    function applyTierDiscount(d) {
        const note = $('loy-uudai');
        note.classList.add('hidden');
        note.textContent = '';
        if (!ckInput || !d || !(d.giam_gia_tri > 0)) return;

        let pct = 0, moTa = '';
        if (d.giam_loai === 'phan_tram') {
            pct = Math.min(100, d.giam_gia_tri);
            moTa = 'giảm ' + d.giam_gia_tri + '%';
        } else { // 'tien'
            pct = total > 0 ? Math.min(100, Math.round(d.giam_gia_tri / total * 10000) / 100) : 0;
            moTa = 'giảm ' + fmt(d.giam_gia_tri);
        }
        if (pct > 0) {
            ckInput.value = pct;
            ckInput.dispatchEvent(new Event('input'));
            note.textContent = 'Ưu đãi hạng ' + (d.hang_nhan || d.hang_the) + ': ' + moTa + ' — đã áp vào ô chiết khấu (' + pct + '%).';
            note.classList.remove('hidden');
        }
    }

    function recompute() {
        if (!cfg) return;
        let pts = parseInt($('loy-input').value || '0', 10);
        if (isNaN(pts) || pts < 0) pts = 0;
        const max = maxRedeemable();
        if (pts > max) { pts = max; $('loy-input').value = pts; }
        $('loy-msg').textContent = (pts > 0 && pts < cfg.min_redeem) ? ('Đổi tối thiểu ' + cfg.min_redeem + ' điểm.') : '';
        const apply = (pts >= cfg.min_redeem) ? pts : 0;
        $('loy-so-diem').value = apply;
        $('loy-giam').textContent = fmt(apply * cfg.point_value);
    }

    $('loy-lookup').addEventListener('click', lookup);
    $('loy-q').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); lookup(); } });
    $('loy-input').addEventListener('input', recompute);
    $('loy-max').addEventListener('click', () => { $('loy-input').value = maxRedeemable(); recompute(); });
    if (ckInput) ckInput.addEventListener('input', recompute);
    $('loy-clear').addEventListener('click', () => {
        cfg = null; balance = 0; $('loy-info').classList.add('hidden');
        $('loy-q').value = ''; $('loy-msg').textContent = ''; clearHidden();
        $('loy-uudai').classList.add('hidden');
        if (ckInput) { ckInput.value = 0; ckInput.dispatchEvent(new Event('input')); }   // bỏ ưu đãi hạng
    });

    // ── Autocomplete thẻ đang hoạt động (gõ tên/SĐT/UID → chọn → tra cứu) ──
    (() => {
        const wrap = $('loy-ac'), list = $('loy-list'), input = $('loy-q');
        if (!wrap || !list) return;
        const its = Array.from(list.querySelectorAll('.loy-item'));
        const filt = () => {
            const s = input.value.trim().toLowerCase();
            its.forEach((it) => { it.style.display = (s === '' || (it.dataset.search || '').includes(s)) ? '' : 'none'; });
        };
        input.addEventListener('input', () => { list.classList.remove('hidden'); filt(); });
        input.addEventListener('focus', () => { list.classList.remove('hidden'); filt(); });
        its.forEach((it) => it.addEventListener('click', () => { input.value = it.dataset.uid; list.classList.add('hidden'); lookup(); }));
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) list.classList.add('hidden'); });
    })();

    // ── Đầu đọc RFID: tự kết nối khi cắm USB → quẹt thẻ là tự tra cứu ──
    (() => {
        const btn = $('loy-connect'), st = $('loy-rfid-status'), q = $('loy-q');
        if (!btn) return;
        if (!('serial' in navigator)) {
            btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed');
            st.textContent = 'Trình duyệt không hỗ trợ Web Serial (Chrome/Edge).';
            return;
        }
        let reading = false;
        async function start(port) {
            if (reading) return;
            try { await port.open({ baudRate: 9600 }); } catch (e) { /* InvalidStateError = đã mở sẵn */ }
            if (!port.readable) { st.textContent = 'Mở cổng thất bại — ĐÓNG Arduino Serial Monitor / bridge rồi thử lại.'; return; }
            reading = true; btn.textContent = 'Đầu đọc ●'; st.textContent = 'Đầu đọc sẵn sàng — quẹt thẻ...';
            try {
                const dec = new TextDecoderStream();
                port.readable.pipeTo(dec.writable).catch(() => {});
                const rd = dec.readable.getReader();
                let buf = '';
                while (true) {
                    const { value, done } = await rd.read();
                    if (done) break;
                    buf += value;
                    let i;
                    while ((i = buf.indexOf('\n')) >= 0) {
                        const line = buf.slice(0, i).trim();
                        buf = buf.slice(i + 1);
                        if (line.startsWith('UID:')) { q.value = line.slice(4).trim().toUpperCase(); $('loy-list').classList.add('hidden'); lookup(); }
                    }
                }
            } catch (e) { st.textContent = 'Mất kết nối đầu đọc.'; }
            reading = false; btn.textContent = 'Đầu đọc';
        }
        navigator.serial.getPorts().then((p) => { if (p.length && !reading) start(p[0]); }).catch(() => {});
        navigator.serial.addEventListener('connect', (e) => { if (!reading) start(e.target); });
        btn.addEventListener('click', async () => {
            try {
                const granted = await navigator.serial.getPorts();
                start(granted.length ? granted[0] : await navigator.serial.requestPort());
            } catch (e) {
                st.textContent = (e && e.name === 'NotFoundError')
                    ? 'Bạn chưa chọn cổng — bấm lại, CLICK dòng USB-SERIAL (COMx) rồi bấm "Kết nối".'
                    : 'Không kết nối được: ' + ((e && e.message) || e);
            }
        });
    })();
});
</script>
@endsection
