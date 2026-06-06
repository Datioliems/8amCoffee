@extends('layouts.app')
@section('title', 'Nhật ký hành động')
@section('page-title', 'Nhật ký hành động nhân viên')

@section('content')
<div class="max-w-7xl space-y-5">

    {{-- ── Thống kê hôm nay ── --}}
    <section class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#522C25]/45">Hành động hôm nay</p>
            <p class="mt-3 text-3xl font-bold text-[#1A1A1A]">{{ $hanhDongHomNay }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#522C25]/45">Nhân viên hoạt động hôm nay</p>
            <p class="mt-3 text-3xl font-bold text-[#1A1A1A]">{{ $nhanVienHoatDong }}</p>
        </div>
    </section>

    {{-- ── Bộ lọc ── --}}
    <form method="GET" action="{{ route('hanhdonlog.index') }}"
          class="rounded-2xl bg-white p-4 ring-1 ring-[#522C25]/10">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Hành động</label>
                <select name="hanh_dong"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    <option value="">Tất cả</option>
                    @foreach($danhSachHanhDong as $hd)
                        <option value="{{ $hd }}" {{ $hanhDong === $hd ? 'selected' : '' }}>
                            {{ AppModelsNhatKyHanhDong::nhanHanhDong($hd) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Từ ngày</label>
                <input type="date" name="tu_ngay" value="{{ $tuNgay }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Đến ngày</label>
                <input type="date" name="den_ngay" value="{{ $denNgay }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Mã nhân viên</label>
                <input type="text" name="ma_nv" value="{{ $maNv }}" placeholder="VD: NV001"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#522C25]">
                Lọc
            </button>
            <a href="{{ route('hanhdonlog.index') }}"
               class="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                Xóa lọc
            </a>
        </div>
    </form>

    {{-- ── Bảng nhật ký ── --}}
    <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-[#522C25]/10">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Thời gian</th>
                        <th class="px-4 py-3 text-left">Nhân viên</th>
                        <th class="px-4 py-3 text-left">Hành động</th>
                        <th class="px-4 py-3 text-left">Đối tượng</th>
                        <th class="px-4 py-3 text-left">Mô tả</th>
                        <th class="px-4 py-3 text-left">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($logs as $log)
                    @php
                        $badgeClass = match(true) {
                            in_array($log->hanh_dong, ['tao_phieu_nhap','duyet_phieu_nhap','huy_phieu_nhap','tao_kiem_ke','xac_nhan_kiem_ke','huy_kiem_ke','tao_nguyen_lieu','cap_nhat_nguyen_lieu','xoa_nguyen_lieu','tao_nha_cung_cap','cap_nhat_nha_cung_cap','xoa_nha_cung_cap'])
                                => 'bg-amber-50 text-amber-700',
                            in_array($log->hanh_dong, ['tao_don_hang','xac_nhan_don_hang','cap_nhat_trang_thai_don','gop_don_hang','tach_don_hang'])
                                => 'bg-blue-50 text-blue-700',
                            $log->hanh_dong === 'thanh_toan_don_hang'
                                => 'bg-green-50 text-green-700',
                            in_array($log->hanh_dong, ['tao_mon','cap_nhat_mon','an_mon','hien_mon'])
                                => 'bg-orange-50 text-orange-700',
                            in_array($log->hanh_dong, ['tao_tai_khoan','cap_nhat_tai_khoan','xoa_tai_khoan','cap_nhat_phan_quyen'])
                                => 'bg-purple-50 text-purple-700',
                            in_array($log->hanh_dong, ['phat_the','dieu_chinh_diem','cap_nhat_trang_thai_the','cap_nhat_hang','xoa_hang'])
                                => 'bg-teal-50 text-teal-700',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                            {{ CarbonCarbon::parse($log->thoi_gian)->format('d/m H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">{{ $log->ten_nv ?? '—' }}</p>
                            <p class="font-mono text-xs text-gray-400">{{ $log->ma_nv ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                {{ AppModelsNhatKyHanhDong::nhanHanhDong($log->hanh_dong) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($log->doi_tuong_loai)
                                <span class="text-gray-400">{{ $log->doi_tuong_loai }}</span>
                                <span class="font-mono font-semibold text-gray-700"> {{ $log->doi_tuong_ma }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="max-w-xs truncate px-4 py-3 text-gray-600">{{ $log->mo_ta ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">{{ $log->dia_chi_ip ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">Không có nhật ký nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
