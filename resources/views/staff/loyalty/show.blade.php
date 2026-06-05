@extends('layouts.app')
@section('title', 'Thẻ ' . $card->ma_the)
@section('page-title', 'Chi tiết thẻ ' . $card->ma_the)

@php
    $loaiNhan = ['khoi_tao'=>'Khởi tạo','tich_diem'=>'Tích điểm','doi_diem'=>'Đổi điểm','dieu_chinh'=>'Điều chỉnh','het_han'=>'Hết hạn'];
    $ttNhan   = ['hoat_dong'=>'Hoạt động','khoa'=>'Đã khóa','mat'=>'Báo mất'];
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('loyalty.index') }}" class="text-sm text-[#8B5A2B] hover:underline">← Về danh sách thẻ</a>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error') || $errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#BB0011]">{{ session('error') ?? $errors->first() }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Thông tin thẻ --}}
        <div class="rounded-2xl border border-[#522C25]/10 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Mã thẻ</p>
                    <p class="text-xl font-bold text-[#1A1A1A]">{{ $card->ma_the }}</p>
                    <p class="font-mono text-xs text-[#522C25]/55">UID {{ $card->uid_rfid }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Số dư điểm</p>
                    <p class="text-3xl font-bold text-[#8B5A2B]">{{ number_format($card->diem_hien_tai, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-[#522C25]/45">≈ {{ number_format($card->giaTriTien(), 0, ',', '.') }}đ</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div><p class="text-[11px] text-[#522C25]/55">Khách</p><p class="font-medium">{{ $card->khachHang?->ten_kh ?? ($card->ma_kh ?: '—') }}</p></div>
                <div><p class="text-[11px] text-[#522C25]/55">Hạng</p><p class="font-medium">{{ $tier['nhan'] }}</p></div>
                <div><p class="text-[11px] text-[#522C25]/55">Trạng thái</p><p class="font-medium">{{ $ttNhan[$card->trang_thai] ?? $card->trang_thai }}</p></div>
                <div><p class="text-[11px] text-[#522C25]/55">Tổng tích lũy</p><p class="font-medium">{{ number_format($card->tong_diem_tich_luy, 0, ',', '.') }}</p></div>
            </div>
            <div class="mt-3 rounded-lg bg-[#FFF7E8] px-3 py-2 text-[12px] text-[#8B5A2B]">
                <b>Quyền lợi hạng {{ $tier['nhan'] }}:</b>
                tích điểm <b>×{{ $tier['he_so'] }}</b>
                @if($tier['giam']['gia_tri'] > 0)
                    · ưu đãi POS <b>{{ $tier['giam']['loai'] === 'tien' ? number_format($tier['giam']['gia_tri'], 0, ',', '.') . 'đ' : $tier['giam']['gia_tri'] . '%' }}</b>
                @else
                    · chưa có ưu đãi giảm giá
                @endif
            </div>
            @if($ledgerBalance !== (int) $card->diem_hien_tai)
                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[11px] text-amber-700">
                    ⚠ Số dư sổ cái ({{ number_format($ledgerBalance,0,',','.') }}) lệch với số dư thẻ ({{ number_format($card->diem_hien_tai,0,',','.') }}). Cần đối soát.
                </p>
            @endif
        </div>

        {{-- Hành động --}}
        <div class="space-y-4">
            <form method="POST" action="{{ route('loyalty.status', $card->ma_the) }}" class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
                @csrf
                <p class="mb-2 text-sm font-semibold text-[#8B5A2B]">Trạng thái thẻ</p>
                <div class="flex gap-2">
                    <select name="trang_thai" class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                        <option value="hoat_dong" @selected($card->trang_thai==='hoat_dong')>Hoạt động</option>
                        <option value="khoa" @selected($card->trang_thai==='khoa')>Khóa</option>
                        <option value="mat" @selected($card->trang_thai==='mat')>Báo mất</option>
                    </select>
                    <button class="shrink-0 rounded-lg bg-[#1A1A1A] px-3 py-2 text-sm font-semibold text-white hover:bg-black">Lưu</button>
                </div>
            </form>

            <form method="POST" action="{{ route('loyalty.adjust', $card->ma_the) }}" class="rounded-2xl border border-[#522C25]/10 bg-white p-4 shadow-sm">
                @csrf
                <p class="mb-2 text-sm font-semibold text-[#8B5A2B]">Điều chỉnh điểm</p>
                <input type="number" name="so_diem" required placeholder="+100 hoặc -50"
                       class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                <input type="text" name="ly_do" required maxlength="255" placeholder="Lý do (bắt buộc)"
                       class="mt-2 w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                <button class="mt-2 w-full rounded-lg bg-[#8B5A2B] px-3 py-2 text-sm font-semibold text-white hover:bg-[#6F4621]">Áp dụng điều chỉnh</button>
                <p class="mt-1 text-[11px] text-[#522C25]/45">Số dương = cộng, số âm = trừ. Ghi vào sổ cái.</p>
            </form>
        </div>
    </div>

    {{-- Sổ cái điểm --}}
    <div class="overflow-hidden rounded-2xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="border-b border-[#522C25]/10 px-5 py-3 text-sm font-semibold text-[#522C25]">Lịch sử điểm (100 dòng gần nhất)</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3">Loại</th>
                        <th class="px-4 py-3 text-right">Điểm</th>
                        <th class="px-4 py-3 text-right">Số dư sau</th>
                        <th class="px-4 py-3 text-right">Tiền liên quan</th>
                        <th class="px-4 py-3">Hóa đơn / Đơn</th>
                        <th class="px-4 py-3">Mô tả</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($ledger as $g)
                    <tr>
                        <td class="px-4 py-2.5 text-[#522C25]/70">{{ \Illuminate\Support\Carbon::parse($g->thoi_gian)->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2.5">{{ $loaiNhan[$g->loai] ?? $g->loai }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold {{ $g->so_diem >= 0 ? 'text-emerald-600' : 'text-[#BB0011]' }}">{{ $g->so_diem >= 0 ? '+' : '' }}{{ number_format($g->so_diem, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $g->so_diem_sau !== null ? number_format($g->so_diem_sau, 0, ',', '.') : '—' }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $g->so_tien_lien_quan ? number_format($g->so_tien_lien_quan, 0, ',', '.') . 'đ' : '—' }}</td>
                        <td class="px-4 py-2.5 text-[11px] text-[#522C25]/60">{{ $g->ma_hoa_don ?: $g->ma_order ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-[#522C25]/70">{{ $g->mo_ta }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-[#522C25]/55">Chưa có giao dịch điểm.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
