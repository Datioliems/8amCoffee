@extends('layouts.app')

@section('title', 'Nhà cung cấp')
@section('page-title', 'Nhà cung cấp')

@section('content')
<div class="max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <div></div>
        <a href="{{ route('inventory.supplier.create') }}"
           class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + Thêm NCC
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Mã NCC</th>
                    <th class="px-4 py-3 text-left">Tên nhà cung cấp</th>
                    <th class="px-4 py-3 text-left">SĐT</th>
                    <th class="px-4 py-3 text-left">Địa chỉ</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($nhaCungCaps as $ncc)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-gray-500">{{ $ncc->ma_ncc }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $ncc->ten_ncc }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $ncc->sdt ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 truncate max-w-[200px]">{{ $ncc->dia_chi ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('inventory.supplier.edit', $ncc->ma_ncc) }}"
                           class="text-blue-500 hover:underline mr-3">Sửa</a>
                        <form action="{{ route('inventory.supplier.destroy', $ncc->ma_ncc) }}"
                              method="POST" class="inline"
                              onsubmit="return confirm('Xóa nhà cung cấp này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:underline">Xóa</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Chưa có nhà cung cấp nào.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">{{ $nhaCungCaps->links() }}</div>
    </div>
</div>
@endsection
