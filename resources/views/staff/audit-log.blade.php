@extends('layouts.app')
@section('title', 'Nhật ký đăng nhập - 8AM')
@section('page-title', 'Nhật ký đăng nhập & an toàn')

@section('content')
@php
    $nhan = [
        'dang_nhap'           => ['Đăng nhập', 'bg-emerald-50 text-emerald-700'],
        'dang_nhap_that_bai'  => ['Đăng nhập sai', 'bg-red-50 text-[#BB0011]'],
        'tai_khoan_bi_khoa'   => ['Tài khoản bị khoá', 'bg-orange-50 text-orange-700'],
        'otp_gui'             => ['Gửi OTP', 'bg-blue-50 text-blue-700'],
        'otp_thanh_cong'      => ['OTP đúng', 'bg-emerald-50 text-emerald-700'],
        'otp_sai'             => ['OTP sai', 'bg-red-50 text-[#BB0011]'],
        'dang_xuat'           => ['Đăng xuất', 'bg-[#F1ECEA] text-[#522C25]'],
    ];
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- Thống kê 24h --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Đăng nhập thành công (24h)</p>
            <p class="mt-1 text-3xl font-semibold text-emerald-600">{{ $thanhCong24h }}</p>
        </div>
        <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Đăng nhập sai (24h)</p>
            <p class="mt-1 text-3xl font-semibold text-[#BB0011]">{{ $thatBai24h }}</p>
        </div>
        <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Tài khoản bị khoá (24h)</p>
            <p class="mt-1 text-3xl font-semibold text-orange-600">{{ $khoa24h }}</p>
        </div>
    </div>

    <div class="rounded-3xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#522C25]/10 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold">Lịch sử truy cập hệ thống</h2>
                <p class="text-xs text-[#522C25]/55">Ghi nhận đăng nhập, thất bại, khoá tài khoản và xác thực 2 lớp.</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <select name="hanh_dong" onchange="this.form.submit()"
                        class="rounded-full border border-[#522C25]/15 bg-white px-4 py-2 text-sm">
                    <option value="">Tất cả hành động</option>
                    @foreach($nhan as $k => $v)
                        <option value="{{ $k }}" @selected($hanhDong === $k)>{{ $v[0] }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3">Tài khoản</th>
                        <th class="px-4 py-3">Hành động</th>
                        <th class="px-4 py-3">IP</th>
                        <th class="px-4 py-3">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($logs as $log)
                    @php $meta = $nhan[$log->hanh_dong] ?? [$log->hanh_dong, 'bg-[#F1ECEA] text-[#522C25]']; @endphp
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->thoi_gian)->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $log->ten_tk ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $meta[1] }}">{{ $meta[0] }}</span>
                        </td>
                        <td class="px-4 py-3 text-[#522C25]/55">{{ $log->dia_chi_ip }}</td>
                        <td class="px-4 py-3 text-[#522C25]/70">{{ $log->chi_tiet }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-16 text-center text-[#522C25]/55">Chưa có bản ghi nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>{{ $logs->links() }}</div>
</div>
@endsection
