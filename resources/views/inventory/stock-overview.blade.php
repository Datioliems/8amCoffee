@extends('layouts.app')
@section('page-title', 'Tồn kho')

@section('content')
@php
    $healthyStock = max(0, $totalMaterials - $outOfStock - $lowStock);
@endphp

<div class="max-w-7xl space-y-6">
    <section class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-[#1A1A1A]">Tồn kho nguyên liệu</h2>
                <p class="mt-1 text-sm text-[#522C25]/60">Theo dõi số lượng, tìm kiếm và lọc trạng thái nguyên liệu ngay tại đây.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('inventory.materials.create') }}" class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">Thêm nguyên liệu</a>
                <a href="{{ route('inventory.import.create') }}" class="rounded-xl bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">Tạo phiếu nhập</a>
                <a href="{{ route('inventory.stockcheck.create') }}" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/15 transition hover:bg-[#FAF7F2]">Kiểm kê</a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#522C25]/45">Nguyên liệu</p>
            <p class="mt-3 text-3xl font-bold text-[#1A1A1A]">{{ $totalMaterials }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#522C25]/45">Đủ hàng</p>
            <p class="mt-3 text-3xl font-bold text-emerald-600">{{ $healthyStock }}</p>
        </div>
        <div class="rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-100">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Sắp hết</p>
            <p class="mt-3 text-3xl font-bold text-amber-700">{{ $lowStock }}</p>
        </div>
        <div class="rounded-2xl bg-red-50 p-5 ring-1 ring-red-100">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-red-500">Hết hàng</p>
            <p class="mt-3 text-3xl font-bold text-red-600">{{ $outOfStock }}</p>
        </div>
    </section>

    <section class="rounded-2xl bg-white ring-1 ring-[#522C25]/10">
        <div class="border-b border-[#522C25]/10 p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-[#1A1A1A]">Dữ liệu nguyên liệu</h3>
                    <p class="mt-1 text-sm text-[#522C25]/60">Hiển thị tối đa 10 nguyên liệu mỗi trang.</p>
                </div>
                <form method="GET" action="{{ route('inventory.index') }}" class="grid gap-2 sm:grid-cols-[minmax(220px,1fr)_160px_auto]">
                    <input type="search" name="q" value="{{ $keyword }}" placeholder="Tìm mã, tên hoặc đơn vị"
                           class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    <select name="stock_status" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                        <option value="">Tất cả trạng thái</option>
                        <option value="ok" {{ $stockStatus === 'ok' ? 'selected' : '' }}>Đủ hàng</option>
                        <option value="low" {{ $stockStatus === 'low' ? 'selected' : '' }}>Sắp hết</option>
                        <option value="out" {{ $stockStatus === 'out' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                    <button class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">Lọc</button>
                </form>
            </div>
            @if($keyword !== '' || $stockStatus)
                <a href="{{ route('inventory.index') }}" class="mt-3 inline-block text-sm font-semibold text-[#8B5A2B] hover:text-[#522C25]">Xóa bộ lọc</a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Mã NL</th>
                        <th class="px-5 py-3 text-left">Tên nguyên liệu</th>
                        <th class="px-5 py-3 text-left">Đơn vị</th>
                        <th class="px-5 py-3 text-right">Số lượng</th>
                        <th class="px-5 py-3 text-left">Mức tồn</th>
                        <th class="px-5 py-3 text-right">Ngưỡng cảnh báo</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($materials as $material)
                        @php
                            $stock = $material->tonKhos->first();
                            $ton = (float) ($stock?->sl_ton_kho_he_thong ?? 0);
                            $nguong = (float) ($stock?->nguong_canh_bao ?? 0);
                            $percent = $nguong > 0 ? min(100, round(($ton / $nguong) * 100)) : ($ton > 0 ? 100 : 0);
                            $status = $ton <= 0 ? 'Hết hàng' : ($nguong > 0 && $ton <= $nguong ? 'Sắp hết' : 'Đủ hàng');
                            $statusClass = $status === 'Hết hàng'
                                ? 'bg-red-50 text-red-600'
                                : ($status === 'Sắp hết' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700');
                            $barClass = $status === 'Hết hàng'
                                ? 'bg-red-500'
                                : ($status === 'Sắp hết' ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <tr class="hover:bg-[#FAF7F2]">
                            <td class="px-5 py-3 font-mono text-gray-500">{{ $material->ma_nl }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-900">{{ $material->ten_nl }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $material->don_vi }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-[#522C25]">{{ rtrim(rtrim(number_format($ton, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-20 rounded-full px-2 py-1 text-center text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ rtrim(rtrim(number_format($nguong, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if($status === 'Hết hàng')
                                        <a href="{{ route('inventory.import.create', ['nl' => $material->ma_nl]) }}"
                                           class="text-sm font-semibold text-emerald-600 hover:underline">Nhập</a>
                                    @endif
                                    <a href="{{ route('inventory.materials.edit', $material->ma_nl) }}"
                                       class="text-sm font-semibold text-amber-600 hover:underline">Sửa</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">Không có nguyên liệu phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-5 py-4">
            {{ $materials->links() }}
        </div>
    </section>
</div>
@endsection
