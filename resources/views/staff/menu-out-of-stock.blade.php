@extends('layouts.app')

@section('title', 'Tồn kho & Ẩn món')
@section('page-title', 'Tồn kho & Ẩn món')

@section('content')
<div class="max-w-5xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('menu.index') }}"
           class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/10 transition hover:bg-[#FAF7F2]">
            Quay lại thực đơn
        </a>
        @if($autoHidden->count() + $activeLow->count() > 0)
            <span class="rounded-full bg-red-50 px-4 py-2 text-sm font-semibold text-red-600">
                {{ $autoHidden->count() + $activeLow->count() }} món cần xử lý
            </span>
        @else
            <span class="rounded-full bg-green-50 px-4 py-2 text-sm font-semibold text-green-700">Tồn kho ổn định</span>
        @endif
    </div>

    {{-- ── Đã tự động ẩn ──────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-amber-50 px-5 py-4">
            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white">
                {{ $autoHidden->count() }}
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Đã tự động ẩn</p>
                <p class="text-xs text-gray-500">
                    Scheduler tự ẩn khi tổng tồn kho nguyên liệu < định mức.
                    Nhấn <strong>Hiện lại</strong> để bỏ qua kiểm tra tồn kho (món vẫn hiển thị nhưng không thể đặt nếu kho vẫn thiếu).
                </p>
            </div>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($autoHidden as $mon)
                <div class="grid gap-4 p-4 md:grid-cols-[1fr_auto] md:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900">{{ $mon->ten_mon }}</p>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                {{ $mon->danhMuc?->ten_danh_muc }}
                            </span>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                Tự động ẩn
                            </span>
                        </div>
                        @if(!empty($mon->nguyen_lieu_het))
                            <p class="mt-1 text-sm text-red-600">
                                Thiếu: {{ collect($mon->nguyen_lieu_het)->implode(', ') }}
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('menu.restore', $mon->ma_mon) }}">
                        @csrf
                        @method('PUT')
                        <button type="submit"
                                class="rounded-xl border border-[#522C25]/20 bg-white px-4 py-2 text-sm font-semibold text-[#522C25] transition hover:bg-[#FAF7F2]">
                            Hiện lại
                        </button>
                    </form>
                </div>
            @empty
                <div class="py-10 text-center text-sm text-gray-400">Không có món nào bị tự động ẩn.</div>
            @endforelse
        </div>
    </div>

    {{-- ── Đang bán nhưng thiếu nguyên liệu ──────────────────────────────── --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-red-50 px-5 py-4">
            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                {{ $activeLow->count() }}
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Đang bán nhưng thiếu nguyên liệu</p>
                <p class="text-xs text-gray-500">
                    Các món đang hiển thị trên thực đơn nhưng kho tại chi nhánh hiện tại không đủ.
                    Scheduler sẽ tự ẩn trong ≤ 5 phút, hoặc nhấn <strong>Ẩn ngay</strong>.
                </p>
            </div>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($activeLow as $mon)
                <div class="grid gap-4 p-4 md:grid-cols-[1fr_auto] md:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900">{{ $mon->ten_mon }}</p>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                {{ $mon->danhMuc?->ten_danh_muc }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-red-600">
                            Thiếu: {{ collect($mon->nguyen_lieu_het ?? [])->implode(', ') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('menu.destroy', $mon->ma_mon) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-xl bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">
                            Ẩn ngay
                        </button>
                    </form>
                </div>
            @empty
                <div class="py-10 text-center text-sm text-gray-400">Không có món nào đang thiếu nguyên liệu.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
