@extends('layouts.customer')

@section('title', 'Trạng thái đơn hàng')

@push('head')
<script src="{{ asset('js/lottie.min.js') }}" defer></script>
@endpush

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
    $labelOf = fn ($s) => $steps[$s]['label'] ?? ($s === 'dang_chon' ? 'Chưa gửi' : ($s === 'da_huy' ? 'Đã hủy' : $s));

    // Cột phải (nhân vật pha chế) chỉ ẩn khi đơn đã xong/đã hủy.
    $hideRight = in_array($order->trang_thai, ['hoan_thanh', 'da_huy'], true);
    $isReady   = $order->trang_thai === 'da_phuc_vu';

    // Tạm tính giỏ hàng (đơn giá tại thời điểm + phụ thu option) × số lượng.
    $tongTamTinh = $items->sum(fn ($ct) => ($ct->don_gia_tai_thoi_diem + $ct->options->sum('gia_them')) * $ct->so_luong);
@endphp

<div class="mx-auto max-w-5xl">
    @if(session('info'))
    <div class="mb-5 rounded-2xl bg-blue-50 px-4 py-3 text-sm text-blue-700 ring-1 ring-blue-100">
        {{ session('info') }}
    </div>
    @endif

    <div class="grid gap-6 {{ $hideRight ? '' : 'lg:grid-cols-2 lg:items-start' }}">

        {{-- ───────── CỘT TRÁI: trạng thái + giỏ hàng ───────── --}}
        <div class="{{ $hideRight ? 'mx-auto w-full max-w-xl' : '' }}">
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

            {{-- Danh sách món đã đặt (giỏ hàng) --}}
            @if($order->trang_thai !== 'da_huy')
            <div class="mt-5 rounded-[1.6rem] bg-white p-5 text-left ring-1 ring-[#522C25]/10 am-shadow">
                <p class="am-mono text-xs uppercase tracking-[0.16em] text-[#522C25]/55">Giỏ hàng · {{ $items->count() }} món</p>
                <div class="mt-3 space-y-3">
                    @forelse($items as $ct)
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#1A1A1A]">
                                {{ optional($ct->mon)->ten_mon ?? $ct->ma_mon }}
                                <span class="text-[#522C25]/55">×{{ $ct->so_luong }}</span>
                            </p>
                            @if($ct->options->isNotEmpty())
                            <p class="mt-0.5 text-xs text-[#522C25]/60">{{ $ct->options->pluck('ten_lua_chon')->implode(' · ') }}</p>
                            @endif
                            @if($ct->ghi_chu)
                            <p class="mt-0.5 text-xs italic text-[#522C25]/50">“{{ $ct->ghi_chu }}”</p>
                            @endif
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-[#522C25]">
                            {{ number_format(($ct->don_gia_tai_thoi_diem + $ct->options->sum('gia_them')) * $ct->so_luong, 0, ',', '.') }}đ
                        </p>
                    </div>
                    @empty
                    <p class="text-sm text-[#522C25]/55">Chưa có món nào trong đơn.</p>
                    @endforelse
                </div>
                @if($items->isNotEmpty())
                <div class="mt-4 flex items-center justify-between border-t border-[#522C25]/10 pt-3">
                    <span class="text-sm text-[#522C25]/70">Tạm tính</span>
                    <span class="am-mono text-base font-bold text-[#E82C2A]">{{ number_format($tongTamTinh, 0, ',', '.') }}đ</span>
                </div>
                @endif
            </div>
            @endif

            {{-- Đơn khác trong phiên --}}
            @if($otherOrders->isNotEmpty())
            <div class="mt-4 rounded-[1.6rem] bg-white p-5 text-left ring-1 ring-[#522C25]/10">
                <p class="am-mono text-xs uppercase tracking-[0.16em] text-[#522C25]/55">Đơn khác của bạn</p>
                <div class="mt-3 space-y-2">
                    @foreach($otherOrders as $o)
                    <a href="{{ route('customer.status', $o->ma_order) }}"
                       class="flex items-center justify-between rounded-xl bg-[#F6F3F2] px-3 py-2 transition hover:bg-[#EFEAE8]">
                        <span class="am-mono text-xs text-[#522C25]/70">{{ $o->ma_order }}</span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-[#522C25] ring-1 ring-[#522C25]/10">{{ $labelOf($o->trang_thai) }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ───────── CỘT PHẢI: nhân vật pha chế + lời nhắn + đếm ngược ───────── --}}
        @unless($hideRight)
        <div class="lg:sticky lg:top-24">
            <div class="rounded-[2rem] bg-gradient-to-b from-[#FFF4F2] to-white p-6 text-center ring-1 ring-[#522C25]/10 am-shadow">
                {{-- Lời nhắn (phía trên đầu nhân vật) --}}
                <p class="am-headline text-lg font-semibold leading-snug text-[#1A1A1A]">
                    {{ $isReady ? ($onlyEats ? 'Món ăn của bạn đã sẵn sàng!' : 'Đồ uống của bạn đã sẵn sàng!') : $prepMessage }}
                </p>

                {{-- Đếm ngược 5 phút (chỉ khi đang pha chế) --}}
                @if($order->trang_thai === 'dang_pha_che')
                <div class="mt-3" data-brew-remaining="{{ $brewRemainingSec }}">
                    <p class="text-xs uppercase tracking-[0.16em] text-[#522C25]/55">Dự kiến hoàn thành sau</p>
                    <p id="brew-countdown" class="am-display text-5xl leading-none tabular-nums text-[#E82C2A]">--:--</p>
                    <p id="brew-note" class="mt-1 hidden text-xs font-semibold text-[#E82C2A]">Sắp xong rồi, bạn chờ chút nhé!</p>
                </div>
                @endif

                {{-- Nhân vật Lottie (kèm fallback emoji nếu Lottie lỗi/không tải được) --}}
                <div class="relative mx-auto mt-4 aspect-square w-full max-w-[280px]">
                    <div id="dog-fallback" class="absolute inset-0 flex items-center justify-center text-7xl">🐶☕</div>
                    <div id="dog-lottie" class="relative h-full w-full"></div>
                </div>

                <p class="mt-1 text-xs text-[#522C25]/50">Barista 8am đang pha cho bạn…</p>
            </div>
        </div>
        @endunless
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

    // Đếm ngược 5 phút khi đang pha chế.
    (function () {
        const box = document.querySelector('[data-brew-remaining]');
        const out = document.getElementById('brew-countdown');
        if (!box || !out) return;
        let remaining = parseInt(box.getAttribute('data-brew-remaining'), 10);
        if (isNaN(remaining)) remaining = 300;
        function tick() {
            if (remaining <= 0) {
                out.textContent = '0:00';
                const note = document.getElementById('brew-note');
                if (note) note.classList.remove('hidden');
                return;
            }
            const m = Math.floor(remaining / 60), s = remaining % 60;
            out.textContent = m + ':' + (s < 10 ? '0' : '') + s;
            remaining--;
            setTimeout(tick, 1000);
        }
        tick();
    })();

    // Khởi tạo Lottie nhân vật pha chế (chạy sau khi lottie.min.js đã nạp — defer xong trước DOMContentLoaded).
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('dog-lottie');
        if (!el || !window.lottie) return;
        try {
            const anim = lottie.loadAnimation({
                container: el,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: @json(asset('animations/barista-dog.json')),
            });
            anim.addEventListener('DOMLoaded', function () {
                const fb = document.getElementById('dog-fallback');
                if (fb) fb.style.display = 'none';
            });
        } catch (e) { /* giữ fallback emoji */ }
    });
</script>
@endif
@endsection
