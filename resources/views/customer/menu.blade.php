@extends('layouts.customer')
@section('title', 'Thực đơn - 8am Coffee')

@push('head')
@if($maOrder)
<meta name="ma-order" content="{{ $maOrder }}">
@endif
@vite(['resources/js/showroom.js', 'resources/js/mon-viewer.js'])
@endpush

@section('content')
{{-- Hero --}}
<section class="overflow-hidden rounded-[1.75rem] bg-[#1A1A1A] text-white am-shadow">
    <div class="relative min-h-56 p-6 md:min-h-72 md:p-8">
        <img src="{{ asset('images/8-AM-Coffee-Roastery-16.jpg') }}" alt="Không gian 8AM Coffee" class="absolute inset-0 h-full w-full object-cover opacity-50">
        <div class="absolute inset-0 bg-gradient-to-t from-[#1A1A1A] via-[#1A1A1A]/45 to-transparent"></div>
        <div class="relative flex h-full flex-col justify-between">
            <div class="flex items-center justify-between gap-3">
                <span class="rounded-full bg-white/15 px-4 py-2 text-xs font-semibold backdrop-blur">Bàn {{ str_pad($ban->so_ban, 2, '0', STR_PAD_LEFT) }} · {{ $ban->vi_tri }}</span>
                <span class="rounded-full bg-[#E82C2A] px-4 py-2 text-xs font-semibold">Đơn mới</span>
            </div>
            <div class="mt-10">
                <p class="mb-3 text-xs uppercase tracking-[0.2em] text-white/70">quầy sáng 8am</p>
                <h1 class="am-display max-w-xl text-5xl leading-none md:text-7xl">Chào buổi sáng!</h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-white/80">Chọn món tại bàn — hoặc xem sơ đồ 3D để đổi bàn ngay bên dưới.</p>
            </div>
        </div>
    </div>
</section>

{{-- Sơ đồ 3D: chọn & đổi bàn (gộp thẳng vào menu) --}}
<section class="mt-5 overflow-hidden rounded-[1.75rem] bg-[#161616] am-shadow">
    <div class="px-5 pt-5">
        <p class="am-mono text-xs uppercase tracking-[0.16em] text-white/55">Tham quan &amp; chọn bàn</p>
        <h2 class="am-headline mt-1 text-xl font-semibold text-white">Sơ đồ 3D quán 8AM</h2>
    </div>
    <div id="showroom-root" class="mt-4 grid grid-cols-1 gap-0 px-0 lg:grid-cols-4 lg:gap-4 lg:px-5"
         data-model-url="{{ \App\Support\Cdn::url('models/'.$model3d) }}"
         data-img-base="{{ asset('images') }}"
         data-photo-url="{{ asset('images/8-AM-Coffee-Roastery-4.jpg') }}"
         data-tables-url="{{ route('customer.tables', $ban->ma_ban) }}"
         data-move-url="{{ route('customer.move', ['ma_ban' => $ban->ma_ban, 'to' => '__TO__']) }}"
         data-redirect-url="{{ route('customer.menu', ['ma_ban' => '__TO__']) }}"
         data-current-table="{{ $ban->ma_ban }}">
        <div id="sr-canvas" class="h-[56vh] w-full lg:col-span-3"></div>
        <div class="space-y-3 px-5 pb-5 pt-2 lg:px-0 lg:pb-0">
            <div id="sr-info" class="text-sm text-white/60">Chạm vào một ghim bàn để xem ảnh bàn, số ghế &amp; trạng thái.</div>
            <div>
                <div class="mb-2 flex flex-wrap gap-1.5 text-[11px]">
                    <button class="sr-floor-tag rounded-full bg-white/10 px-2.5 py-1 text-white/80 ring-1 ring-white/15 transition hover:bg-white/20" data-floor-img="Stard_1_out.jpg">Tầng 1 · ngoài</button>
                    <button class="sr-floor-tag rounded-full bg-white/10 px-2.5 py-1 text-white/80 ring-1 ring-white/15 transition hover:bg-white/20" data-floor-img="stard_1_in.jpg">Tầng 1 · trong</button>
                    <button class="sr-floor-tag rounded-full bg-white/10 px-2.5 py-1 text-white/80 ring-1 ring-white/15 transition hover:bg-white/20" data-floor-img="Stard_2.jpg">Tầng 2</button>
                    <button class="sr-floor-tag rounded-full bg-white/10 px-2.5 py-1 text-white/80 ring-1 ring-white/15 transition hover:bg-white/20" data-floor-img="Stard_3.jpg">Tầng 3</button>
                </div>
                <div id="sr-floor" class="relative flex h-44 items-center justify-center overflow-hidden rounded-2xl bg-white/5 ring-1 ring-white/10">
                    <span id="sr-floor-ph" class="px-4 text-center text-xs text-white/40">Bấm một tầng để xem ảnh khung cảnh tầng đó.</span>
                    <img id="sr-floor-img" class="hidden h-full w-full object-cover" alt="">
                </div>
            </div>
        </div>
    </div>
    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 px-5 pb-5 pt-2 text-xs text-white/55">
        <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#CADCAC]"></span> Trống ·
        <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#E82C2A]"></span> Có khách ·
        <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#E0A800]"></span> Đặt trước ·
        <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#5B8DEF]"></span> Đang chọn
        <span class="w-full text-white/45">Kéo để xoay, cuộn để phóng to.</span>
    </p>
