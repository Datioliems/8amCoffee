@props(['bans', 'orderCounts' => [], 'mode' => 'order'])
@php
    // mode = 'order'  → bấm bàn mở trang đặt món tại bàn (orders.table)
    // mode = 'anchor' → bấm bàn cuộn tới hàng cấu hình (#table-...) (trang Bàn & QR)
    $statusMeta = [
        'trong'     => ['label' => 'Trống',     'iso' => '#CADCAC'],
        'co_khach'  => ['label' => 'Có khách',  'iso' => '#E82C2A'],
        'dat_truoc' => ['label' => 'Đặt trước', 'iso' => '#80534a'],
        'dong'      => ['label' => 'Đóng',      'iso' => '#916f6b'],
    ];
    $oc = collect($orderCounts);
    $bansByFloor = $bans->groupBy(fn($b) => $b->vi_tri ?: 'Khác');
    $floorNames = $bansByFloor->keys()->values();
@endphp

<div class="rounded-[20px] border border-[#e6bdb8]/40 bg-[#fcf9f8] p-5"
     x-data="{ fi: 0, total: {{ max(1, $floorNames->count()) }} }">

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <span class="rounded-full border border-[#E82C2A]/20 bg-white/90 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-[#E82C2A]">
            Sơ đồ bàn
        </span>
        @if($floorNames->count() > 1)
        <div class="flex items-center gap-2">
            <button type="button" @click="fi = (fi - 1 + total) % total"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-lg font-bold text-[#522C25] ring-1 ring-[#522C25]/15 transition hover:bg-[#F2F2F2]">‹</button>
            <span class="min-w-[88px] text-center text-sm font-bold text-[#522C25]"
                  x-text="['{{ $floorNames->implode("','") }}'][fi]"></span>
            <button type="button" @click="fi = (fi + 1) % total"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-lg font-bold text-[#522C25] ring-1 ring-[#522C25]/15 transition hover:bg-[#F2F2F2]">›</button>
        </div>
        @endif
    </div>

    <div class="rounded-[16px] bg-white p-4 ring-1 ring-[#522C25]/10">
        @foreach($floorNames as $idx => $floor)
        @php $floorBans = $bansByFloor[$floor]; @endphp
        <div x-show="fi === {{ $idx }}" x-cloak>
            <p class="mb-3 text-sm font-bold text-[#522C25]/70">{{ $floor }} · {{ $floorBans->count() }} bàn</p>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                @foreach($floorBans as $ban)
                @php
                    $orders   = (int) ($oc[$ban->ma_ban] ?? 0);
                    $occupied = $ban->trang_thai === 'co_khach' || $orders > 0;
                    $meta     = $occupied ? $statusMeta['co_khach'] : ($statusMeta[$ban->trang_thai] ?? $statusMeta['trong']);
                    $href     = $mode === 'order' ? route('orders.table', $ban->ma_ban) : '#table-'.$ban->ma_ban;
                @endphp
                <a href="{{ $href }}"
                   class="relative flex flex-col items-center justify-center rounded-2xl border border-white/70 px-2 py-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                   style="background: {{ $meta['iso'] }};"
                   title="Bàn {{ $ban->so_ban }} · {{ $ban->so_ghe ?? 4 }} ghế · {{ $meta['label'] }}">
                    @if($orders > 0)
                    <span class="absolute right-1.5 top-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-white px-1 text-[11px] font-bold text-[#E82C2A]">{{ $orders }}</span>
                    @endif
                    <span class="text-lg font-bold">B{{ $ban->so_ban }}</span>
                    <span class="mt-0.5 text-[11px] font-medium opacity-95">{{ $ban->so_ghe ?? 4 }} ghế</span>
                    <span class="mt-1 rounded-full bg-white/25 px-2 py-0.5 text-[10px] font-semibold">{{ $meta['label'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach

        <p class="mt-4 text-xs text-[#522C25]/50">
            <span class="font-semibold text-[#E82C2A]">Đỏ</span> = có khách/đang order ·
            <span class="font-semibold text-[#52613B]">xanh</span> = trống ·
            <span class="font-semibold text-[#80534a]">nâu</span> = đặt trước.
            @if($mode === 'order') Bấm vào bàn để xem đơn / đặt món tại bàn. @endif
        </p>
    </div>
</div>
