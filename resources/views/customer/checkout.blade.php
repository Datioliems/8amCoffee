@extends('layouts.customer')
@section('title', 'Xác nhận đặt món')

@section('content')
@php
    $total = $order->chiTietOrders->sum(fn($i) => ($i->don_gia_tai_thoi_diem + $i->options->sum('gia_them')) * $i->so_luong);
@endphp

<div class="mx-auto max-w-2xl">
    <div class="mb-6 overflow-hidden rounded-[1.75rem] bg-[#1A1A1A] text-white">
        <div class="relative p-6 md:p-8">
            <img src="{{ asset('images/latte.jpg') }}" alt="Xác nhận đơn hàng" class="absolute inset-0 h-full w-full object-cover opacity-35">
            <div class="absolute inset-0 bg-gradient-to-t from-[#1A1A1A] to-[#1A1A1A]/40"></div>
            <div class="relative">
                <p class="text-xs uppercase tracking-[0.2em] text-white/70">Bàn {{ $order->ban->so_ban ?? '?' }}</p>
                <h1 class="am-display mt-3 text-5xl leading-none">Xác nhận đơn hàng</h1>
                <p class="mt-3 max-w-md text-sm leading-6 text-white/75">Kiểm tra lại món đã chọn trước khi gửi cho quầy pha chế.</p>
            </div>
        </div>
    </div>

    <div class="rounded-3xl bg-white p-5 ring-1 ring-[#522C25]/10 am-shadow">
        <div class="space-y-3">
            @foreach($order->chiTietOrders as $item)
            <div class="flex items-center justify-between gap-4 rounded-2xl bg-[#F6F3F2] p-4">
                <div class="min-w-0">
                    <p class="am-headline font-semibold text-[#1A1A1A]">{{ $item->mon->ten_mon }}</p>
                    @if($item->ghi_chu)
                        <p class="mt-1 whitespace-pre-line text-xs leading-5 text-[#522C25]/60">{{ $item->ghi_chu }}</p>
                    @endif
                    <p class="mt-1 text-xs text-[#522C25]/60">Số lượng: {{ $item->so_luong }}</p>
                </div>
                <p class="am-mono shrink-0 font-bold text-[#E82C2A]">
                    {{ number_format(($item->don_gia_tai_thoi_diem + $item->options->sum('gia_them')) * $item->so_luong, 0, ',', '.') }}đ
                </p>
            </div>
            @endforeach
        </div>

        <div class="mt-5 flex items-center justify-between rounded-2xl bg-[#1A1A1A] px-5 py-4 text-white">
            <span class="text-sm text-white/70">Tổng thanh toán</span>
            <span class="am-mono text-xl font-bold">{{ number_format($total, 0, ',', '.') }}đ</span>
        </div>

        {{-- GỢI Ý MÓN MUA KÈM (AI market-basket) — nhỏ, mang tính bổ trợ --}}
        @if(!empty($suggestions))
        <div class="mt-5">
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-[#522C25]/45">Gợi ý dùng kèm</p>
            <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 scrollbar-hide">
                @foreach($suggestions as $sg)
                <form method="POST" action="{{ route('customer.addItem', $order->ma_order) }}" class="shrink-0">
                    @csrf
                    <input type="hidden" name="ma_mon" value="{{ $sg['ma_mon'] }}">
                    <input type="hidden" name="so_luong" value="1">
                    <button class="flex items-center gap-2 rounded-full border border-[#522C25]/12 bg-white px-3 py-1.5 text-xs text-[#522C25] transition hover:border-[#E82C2A]/40 hover:bg-[#FFF1F0]">
                        <span class="font-medium">{{ $sg['ten_mon'] }}</span>
                        <span class="text-[#E82C2A]">+{{ number_format($sg['don_gia'], 0, ',', '.') }}đ</span>
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#E82C2A] text-[10px] font-bold text-white">+</span>
                    </button>
                </form>
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('customer.confirm', $order->ma_order) }}" class="mt-5">
            @csrf
            {{-- Hình thức phục vụ: uống tại bàn (ly thật) hay mang về (cốc nhựa) --}}
            <div class="mb-4" x-data="{ ht: '{{ $order->hinh_thuc ?? 'tai_ban' }}' }">
                <p class="am-headline mb-2 text-sm font-semibold text-[#1A1A1A]">Bạn dùng tại quán hay mang về?</p>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl border py-3 text-sm font-medium transition"
                           :class="ht==='tai_ban' ? 'border-[#52613B] bg-[#E8F0DD] text-[#3f5325]' : 'border-[#522C25]/15 bg-white text-[#522C25]'">
                        <input type="radio" name="hinh_thuc" value="tai_ban" x-model="ht" class="hidden">
                        Uống tại bàn
                    </label>
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl border py-3 text-sm font-medium transition"
                           :class="ht==='mang_ve' ? 'border-[#9a3412] bg-[#FFE3D6] text-[#9a3412]' : 'border-[#522C25]/15 bg-white text-[#522C25]'">
                        <input type="radio" name="hinh_thuc" value="mang_ve" x-model="ht" class="hidden">
                        Mang về (cốc nhựa)
                    </label>
                </div>
            </div>
            <button class="am-headline w-full rounded-full bg-[#E82C2A] py-3.5 text-sm font-semibold text-white transition active:scale-[0.98]">
                Gửi đơn cho quán
            </button>
        </form>
        <a href="{{ route('customer.menu', ['ma_ban' => $order->ban->ma_ban, 'ma_order' => $order->ma_order]) }}"
           class="mt-3 block text-center text-sm font-medium text-[#522C25]/65">
            Quay lại chọn thêm món
        </a>
    </div>
</div>
@endsection