</section>

{{-- Danh mục --}}
<nav class="sticky top-16 z-30 -mx-4 mt-6 overflow-x-auto border-y border-[#522C25]/10 bg-[#FCFAFA]/95 px-4 py-3 backdrop-blur scrollbar-hide md:mx-0 md:rounded-full md:border md:px-3">
    <div class="flex w-max gap-2">
        <button @click="activeCategory = null"
                :class="activeCategory === null ? 'bg-[#E82C2A] text-white' : 'bg-white text-[#522C25] ring-1 ring-[#522C25]/10'"
                class="am-mono rounded-full px-5 py-2.5 text-sm transition">Tất cả</button>
        @foreach($danhMucs as $dm)
            @if($dm->mons->count() > 0)
            <button @click="activeCategory = '{{ $dm->ma_danh_muc }}'"
                    :class="activeCategory === '{{ $dm->ma_danh_muc }}' ? 'bg-[#E82C2A] text-white' : 'bg-white text-[#522C25] ring-1 ring-[#522C25]/10'"
                    class="am-mono whitespace-nowrap rounded-full px-5 py-2.5 text-sm transition">{{ $dm->ten_danh_muc }}</button>
            @endif
        @endforeach
    </div>
</nav>

<div class="mt-7 space-y-10">
@forelse($danhMucs as $dm)
    @if($dm->mons->count() > 0)
    <section x-show="activeCategory === null || activeCategory === '{{ $dm->ma_danh_muc }}'" x-transition.opacity class="scroll-mt-32">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <p class="am-mono text-xs uppercase tracking-[0.16em] text-[#522C25]/55">Danh mục</p>
                <h2 class="am-display mt-1 text-4xl leading-none text-[#1A1A1A]">{{ $dm->ten_danh_muc }}</h2>
            </div>
            <span class="rounded-full bg-[#F2F2F2] px-3 py-1 text-xs font-semibold text-[#522C25]/70">{{ $dm->mons->count() }} món</span>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($dm->mons as $mon)
                <x-menu-item-card :mon="$mon" :order="$maOrder ?? null" />
            @endforeach
        </div>
    </section>
    @endif
@empty
    <div class="rounded-3xl border border-dashed border-[#522C25]/20 bg-white py-16 text-center text-sm text-[#522C25]/60">Thực đơn đang được cập nhật.</div>
@endforelse
</div>

{{-- ── POPUP HƯỚNG DẪN DÙNG SƠ ĐỒ 3D ĐỂ CHỌN/ĐỔI BÀN ────────── --}}
<div x-data="{ open: false }" x-init="if(!localStorage.getItem('sr_guide_seen')){ setTimeout(() => open = true, 800) }">
    {{-- Nút trợ giúp nổi --}}
    <button @click="open = true"
            class="fixed bottom-5 right-5 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-[#1A1A1A] text-lg font-bold text-white shadow-lg ring-2 ring-white/70"
            title="Hướng dẫn dùng sơ đồ 3D">?</button>

    <div x-show="open" x-cloak x-transition.opacity @click.self="open=false"
         class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-sm rounded-[1.75rem] bg-white p-6 am-shadow" x-transition>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="am-display text-2xl text-[#1A1A1A]">Chọn bàn bằng sơ đồ 3D</h3>
                <button @click="open=false" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#F2F2F2] text-xl text-[#522C25]">&times;</button>
            </div>
            <ul class="space-y-3 text-sm leading-6 text-[#522C25]/80">
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#E82C2A] text-xs font-bold text-white">1</span> <span><strong>Kéo</strong> để xoay quán · <strong>cuộn</strong> (hoặc chạm 2 ngón) để phóng to.</span></li>
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#E82C2A] text-xs font-bold text-white">2</span> <span><strong>Chạm vào ghim bàn</strong> để xem số ghế, ảnh & trạng thái bàn.</span></li>
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#E82C2A] text-xs font-bold text-white">3</span> <span>Chọn một <strong>bàn trống</strong> để chuyển tới bàn đó.</span></li>
            </ul>
            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-[#522C25]/60">
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-[#CADCAC]"></span>Trống</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-[#E82C2A]"></span>Có khách</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-[#E0A800]"></span>Đặt trước</span>
            </div>
            <button @click="open=false; localStorage.setItem('sr_guide_seen','1')"
                    class="am-headline mt-5 w-full rounded-full bg-[#E82C2A] py-3 text-sm font-semibold text-white">Đã hiểu</button>
        </div>
    </div>
</div>
@endsection
