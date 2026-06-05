@extends('layouts.app')
@section('title', 'Nhật ký Email - 8AM')
@section('page-title', 'Nhật ký gửi Email')

@section('content')
@php
    $nhanLoai = [
        'tai_khoan' => ['Tài khoản', 'bg-blue-50 text-blue-700'],
        'kich_hoat' => ['Kích hoạt', 'bg-violet-50 text-violet-700'],
        'otp'       => ['OTP', 'bg-amber-50 text-amber-700'],
        'khac'      => ['Khác', 'bg-[#F1ECEA] text-[#522C25]'],
    ];
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Gửi thành công (24h)</p>
            <p class="mt-1 text-3xl font-semibold text-emerald-600">{{ $thanhCong }}</p>
        </div>
        <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Gửi thất bại (24h)</p>
            <p class="mt-1 text-3xl font-semibold text-[#BB0011]">{{ $thatBai }}</p>
        </div>
    </div>

    <div class="rounded-3xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#522C25]/10 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold">Lịch sử email hệ thống</h2>
                <p class="text-xs text-[#522C25]/55">Thông tin tài khoản, link kích hoạt và mã OTP đã gửi.</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET">
                    <select name="loai" onchange="this.form.submit()" class="rounded-full border border-[#522C25]/15 bg-white px-4 py-2 text-sm">
                        <option value="">Tất cả loại</option>
                        @foreach($nhanLoai as $k => $v)
                            <option value="{{ $k }}" @selected($loai === $k)>{{ $v[0] }}</option>
                        @endforeach
                    </select>
                </form>
                <form method="POST" action="{{ route('emaillog.clearFailed') }}"
                      onsubmit="return confirm('Xóa toàn bộ dòng email gửi thất bại?');">
                    @csrf @method('DELETE')
                    <button class="rounded-full bg-red-50 px-4 py-2 text-sm font-semibold text-[#BB0011] ring-1 ring-red-100 hover:bg-red-100">
                        Xóa email thất bại
                    </button>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3">Loại</th>
                        <th class="px-4 py-3">Người nhận</th>
                        <th class="px-4 py-3">Tiêu đề</th>
                        <th class="px-4 py-3">Kết quả</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($logs as $log)
                    @php $meta = $nhanLoai[$log->loai] ?? [$log->loai, 'bg-[#F1ECEA] text-[#522C25]']; @endphp
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->thoi_gian)->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $meta[1] }}">{{ $meta[0] }}</span></td>
                        <td class="px-4 py-3 font-medium">{{ $log->email }}</td>
                        <td class="px-4 py-3 text-[#522C25]/70">{{ $log->tieu_de }}</td>
                        <td class="px-4 py-3">
                            @if($log->trang_thai === 'thanh_cong')
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Thành công</span>
                            @else
                                <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-[#BB0011]" title="{{ $log->loi }}">Thất bại</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('emaillog.destroy', $log->id) }}" onsubmit="return confirm('Xóa dòng này?');">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-[#BB0011] hover:underline">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center text-[#522C25]/55">Chưa có email nào được ghi nhận.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>{{ $logs->links() }}</div>
</div>
@endsection
