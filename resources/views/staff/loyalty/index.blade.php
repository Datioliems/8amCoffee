@extends('layouts.app')
@section('title', 'Thẻ thành viên')
@section('page-title', 'Thẻ thành viên & điểm')

@php
    $tt = ['hoat_dong' => ['Hoạt động','bg-emerald-50 text-emerald-700'], 'khoa' => ['Đã khóa','bg-[#F1ECEA] text-[#522C25]/70'], 'mat' => ['Báo mất','bg-red-50 text-[#BB0011]']];
@endphp

@section('content')
<div class="mx-auto max-w-6xl space-y-5">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#BB0011]">{{ session('error') }}</div>
    @endif

    {{-- KPI --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Tổng thẻ</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($thongKe['tong_the'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Đang hoạt động</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ number_format($thongKe['hoat_dong'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Tổng điểm đang lưu hành</p>
            <p class="mt-1 text-2xl font-semibold text-[#8B5A2B]">{{ number_format($thongKe['tong_diem'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Phát thẻ + tìm kiếm --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <form method="POST" action="{{ route('loyalty.issue') }}" class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            @csrf
            <p class="mb-2 text-sm font-semibold text-[#8B5A2B]">Phát thẻ mới</p>
            <div class="flex flex-wrap items-end gap-2">
                <div class="grow">
                    <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">UID thẻ (hex)</label>
                    <input type="text" name="uid" required placeholder="04A1B2C3"
                           class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                </div>
                <div class="grow">
                    <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">SĐT khách</label>
                    <input type="text" name="sdt" required inputmode="numeric" pattern="0[0-9]{9}" placeholder="0901234567"
                           class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                </div>
                <button class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white hover:bg-black">Phát thẻ</button>
            </div>
            <p class="mt-1 text-[11px] text-[#522C25]/45">Điểm khởi tạo tính theo tổng chi tiêu của khách (mốc {{ number_format((int) config('loyalty.issue_threshold'), 0, ',', '.') }}đ).</p>
        </form>

        <form method="GET" class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
            <p class="mb-2 text-sm font-semibold text-[#8B5A2B]">Tìm thẻ</p>
            <div class="flex flex-wrap items-end gap-2">
                <div class="grow">
                    <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">UID / Mã thẻ / SĐT</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="04A1B2C3 · TV000001 · 0901234567"
                           class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Trạng thái</label>
                    <select name="trang_thai" class="rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                        <option value="">Tất cả</option>
                        <option value="hoat_dong" @selected($trangThai==='hoat_dong')>Hoạt động</option>
                        <option value="khoa" @selected($trangThai==='khoa')>Đã khóa</option>
                        <option value="mat" @selected($trangThai==='mat')>Báo mất</option>
                    </select>
                </div>
                <button class="rounded-lg bg-[#8B5A2B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6F4621]">Lọc</button>
            </div>
        </form>
    </div>

    {{-- Bảng thẻ --}}
    <div class="overflow-hidden rounded-2xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Mã thẻ / UID</th>
                        <th class="px-4 py-3">Khách</th>
                        <th class="px-4 py-3 text-right">Điểm</th>
                        <th class="px-4 py-3">Hạng</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Ngày phát</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($cards as $card)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-[#1A1A1A]">{{ $card->ma_the }}</p>
                            <p class="font-mono text-[11px] text-[#522C25]/55">{{ $card->uid_rfid }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $card->khachHang?->ten_kh ?? ($card->ma_kh ?: '—') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-[#8B5A2B]">{{ number_format($card->diem_hien_tai, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-[#FFF7E8] px-2 py-0.5 text-[11px] font-semibold text-[#8B5A2B]">{{ $card->hang_the }}</span></td>
                        <td class="px-4 py-3">
                            @php($s = $tt[$card->trang_thai] ?? [$card->trang_thai,'bg-[#F1ECEA] text-[#522C25]/70'])
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $s[1] }}">{{ $s[0] }}</span>
                        </td>
                        <td class="px-4 py-3 text-[#522C25]/70">{{ $card->ngay_phat ? \Illuminate\Support\Carbon::parse($card->ngay_phat)->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('loyalty.show', $card->ma_the) }}" class="rounded-lg border border-[#522C25]/20 px-3 py-1.5 text-xs font-semibold text-[#522C25] hover:bg-[#F2F2F2]">Xem</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-16 text-center text-[#522C25]/55">Chưa có thẻ nào. Hãy phát thẻ ở khung trên.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $cards->links() }}</div>
</div>
@endsection
