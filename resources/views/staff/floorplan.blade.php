@extends('layouts.app')

@section('title', 'Sơ đồ bàn 3D - 8AM Coffee')
@section('page-title', 'Sơ đồ bàn 3D (Live)')

@section('content')
<div id="floorplan-root"
     data-model-url="{{ \App\Support\Cdn::url('models/'.$model3d) }}"
     data-tables-url="{{ route('floorplan.tables') }}">

    {{-- Thanh điều khiển: nút tầng sinh động theo chi nhánh --}}
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <span class="mr-1 text-xs font-semibold text-gray-500">{{ $chiNhanh->ten_chi_nhanh ?? 'Chi nhánh' }}</span>
        <button data-floor="all" class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-sm">Tất cả</button>
        @foreach($floors as $floor)
        <button data-floor="{{ $floor }}" class="px-3 py-1.5 rounded-lg bg-white border text-sm">{{ $floor }}</button>
        @endforeach

        <div class="ml-auto flex items-center gap-3 text-xs text-gray-600">
            <span class="flex items-center gap-1"><i class="inline-block w-3 h-3 rounded-full" style="background:#22c55e"></i> Trống</span>
            <span class="flex items-center gap-1"><i class="inline-block w-3 h-3 rounded-full" style="background:#ef4444"></i> Có khách</span>
            <span class="flex items-center gap-1"><i class="inline-block w-3 h-3 rounded-full" style="background:#f59e0b"></i> Đặt trước</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div id="fp-canvas" class="lg:col-span-3 rounded-2xl bg-white border border-gray-200 overflow-hidden"
             style="height:72vh"></div>
        <div id="fp-info" class="rounded-2xl bg-white border border-gray-200 p-4 text-sm text-gray-400">
            Chọn một bàn để xem chi tiết…
        </div>
    </div>
</div>

@vite('resources/js/floorplan.js')
@endsection
