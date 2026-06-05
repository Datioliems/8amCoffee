@extends('layouts.app')

@section('title', isset($nhaCungCap) ? 'Sửa NCC' : 'Thêm NCC')
@section('page-title', isset($nhaCungCap) ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp')

@section('content')
<div class="max-w-lg mx-auto">
    <form method="POST" action="{{ isset($nhaCungCap)
        ? route('inventory.supplier.update', $nhaCungCap->ma_ncc)
        : route('inventory.supplier.store') }}"
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
        @csrf
        @if(isset($nhaCungCap))
            @method('PUT')
        @endif

        @if(!isset($nhaCungCap))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mã NCC</label>
            <input type="text" name="ma_ncc" value="{{ old('ma_ncc') }}" placeholder="VD: NCC001"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            @error('ma_ncc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên nhà cung cấp</label>
            <input type="text" name="ten_ncc" value="{{ old('ten_ncc', $nhaCungCap->ten_ncc ?? '') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            @error('ten_ncc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
            <input type="text" name="sdt" value="{{ old('sdt', $nhaCungCap->sdt ?? '') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            @error('sdt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
            <textarea name="dia_chi" rows="2"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">{{ old('dia_chi', $nhaCungCap->dia_chi ?? '') }}</textarea>
            @error('dia_chi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $nhaCungCap->email ?? '') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                {{ isset($nhaCungCap) ? 'Cập nhật' : 'Thêm mới' }}
            </button>
            <a href="{{ route('inventory.supplier.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-5 py-2">Hủy</a>
        </div>
    </form>
</div>
@endsection
