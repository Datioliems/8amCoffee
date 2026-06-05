@extends('layouts.app')
@section('page-title', 'Phiếu nhập kho')

@section('content')
<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('inventory.index') }}"
       class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/10 transition hover:bg-[#FAF7F2]">
        Quay lại Tồn kho
    </a>
    <a href="{{ route('inventory.import.create') }}"
       class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
        + Tạo phiếu nhập
    </a>
</div>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Mã phiếu</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Ngày nhập</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Nhà cung cấp</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400">Tổng giá trị</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400">Trạng thái</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($imports as $import)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $import->ma_pnk }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $import->ngay_nk }}</td>
                    <td class="px-4 py-3">{{ $import->nhaCungCap->ten_ncc }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($import->tong_gia_tri, 0, ',', '.') }}đ</td>
                    <td class="px-4 py-3 text-center">
                        @if($import->trang_thai === 'cho_duyet')
                            <span class="rounded-full bg-yellow-50 px-2 py-0.5 text-xs text-yellow-700">Chờ duyệt</span>
                        @elseif($import->trang_thai === 'da_duyet')
                            <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">Đã duyệt</span>
                        @else
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs text-red-600">Đã hủy</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('inventory.import.show', $import->ma_pnk) }}"
                               class="rounded-lg bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-800">
                                Chi tiết
                            </a>

                            @if($import->trang_thai === 'cho_duyet')
                                <form action="{{ route('inventory.import.approve', $import->ma_pnk) }}" method="POST"
                                      onsubmit="return confirm('Duyệt phiếu nhập {{ $import->ma_pnk }}? Tồn kho sẽ được cập nhật.')">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700">
                                        Duyệt
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">Chưa có phiếu nhập nào</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $imports->links() }}</div>
@endsection
