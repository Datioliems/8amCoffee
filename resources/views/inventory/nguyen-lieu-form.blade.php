@extends('layouts.app')

@section('title', isset($nguyenLieu) ? 'Sửa nguyên liệu' : 'Thêm nguyên liệu')
@section('page-title', isset($nguyenLieu) ? 'Sửa nguyên liệu' : 'Thêm nguyên liệu')

@section('content')
<div class="max-w-lg mx-auto">
    <form method="POST" action="{{ isset($nguyenLieu)
        ? route('inventory.materials.update', $nguyenLieu->ma_nl)
        : route('inventory.materials.store') }}"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf
        @if(isset($nguyenLieu))
            @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mã nguyên liệu</label>
            <input type="text" value="{{ $nguyenLieu->ma_nl ?? ($nextMaNl ?? '') }}" readonly
                   class="w-full border border-gray-200 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-400">
            @if(!isset($nguyenLieu))
                <p class="mt-1 text-xs text-gray-400">Mã sẽ được hệ thống tự sinh khi lưu.</p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên nguyên liệu <span class="text-red-500">*</span></label>
            <input type="text" name="ten_nl" value="{{ old('ten_nl', $nguyenLieu->ten_nl ?? '') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            @error('ten_nl') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Đơn vị tính <span class="text-red-500">*</span></label>
            <select name="don_vi" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                @foreach(['gram','kg','ml','lit','cai','goi','hop','chai','tui'] as $donVi)
                <option value="{{ $donVi }}" {{ old('don_vi', $nguyenLieu->don_vi ?? '') === $donVi ? 'selected' : '' }}>
                    {{ $donVi }}
                </option>
                @endforeach
            </select>
            @error('don_vi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ngưỡng cảnh báo tồn kho</label>
            <div class="flex items-center gap-2">
                <input type="number" name="nguong_canh_bao" min="0" step="0.01"
                       value="{{ old('nguong_canh_bao', $nguongCanhBao ?? 0) }}"
                       class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                <span class="shrink-0 text-sm text-gray-400">{{ $nguyenLieu->don_vi ?? 'đơn vị' }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">Cảnh báo khi tồn kho ≤ ngưỡng này. Để 0 để tắt.</p>
            @error('nguong_canh_bao') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-1 border-t border-gray-100">
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                {{ isset($nguyenLieu) ? 'Cập nhật' : 'Thêm mới' }}
            </button>
            <a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-4 py-2">Hủy</a>
        </div>
    </form>
</div>
@endsection
