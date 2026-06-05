@extends('layouts.app')

@section('title', 'Tạo phiếu kiểm kê')
@section('page-title', 'Tạo phiếu kiểm kê tồn kho')

@section('content')
<div class="max-w-3xl mx-auto">
    <form method="POST" action="{{ route('inventory.stockcheck.store') }}" class="space-y-5" x-data="{ rows: [{}] }">
        @csrf

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
            <input type="text" name="ghi_chu" placeholder="Tùy chọn"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            @error('ghi_chu') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-medium text-gray-700">Số lượng thực tế</h2>
                <button type="button" @click="rows.push({})"
                        class="text-sm bg-amber-50 text-amber-600 hover:bg-amber-100 px-3 py-1 rounded-lg transition">
                    + Thêm dòng
                </button>
            </div>

            <table class="w-full text-sm">
                <thead class="text-gray-500 text-xs border-b border-gray-100">
                    <tr>
                        <th class="pb-2 text-left">Nguyên liệu</th>
                        <th class="pb-2 text-right">SL thực tế</th>
                        <th class="pb-2 w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, index) in rows" :key="index">
                        <tr class="border-b border-gray-50">
                            <td class="py-2 pr-2">
                                <select :name="`items[${index}][ma_nl]`" required
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-300">
                                    <option value="">-- Chọn --</option>
                                    @foreach($nguyenLieus as $nl)
                                    <option value="{{ $nl->ma_nl }}">{{ $nl->ten_nl }} ({{ $nl->don_vi }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 pr-2">
                                <input type="number" :name="`items[${index}][sl_thuc_te]`"
                                       min="0" step="0.01" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-amber-300">
                            </td>
                            <td class="py-2 text-center">
                                <button type="button" @click="if (rows.length > 1) rows.splice(index, 1)"
                                        class="text-red-400 hover:text-red-600 text-lg">×</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                Tạo phiếu kiểm kê
            </button>
            <a href="{{ route('inventory.stockcheck.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-5 py-2">Hủy</a>
        </div>
    </form>
</div>
@endsection
