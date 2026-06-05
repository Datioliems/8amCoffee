@extends('layouts.app')
@section('title', 'Danh sách khách hàng - 8AM')
@section('page-title', 'Danh sách khách hàng')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div class="rounded-3xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-[#522C25]/10 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold">Khách hàng có giao dịch</h2>
                <p class="text-xs text-[#522C25]/55">Chỉ lưu khách đã phát sinh giao dịch (có hóa đơn).</p>
            </div>
            <span class="text-sm text-[#522C25]/60">{{ $khachHangs->total() }} khách</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Khách hàng</th>
                        <th class="px-4 py-3">Số điện thoại</th>
                        <th class="px-4 py-3 text-right">Số đơn</th>
                        <th class="px-4 py-3 text-right">Tổng chi tiêu</th>
                        <th class="px-4 py-3">Lần cuối</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($khachHangs as $kh)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $kh->ten_kh }}</p>
                            <p class="text-xs text-[#522C25]/45">{{ $kh->ma_kh }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $kh->sdt ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ $kh->so_don }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-[#8B5A2B]">{{ number_format($kh->tong_chi_tieu, 0, ',', '.') }}đ</td>
                        <td class="px-4 py-3 text-[#522C25]/60">{{ $kh->lan_cuoi ? \Carbon\Carbon::parse($kh->lan_cuoi)->format('d/m/Y H:i') : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-16 text-center text-[#522C25]/55">Chưa có khách hàng nào có giao dịch.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>{{ $khachHangs->links() }}</div>
</div>
@endsection
