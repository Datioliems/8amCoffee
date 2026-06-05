@extends('layouts.app')

@section('title', 'Chi tiết phiếu kiểm kê')
@section('page-title', 'Chi tiết phiếu kiểm kê')

@section('content')
@php
    $statusMeta = [
        'nhap' => ['label' => 'Nháp', 'class' => 'bg-gray-100 text-gray-600'],
        'da_xac_nhan' => ['label' => 'Đã xác nhận', 'class' => 'bg-green-100 text-green-700'],
        'da_huy' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-700'],
    ];
    $meta = $statusMeta[$check->trang_thai] ?? ['label' => $check->trang_thai, 'class' => 'bg-gray-100 text-gray-600'];
@endphp

<div class="max-w-4xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/10 transition hover:bg-[#FAF7F2]">
                Quay lại Tồn kho
            </a>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $meta['class'] }}">{{ $meta['label'] }}</span>
        </div>

        @if($check->trang_thai === 'nhap')
            <div class="flex gap-2">
                <form action="{{ route('inventory.stockcheck.confirm', $check->ma_pkk) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                            onclick="return confirm('Xác nhận kiểm kê? Tồn kho thực tế sẽ được cập nhật.')"
                            class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                        Xác nhận kiểm kê
                    </button>
                </form>
                <form action="{{ route('inventory.stockcheck.cancel', $check->ma_pkk) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                            onclick="return confirm('Hủy phiếu kiểm kê {{ $check->ma_pkk }}?')"
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
                <p class="font-mono font-semibold text-gray-900">{{ $check->ma_pkk }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-gray-400">Thời gian kiểm kê</p>
                <p class="font-semibold text-gray-900">{{ $check->thoi_gian_kk ? \Carbon\Carbon::parse($check->thoi_gian_kk)->format('d/m/Y H:i:s') : $check->ngay_kk }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-gray-400">Người kiểm kê</p>
                <p class="font-semibold text-gray-900">{{ $check->nhanVien->ten_nv ?? 'Chưa rõ' }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-gray-400">Kho / chi nhánh</p>
                <p class="font-semibold text-gray-900">{{ $check->chiNhanh->ten_chi_nhanh ?? $check->ma_chi_nhanh }}</p>
                <p class="text-xs text-gray-400">{{ $check->chiTietKiemKes->count() }} nguyên liệu</p>
            </div>
        </div>

        @if($check->ghi_chu)
            <div class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-600">
                <span class="font-semibold text-gray-800">Ghi chú:</span> {{ $check->ghi_chu }}
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Danh sách kiểm kê</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Nguyên liệu</th>
                        <th class="px-5 py-3 text-right">SL hệ thống</th>
                        <th class="px-5 py-3 text-right">SL thực tế</th>
                        <th class="px-5 py-3 text-right">Chênh lệch</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($check->chiTietKiemKes as $ct)
                        @php
                            $diff = (float) $ct->sl_thuc_te - (float) $ct->sl_he_thong;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $ct->nguyenLieu->ten_nl ?? $ct->ma_nl }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ rtrim(rtrim(number_format($ct->sl_he_thong, 2, ',', '.'), '0'), ',') }} {{ $ct->nguyenLieu->don_vi ?? '' }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ rtrim(rtrim(number_format($ct->sl_thuc_te, 2, ',', '.'), '0'), ',') }} {{ $ct->nguyenLieu->don_vi ?? '' }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $diff < 0 ? 'text-red-600' : ($diff > 0 ? 'text-green-600' : 'text-gray-500') }}">
                                {{ $diff > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($diff, 2, ',', '.'), '0'), ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
