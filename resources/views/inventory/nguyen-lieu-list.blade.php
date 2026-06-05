@extends('layouts.app')

@section('title', 'Các nguyên liệu')
@section('page-title', 'Các nguyên liệu')

@section('content')
@php
    // IDs nguyên liệu đang được tham chiếu → không thể xóa
    $protectedIds = collect(
        \Illuminate\Support\Facades\DB::select("
            SELECT ma_nl FROM DINH_MUC
            UNION SELECT ma_nl FROM CHI_TIET_NHAP_KHO
            UNION SELECT ma_nl FROM CHI_TIET_KIEM_KE
        ")
    )->pluck('ma_nl')->flip()->all();
@endphp
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('inventory.index') }}"
               class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:border-amber-300 hover:text-amber-700">
                Quay lại
            </a>
            <a href="{{ route('inventory.materials.create') }}"
               class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                + Thêm nguyên liệu
            </a>
        </div>

        <form method="GET" action="{{ route('inventory.materials.index') }}" class="flex flex-col gap-2 rounded-xl border border-gray-100 bg-white p-4 shadow-sm sm:flex-row">
            <input type="search" name="q" value="{{ $keyword }}"
                   placeholder="Tìm theo mã, tên nguyên liệu hoặc đơn vị"
                   class="min-w-0 flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            <button type="submit" class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#522C25]">
                Tìm kiếm
            </button>
            @if($keyword !== '')
                <a href="{{ route('inventory.materials.index') }}" class="rounded-lg px-4 py-2 text-center text-sm font-medium text-gray-500 hover:text-gray-700">
                    Xóa lọc
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Mã NL</th>
                    <th class="px-4 py-3 text-left">Tên nguyên liệu</th>
                    <th class="px-4 py-3 text-left">Đơn vị</th>
                    <th class="px-4 py-3 text-left">Số lượng</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($nguyenLieus as $nl)
                @php
                    $tonKho = $nl->tonKhos->first();
                    $ton = (float) ($tonKho?->sl_ton_kho_he_thong ?? 0);
                    $nguong = (float) ($tonKho?->nguong_canh_bao ?? 0);
                    $percent = $nguong > 0 ? min(100, round(($ton / $nguong) * 100)) : ($ton > 0 ? 100 : 0);
                    $status = $ton <= 0 ? 'Hết hàng' : ($nguong > 0 && $ton <= $nguong ? 'Sắp hết' : 'Đủ hàng');
                    $statusClass = $status === 'Hết hàng'
                        ? 'bg-red-50 text-red-600'
                        : ($status === 'Sắp hết' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700');
                    $barClass = $status === 'Hết hàng'
                        ? 'bg-red-500'
                        : ($status === 'Sắp hết' ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-gray-500">{{ $nl->ma_nl }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $nl->ten_nl }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $nl->don_vi }}</td>
                    <td class="px-4 py-3">
                        <div class="min-w-44">
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <span class="font-semibold text-gray-800">
                                    {{ rtrim(rtrim(number_format($ton, 2, ',', '.'), '0'), ',') }} {{ $nl->don_vi }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $percent }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">
                                Ngưỡng cảnh báo: {{ rtrim(rtrim(number_format($nguong, 2, ',', '.'), '0'), ',') }} {{ $nl->don_vi }}
                            </p>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('inventory.materials.edit', $nl->ma_nl) }}"
                           class="text-blue-500 hover:underline mr-3">Sửa</a>
                        @if(isset($protectedIds[$nl->ma_nl]))
                            <span class="text-gray-300 cursor-not-allowed" title="Đang được dùng trong công thức / phiếu nhập / kiểm kê">Xóa</span>
                        @else
                        <form action="{{ route('inventory.materials.destroy', $nl->ma_nl) }}"
                              method="POST" class="inline"
                              onsubmit="return confirm('Xóa nguyên liệu này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:underline">Xóa</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                        {{ $keyword !== '' ? 'Không tìm thấy nguyên liệu phù hợp.' : 'Chưa có nguyên liệu nào.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">{{ $nguyenLieus->links() }}</div>
    </div>
</div>
@endsection
