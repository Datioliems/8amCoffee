@extends('layouts.app')

@section('title', 'Báo cáo doanh thu')
@section('page-title', 'Báo cáo doanh thu')

@section('content')
<div class="max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <div></div>
        <a href="{{ route('inventory.report.export', ['tu_ngay' => $tuNgay, 'den_ngay' => $denNgay]) }}"
           class="bg-green-500 hover:bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            Xuất CSV
        </a>
    </div>

    <form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Từ ngày</label>
            <input type="date" name="tu_ngay" value="{{ $tuNgay }}"
                   class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Đến ngày</label>
            <input type="date" name="den_ngay" value="{{ $denNgay }}"
                   class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
        </div>
        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm px-4 py-1.5 rounded-lg transition">
            Lọc
        </button>
    </form>

    <div class="grid grid-cols-2 gap-4 mb-5">
        <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
            <p class="text-xs text-amber-600 mb-1">Tổng doanh thu</p>
            <p class="text-2xl font-bold text-amber-700">{{ number_format($tongKet['tong_doanh_thu'], 0, ',', '.') }}đ</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
            <p class="text-xs text-blue-600 mb-1">Số hóa đơn</p>
            <p class="text-2xl font-bold text-blue-700">{{ $tongKet['tong_hoa_don'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 font-medium text-gray-700 text-sm">Doanh thu theo ngày</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Ngày</th>
                        <th class="px-4 py-2 text-right">Số HD</th>
                        <th class="px-4 py-2 text-right">Doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($doanhThuTheoNgay as $row)
                    <tr>
                        <td class="px-4 py-2 text-gray-700">{{ $row->ngay }}</td>
                        <td class="px-4 py-2 text-right text-gray-500">{{ $row->so_hoa_don }}</td>
                        <td class="px-4 py-2 text-right font-medium text-gray-800">{{ number_format($row->tong_doanh_thu, 0, ',', '.') }}đ</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 font-medium text-gray-700 text-sm">Top 5 món bán chạy</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Hạng</th>
                        <th class="px-4 py-2 text-left">Tên món</th>
                        <th class="px-4 py-2 text-right">Số lượng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($topMon as $idx => $mon)
                    <tr>
                        <td class="px-4 py-2 text-gray-400 font-mono">#{{ $idx + 1 }}</td>
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $mon->ten_mon }}</td>
                        <td class="px-4 py-2 text-right text-amber-600 font-bold">{{ $mon->tong_sl }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
