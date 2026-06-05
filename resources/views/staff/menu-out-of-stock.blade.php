@extends('layouts.app')

@section('title', 'Món hết nguyên liệu')
@section('page-title', 'Món hết nguyên liệu')

@section('content')
<div class="max-w-5xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('menu.index') }}"
           class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/10 transition hover:bg-[#FAF7F2]">
            Quay lại thực đơn
        </a>
        <span class="rounded-full bg-red-50 px-4 py-2 text-sm font-semibold text-red-600">
            {{ $mons->count() }} món cần xử lý
        </span>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <p class="text-sm text-gray-500">Các món dưới đây đang thiếu nguyên liệu theo tồn kho hiện tại. Nhân viên có thể ẩn món để khách không đặt thêm.</p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($mons as $mon)
                <div class="grid gap-4 p-4 md:grid-cols-[1fr_auto] md:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900">{{ $mon->ten_mon }}</p>
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500">{{ $mon->danhMuc?->ten_danh_muc }}</span>
                        </div>
                        <p class="mt-1 text-sm text-red-600">
                            Thiếu: {{ collect($mon->nguyen_lieu_het ?? [])->implode(', ') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('menu.destroy', $mon->ma_mon) }}">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-xl bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">
                            Ẩn món
                        </button>
                    </form>
                </div>
            @empty
                <div class="py-16 text-center text-sm text-gray-400">Không có món nào hết nguyên liệu.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
