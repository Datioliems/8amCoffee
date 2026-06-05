@extends('layouts.app')

@section('title', 'Phiếu kiểm kê')
@section('page-title', 'Phiếu kiểm kê tồn kho')

@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <a href="{{ route('inventory.index') }}"
           class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/10 transition hover:bg-[#FAF7F2]">
            Quay lại Tồn kho
        </a>
        <a href="{{ route('inventory.stockcheck.create') }}"
           class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
            + Tạo phiếu kiểm kê
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Mã phiếu</th>
                        <th class="px-4 py-3 text-left">Thời gian kiểm kê</th>
                        <th class="px-4 py-3 text-left">Người kiểm kê</th>
                        <th class="px-4 py-3 text-left">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($checks as $check)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-gray-500">{{ $check->ma_pkk }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $check->thoi_gian_kk ? \Carbon\Carbon::parse($check->thoi_gian_kk)->format('d/m/Y H:i') : $check->ngay_kk }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $check->nhanVien->ten_nv ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($check->trang_thai === 'nhap')
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Nháp</span>
                            @elseif($check->trang_thai === 'da_xac_nhan')
                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Đã xác nhận</span>
                            @else
                                <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Đã hủy</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('inventory.stockcheck.show', $check->ma_pkk) }}" class="mr-3 text-xs text-amber-600 hover:underline">
                                Chi tiết
                            </a>
                            @if($check->trang_thai === 'nhap')
                                <form action="{{ route('inventory.stockcheck.confirm', $check->ma_pkk) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            onclick="return confirm('Xác nhận kiểm kê? Tồn kho thực tế sẽ được cập nhật.')"
                                            class="text-xs text-green-600 hover:underline">
                                        Xác nhận
                                    </button>
                                </form>
                                <form action="{{ route('inventory.stockcheck.cancel', $check->ma_pkk) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            onclick="return confirm('Hủy phiếu kiểm kê {{ $check->ma_pkk }}?')"
                                            class="ml-3 text-xs text-red-500 hover:underline">
                                        Hủy
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">Chưa có phiếu kiểm kê nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">{{ $checks->links() }}</div>
    </div>
</div>
@endsection
