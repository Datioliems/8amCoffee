@extends('layouts.app')
@section('title', 'Thực đơn - 8AM Coffee')
@section('page-title', 'Quản lý thực đơn')

@section('content')
<div class="max-w-6xl">
    @if($stockWarnings->isNotEmpty())
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-amber-800">Có món đang hết nguyên liệu</p>
                    <p class="mt-1 text-sm text-amber-700">Có {{ $stockWarnings->count() }} món cần kiểm tra và có thể ẩn khỏi thực đơn khách hàng.</p>
                </div>
                <a href="{{ route('menu.out-of-stock') }}"
                   class="inline-flex rounded-xl bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">
                    Xem danh sách
                </a>
            </div>
        </div>
    @endif

    <div class="mb-5 space-y-3">
        <form method="GET" action="{{ route('menu.index') }}" class="flex flex-col gap-2 rounded-xl border border-gray-100 bg-white p-3 shadow-sm sm:flex-row">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm món theo tên, mã hoặc mô tả"
                   class="min-w-0 flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            <button class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">Tìm kiếm</button>
            @if(request('q'))
                <a href="{{ route('menu.index', request('category') ? ['category' => request('category')] : []) }}" class="rounded-lg px-4 py-2 text-center text-sm font-semibold text-gray-500 hover:text-gray-700">Xóa lọc</a>
            @endif
        </form>

        <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2 overflow-x-auto">
            <a href="{{ route('menu.index') }}"
               class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium
                      {{ !request('category') ? 'bg-amber-100 text-amber-800' : 'border border-gray-200 bg-white text-gray-600 hover:border-amber-300' }}">
                Tất cả ({{ $mons->total() }})
            </a>
            @foreach($danhMucs as $dm)
            <a href="{{ route('menu.index', array_filter(['category' => $dm->ma_danh_muc, 'q' => request('q')])) }}"
               class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium
                      {{ request('category') === $dm->ma_danh_muc ? 'bg-amber-100 text-amber-800' : 'border border-gray-200 bg-white text-gray-600 hover:border-amber-300' }}">
                {{ $dm->ten_danh_muc }}
            </a>
            @endforeach
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('menu.toppings.index') }}"
               class="flex items-center gap-1.5 rounded-xl bg-[#1A1A1A] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#522C25]">
                Quản lý topping
            </a>
            <a href="{{ route('menu.create') }}"
               class="flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-medium text-white transition hover:bg-amber-600">
                + Thêm món
            </a>
        </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        @foreach($mons as $mon)
        @php
            $imgUrl = $mon->image_url;
            $sCls = match($mon->trang_thai) {
                'active' => 'bg-green-100 text-green-700',
                'het_hang' => 'bg-yellow-100 text-yellow-700',
                default => 'bg-gray-100 text-gray-500',
            };
            $sLabel = match($mon->trang_thai) {
                'active' => 'Đang bán',
                'het_hang' => 'Hết hàng',
                default => 'Ẩn',
            };
        @endphp

        <div class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:border-amber-300 hover:shadow-sm {{ $mon->trang_thai === 'an' ? 'opacity-60' : '' }}">
            <div class="relative aspect-square overflow-hidden bg-stone-100">
                @if($imgUrl)
                    <img src="{{ $imgUrl }}" alt="{{ $mon->ten_mon }}" loading="lazy"
                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                         onerror="this.parentNode.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-stone-100 text-stone-300\'><svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z\' clip-rule=\'evenodd\'/></svg></div>'">
                @else
                    <div class="flex h-full w-full items-center justify-center bg-stone-100 text-stone-300">
                        <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @endif
                <span class="absolute right-1.5 top-1.5 rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $sCls }}">
                    {{ $sLabel }}
                </span>
            </div>

            <div class="flex flex-1 flex-col p-3">
                <p class="mb-0.5 text-[10px] uppercase tracking-wider text-gray-400">{{ $mon->danhMuc?->ten_danh_muc }}</p>
                <p class="line-clamp-1 text-sm font-semibold text-gray-800">{{ $mon->ten_mon }}</p>
                @if($mon->mo_ta)
                    <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-gray-400">{{ $mon->mo_ta }}</p>
                @endif
                <p class="mt-1.5 text-sm font-bold text-amber-600">{{ number_format($mon->don_gia, 0, ',', '.') }}đ</p>

                <div class="mt-auto grid grid-cols-2 gap-1.5 border-t border-gray-100 pt-2.5">
                    <a href="{{ route('menu.edit', $mon->ma_mon) }}"
                       class="block rounded-lg bg-gray-50 py-1.5 text-center text-xs font-medium text-gray-600 transition hover:bg-amber-50 hover:text-amber-700">
                        Sửa
                    </a>
                    @if($mon->trang_thai === 'an')
                        <form method="POST" action="{{ route('menu.restore', $mon->ma_mon) }}"
                              onsubmit="return confirm('Bỏ ẩn món {{ addslashes($mon->ten_mon) }}?')">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="w-full rounded-lg bg-green-50 py-1.5 text-xs font-medium text-green-700 transition hover:bg-green-100">
                                Bỏ ẩn
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('menu.destroy', $mon->ma_mon) }}"
                              onsubmit="return confirm('Ẩn món {{ addslashes($mon->ten_mon) }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-lg bg-gray-50 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-red-50 hover:text-red-600">
                                Ẩn
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($mons->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-white py-16 text-center">
        <p class="text-sm text-gray-500">Chưa có món nào.</p>
        <a href="{{ route('menu.create') }}" class="mt-2 inline-block text-sm text-amber-600 hover:underline">Thêm món đầu tiên</a>
    </div>
    @endif

    <div class="mt-4">{{ $mons->withQueryString()->links() }}</div>
</div>
@endsection
