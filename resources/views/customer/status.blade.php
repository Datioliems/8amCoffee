@extends('layouts.customer')

@section('title', 'Trạng thái đơn hàng')

@section('content')
@php
    $steps = [
        'cho_xac_nhan' => ['label' => 'Chờ xác nhận', 'copy' => 'Nhân viên sẽ nhận đơn trong giây lát.', 'icon' => '01'],
        'da_xac_nhan' => ['label' => 'Đã xác nhận', 'copy' => 'Đơn đã được chuyển tới quầy pha chế.', 'icon' => '02'],
        'dang_pha_che' => ['label' => 'Đang pha chế', 'copy' => 'Đồ uống của bạn đang được chuẩn bị.', 'icon' => '03'],
        'da_phuc_vu' => ['label' => 'Đã phục vụ', 'copy' => 'Nhân viên đã phục vụ đơn. Vui lòng thanh toán tại quầy.', 'icon' => '04'],
        'hoan_thanh' => ['label' => 'Đơn đã thanh toán', 'copy' => 'Cảm ơn bạn đã ghé 8am.coffee.', 'icon' => '05'],
    ];
    $active = match($order->trang_thai) {
        'dang_chon' => ['label' => 'Chưa gửi đơn', 'copy' => 'Bạn có thể quay lại thực đơn để chọn thêm món.', 'icon' => '...'],
        'da_huy' => ['label' => 'Đơn đã hủy', 'copy' => 'Đơn này đã hủy. Bạn có thể quay lại để gọi món khác.', 'icon' => '!'],
        default => $steps[$order->trang_thai] ?? ['label' => 'Đơn đã hủy', 'copy' => 'Bạn có thể quay lại để gọi món khác.', 'icon' => '!'],
    };
    $keys = array_keys($steps);
    $currentIndex = array_search($order->trang_thai, $keys, true);
@endphp

<div class="mx-auto max-w-xl">
    @if(session('info'))
    <div class="mb-5 rounded-2xl bg-blue-50 px-4 py-3 text-sm text-blue-700 ring-1 ring-blue-100">
        {{ session('info') }}
    </div>
    @endif

    <div class="rounded-[2rem] bg-white p-6 text-center ring-1 ring-[#522C25]/10 am-shadow md:p-8">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[#E82C2A] text-2xl font-bold text-white">
            {{ $active['icon'] }}
        </div>
        <p class="am-mono mt-6 text-xs uppercase tracking-[0.16em] text-[#522C25]/55">Mã đơn {{ $order->ma_order }}</p>
        <h1 class="am-display mt-2 text-5xl leading-none text-[#1A1A1A]">{{ $active['label'] }}</h1>
        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-[#522C25]/65">{{ $active['copy'] }}</p>

        @if($order->trang_thai !== 'da_huy')
            <div class="mt-8 space-y-3 text-left">
                @foreach($steps as $status => $step)
                    @php
                        $idx = array_search($status, $keys, true);
                        $done = $currentIndex !== false && $idx <= $currentIndex;
                    @endphp
                    <div class="flex items-center gap-3 rounded-2xl {{ $done ? 'bg-[#1A1A1A] text-white' : 'bg-[#F6F3F2] text-[#522C25]/60' }} px-4 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $done ? 'bg-white/15' : 'bg-white' }} text-xs font-bold">{{ $step['icon'] }}</span>
                        <span class="am-headline text-sm font-semibold">{{ $step['label'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @if($order->trang_thai === 'dang_chon')
                <a href="{{ route('customer.menu', ['ma_ban' => $order->ma_ban, 'ma_order' => $order->ma_order]) }}"
                   class="rounded-full bg-[#E82C2A] px-5 py-2.5 text-sm font-semibold text-white">
                    Quay lại thực đơn
                </a>
            @else
                {{-- Mọi trạng thái khác dang_chon (đang chờ/pha chế/phục vụ/đã TT/đã hủy):
                     cho phép tạo ĐƠN MỚI ngay cả khi đơn cũ đang được phục vụ. --}}
                @php $dangXuLy = in_array($order->trang_thai, ['cho_xac_nhan','dang_pha_che','da_phuc_vu']); @endphp
                @if($order->ma_ban)
                <form method="POST" action="{{ route('customer.create', $order->ma_ban) }}">
                    @csrf
                    <input type="hidden" name="ten_kh" value="{{ session('customer_profile.ten_kh', $order->ten_khach ?: 'Khách') }}">
                    <input type="hidden" name="sdt_kh" value="{{ session('customer_profile.sdt_kh', $order->sdt_khach) }}">
                    <button type="submit" class="rounded-full bg-[#E82C2A] px-5 py-2.5 text-sm font-semibold text-white">
                        {{ $dangXuLy ? 'Đặt thêm đơn mới' : 'Gọi món khác' }}
                    </button>
                </form>
                @else
                <a href="{{ route('customer.scan', ['ma_ban' => $order->ma_ban]) }}"
                   class="rounded-full bg-[#E82C2A] px-5 py-2.5 text-sm font-semibold text-white">
                    {{ $dangXuLy ? 'Đặt thêm đơn mới' : 'Gọi món khác' }}
                </a>
                @endif
                @if($dangXuLy)
                <p class="mt-1 w-full text-center text-xs text-[#522C25]/55">Đơn hiện tại vẫn đang được phục vụ — đơn mới sẽ là một đơn riêng.</p>
                @endif
            @endif
        </div>

        @if(!in_array($order->trang_thai, ['hoan_thanh', 'da_huy']))
            <p class="mt-6 text-xs text-[#522C25]/55">Trang sẽ tự cập nhật trạng thái.</p>
        @endif
    </div>
</div>

@if(!in_array($order->trang_thai, ['hoan_thanh', 'da_huy']))
<script>
    // Polling trạng thái đơn — chỉ reload khi trạng thái thực sự đổi (mượt hơn meta-refresh).
    (function () {
        const url = @json(route('customer.statusJson', $order->ma_order));
        const current = @json($order->trang_thai);
        async function poll() {
            try {
                const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                if (r.ok) {
                    const data = await r.json();
                    if (data.trang_thai && data.trang_thai !== current) {
                        window.location.reload();
                        return;
                    }
                }
            } catch (e) { /* bỏ qua lỗi mạng tạm thời */ }
            setTimeout(poll, 5000);
        }
        setTimeout(poll, 5000);
    })();
</script>
@endif
@endsection
