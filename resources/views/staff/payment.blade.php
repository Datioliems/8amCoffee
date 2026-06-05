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
@endsection
