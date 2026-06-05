@extends('layouts.app')

@section('title', 'Báo cáo doanh thu')
@section('page-title', 'Báo cáo doanh thu')

@php
    $params = [
        'ky'           => $ky,
        'ma_chi_nhanh' => $branchFilter ?? '',
        'tu_ngay'      => $tuNgay,
        'den_ngay'     => $denNgay,
    ];
@endphp

@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-gray-500">
                Kỳ: <span class="font-semibold text-gray-700">{{ $kyLabel }}</span>
                ({{ \Carbon\Carbon::parse($tuNgay)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($denNgay)->format('d/m/Y') }})
                · <span class="font-semibold text-gray-700">{{ $branchLabel }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('report.print', $params) }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-medium text-white transition hover:bg-black">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                In báo cáo
            </a>
            <a href="{{ route('report.export', $params) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                Xuất CSV
            </a>
        </div>
    </div>

    <form method="GET" x-data="{ ky: '{{ $ky }}' }"
          class="mb-5 flex flex-wrap items-end gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div>
            <label class="mb-1 block text-xs text-gray-500">Kỳ báo cáo</label>
            <select name="ky" x-model="ky"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                <option value="hom_nay">Hôm nay</option>
                <option value="tuan_nay">Tuần này</option>
                <option value="thang_nay">Tháng này</option>
                <option value="quy_nay">Quý này</option>
                <option value="nam_nay">Năm này</option>
                <option value="tuy_chon">Tùy chọn (chủ động)</option>
            </select>
        </div>

        @if($isSuper)
        <div>
            <label class="mb-1 block text-xs text-gray-500">Chi nhánh</label>
            <select name="ma_chi_nhanh"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                <option value="">Tất cả chi nhánh</option>
                @foreach($branches as $b)
                <option value="{{ $b->ma_chi_nhanh }}" {{ $branchFilter === $b->ma_chi_nhanh ? 'selected' : '' }}>{{ $b->ten_chi_nhanh }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div x-show="ky === 'tuy_chon'" x-cloak>
            <label class="mb-1 block text-xs text-gray-500">Từ ngày</label>
            <input type="date" name="tu_ngay" value="{{ $tuNgay }}"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
        </div>
        <div x-show="ky === 'tuy_chon'" x-cloak>
            <label class="mb-1 block text-xs text-gray-500">Đến ngày</label>
            <input type="date" name="den_ngay" value="{{ $denNgay }}"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
        </div>

        <button type="submit" class="rounded-lg bg-amber-500 px-4 py-1.5 text-sm text-white transition hover:bg-amber-600">
            Lọc
        </button>
    </form>

    <div class="mb-5 grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
            <p class="mb-1 text-xs text-amber-600">Tổng doanh thu</p>
            <p class="text-2xl font-bold text-amber-700">{{ number_format($tongKet['tong_doanh_thu'], 0, ',', '.') }}đ</p>
        </div>
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <p class="mb-1 text-xs text-blue-600">Số hóa đơn</p>
            <p class="text-2xl font-bold text-blue-700">{{ $tongKet['tong_hoa_don'] }}</p>
        </div>
    </div>

    @if($doanhThuTheoChiNhanh->isNotEmpty())
    <div class="mb-5 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-3 text-sm font-medium text-gray-700">Doanh thu theo chi nhánh</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-4 py-2 text-left">Chi nhánh</th>
                    <th class="px-4 py-2 text-right">Số HD</th>
                    <th class="px-4 py-2 text-right">Doanh thu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($doanhThuTheoChiNhanh as $row)
                <tr>
                    <td class="px-4 py-2 text-gray-700">{{ $row->ten_chi_nhanh ?? $row->ma_chi_nhanh }}</td>
                    <td class="px-4 py-2 text-right text-gray-500">{{ $row->so_hoa_don }}</td>
                    <td class="px-4 py-2 text-right font-medium text-gray-800">{{ number_format($row->tong_doanh_thu, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3 text-sm font-medium text-gray-700">Doanh thu theo ngày</div>
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
                        <td class="px-4 py-2 text-gray-700">{{ \Carbon\Carbon::parse($row->ngay)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right text-gray-500">{{ $row->so_hoa_don }}</td>
                        <td class="px-4 py-2 text-right font-medium text-gray-800">{{ number_format($row->tong_doanh_thu, 0, ',', '.') }}đ</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3 text-sm font-medium text-gray-700">Top 5 món bán chạy</div>
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
                        <td class="px-4 py-2 font-mono text-gray-400">#{{ $idx + 1 }}</td>
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $mon->ten_mon }}</td>
                        <td class="px-4 py-2 text-right font-bold text-amber-600">{{ $mon->tong_sl }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Khi đổi kỳ (khác Tùy chọn) thì nộp form ngay cho tiện.
    document.querySelector('select[name="ky"]').addEventListener('change', function () {
        if (this.value !== 'tuy_chon') this.form.submit();
    });
</script>
@endsection
