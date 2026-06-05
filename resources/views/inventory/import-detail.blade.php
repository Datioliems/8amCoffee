@extends('layouts.app')

@section('title', 'Chi tiết phiếu nhập kho')
@section('page-title', 'Chi tiết phiếu nhập kho')

@section('content')
@php
    $statusMeta = [
        'cho_duyet' => ['label' => 'Chờ duyệt', 'class' => 'bg-amber-100 text-amber-700'],
        'da_duyet' => ['label' => 'Đã duyệt', 'class' => 'bg-green-100 text-green-700'],
        'da_huy' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-700'],
    ];
    $meta = $statusMeta[$import->trang_thai] ?? ['label' => $import->trang_thai, 'class' => 'bg-gray-100 text-gray-600'];
@endphp

<div class="max-w-4xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/10 transition hover:bg-[#FAF7F2]">
                Quay lại Tồn kho
            </a>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $meta['class'] }}">{{ $meta['label'] }}</span>
            <a href="{{ route('invoice.import', $import->ma_pnk) }}" target="_blank"
               class="ml-2 rounded-xl bg-[#8B5A2B] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6F4621]">
                In hóa đơn nhập
            </a>
        </div>

        @if($import->trang_thai === 'cho_duyet')
            <div class="flex gap-2">
                <form action="{{ route('inventory.import.approve', $import->ma_pnk) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                            onclick="return confirm('Duyệt phiếu nhập {{ $import->ma_pnk }}? Tồn kho sẽ được cập nhật.')"
                            class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                        Duyệt phiếu
                    </button>
                </form>
                <form action="{{ route('inventory.import.cancel', $import->ma_pnk) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                            onclick="return confirm('Hủy phiếu nhập {{ $import->ma_pnk }}?')"
                            class="rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                        Hủy phiếu
                    </button>
                </form>
            </div>
        @endif
    </div>

    <section class="rounded-2xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
        <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-gray-400">Mã phiếu</p>
                <p class="font-mono font-semibold text-gray-900">{{ $import->ma_pnk }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-gray-400">Nhà cung cấp</p>
                <p class="font-semibold text-gray-900">{{ $import->nhaCungCap->ten_ncc ?? 'Chưa rõ' }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-gray-400">Ngày nhập</p>
                <p class="font-semibold text-gray-900">{{ $import->ngay_nk }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-gray-400">Tổng giá trị</p>
                <p class="font-bold text-amber-600">{{ number_format($import->tong_gia_tri ?? 0, 0, ',', '.') }}đ</p>
            </div>
        </div>

        @if($import->ghi_chu)
            <div class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-600">
                <span class="font-semibold text-gray-800">Ghi chú:</span> {{ $import->ghi_chu }}
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Danh sách nguyên liệu</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Nguyên liệu</th>
                        <th class="px-5 py-3 text-right">Số lượng</th>
                        <th class="px-5 py-3 text-right">Đơn giá</th>
                        <th class="px-5 py-3 text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($import->chiTietNhapKhos as $ct)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $ct->nguyenLieu->ten_nl ?? $ct->ma_nl }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ rtrim(rtrim(number_format($ct->so_luong, 2, ',', '.'), '0'), ',') }} {{ $ct->nguyenLieu->don_vi ?? '' }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ number_format($ct->don_gia, 0, ',', '.') }}đ</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ number_format($ct->so_luong * $ct->don_gia, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
