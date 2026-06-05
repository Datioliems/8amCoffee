@props(['order'])
@php
    // Tổng tiền: ưu tiên hóa đơn (đã chốt); nếu chưa có thì tính từ chi tiết + phụ thu options.
    $tong = $order->hoaDon
        ? (int) $order->hoaDon->tong_tien_sau_ck
        : (int) $order->chiTietOrders->sum(fn($i) => ($i->don_gia_tai_thoi_diem + ($i->relationLoaded('options') ? $i->options->sum('gia_them') : 0)) * $i->so_luong);
    $soMon = (int) $order->chiTietOrders->sum('so_luong');
    $fmt = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('H:i') : '—';
@endphp
<div class="relative flex min-h-56 flex-col gap-4 rounded-[1.5rem] bg-white p-5 ring-1 ring-[#522C25]/10 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#522C25]/10">
    {{-- Bấm cả thẻ để mở trang chi tiết (link phủ toàn thẻ, nút thao tác nằm trên) --}}
    <a href="{{ route('orders.show', $order->ma_order) }}" class="absolute inset-0 z-0 rounded-[1.5rem]" aria-label="Xem chi tiết đơn {{ $order->ma_order }}"></a>

    <div class="relative z-10 flex items-start justify-between">
        <div>
            <p class="text-xs uppercase tracking-[0.18em] text-[#522C25]/50">{{ $order->ban ? 'Bàn ' . $order->ban->so_ban : 'Mang về' }}</p>
            <p class="mt-1 text-xl font-semibold text-[#1A1A1A]">{{ $fmt($order->gio_order) }}</p>
            <p class="mt-1 font-mono text-xs text-[#522C25]/60">{{ $order->ma_order }}</p>
        </div>
        <div class="flex flex-col items-end gap-1.5">
            <x-order-status-badge :status="$order->trang_thai" />
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $order->dung_coc_nhua ? 'bg-[#FFE3D6] text-[#9a3412]' : 'bg-[#E8F0DD] text-[#3f5325]' }}">
                {{ $order->dung_coc_nhua ? 'Mang về' : 'Tại bàn' }}
            </span>
        </div>
    </div>

    <div class="relative z-10 space-y-1 text-sm text-[#522C25]/70">
        @forelse($order->chiTietOrders->take(3) as $item)
        <p class="flex justify-between gap-3">
            <span class="line-clamp-1">{{ $item->mon->ten_mon ?? '—' }}</span>
            <span class="whitespace-nowrap"><span class="font-semibold">x{{ $item->so_luong }}</span> · {{ number_format($item->don_gia_tai_thoi_diem, 0, ',', '.') }}đ</span>
        </p>
        @empty
        <p class="italic text-[#522C25]/40">Chưa chọn món nào</p>
        @endforelse
        @if($order->chiTietOrders->count() > 3)
        <p class="text-[#522C25]/45">+ {{ $order->chiTietOrders->count() - 3 }} món khác</p>
        @endif
    </div>

    {{-- Số lượng món + tổng tiền --}}
    <div class="relative z-10 flex items-center justify-between border-t border-[#522C25]/10 pt-2 text-sm">
        <span class="text-[#522C25]/60">{{ $soMon }} món</span>
        <span class="font-semibold text-[#1A1A1A]">{{ number_format($tong, 0, ',', '.') }}đ</span>
    </div>

    {{-- Mốc thời gian: đặt / phục vụ / thanh toán --}}
    <div class="relative z-10 grid grid-cols-3 gap-1 text-[10px] text-[#522C25]/55">
        <span>Đặt: {{ $fmt($order->gio_order) }}</span>
        <span>Phục vụ: {{ $fmt($order->thoi_gian_phuc_vu) }}</span>
        <span>TToán: {{ $fmt($order->thoi_gian_thanh_toan) }}</span>
    </div>

    <div class="relative z-10 mt-auto flex gap-2 pt-2">
        @if($order->trang_thai === 'cho_xac_nhan' && $order->ban)
        <form method="POST" action="{{ route('orders.confirm', $order->ma_order) }}" class="flex-1">
            @csrf @method('PUT')
            <button class="w-full rounded-full bg-[#E82C2A] py-2 text-xs font-semibold text-white">
                Xác nhận
            </button>
        </form>
        @endif
        @if(!$order->ban || in_array($order->trang_thai, ['da_xac_nhan','dang_pha_che','da_phuc_vu']))
        <a href="{{ route('payment.show', $order->ma_order) }}"
           class="flex-1 rounded-full bg-[#52613B] py-2 text-center text-xs font-semibold text-white">
            Thanh toán
        </a>
        @endif
        @if(!in_array($order->trang_thai, ['hoan_thanh','da_huy']))
        <form method="POST" action="{{ route('orders.status', $order->ma_order) }}" class="flex-1"
              onsubmit="return confirm('Hủy đơn {{ $order->ma_order }}?');">
            @csrf @method('PUT')
            <input type="hidden" name="trang_thai" value="da_huy">
            <button class="w-full rounded-full bg-[#F2F2F2] py-2 text-xs font-semibold text-[#BB0011]">
                Hủy đơn
            </button>
        </form>
        @endif
    </div>
</div>
