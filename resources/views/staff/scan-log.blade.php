@extends('layouts.app')
@section('title', 'Log quét QR - 8AM')
@section('page-title', 'Log quét mã QR')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div class="rounded-3xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-[#522C25]/10 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold">Lịch sử quét mã QR tại bàn</h2>
                <p class="text-xs text-[#522C25]/55">Ghi nhận số bàn, chi nhánh và thời gian khách quét mã.</p>
            </div>
            <span class="text-sm text-[#522C25]/60">{{ $logs->total() }} lượt</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3">Bàn</th>
                        @if($isSuper)<th class="px-4 py-3">Chi nhánh</th>@endif
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($log->thoi_gian)->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->so_ban ? 'Bàn ' . $log->so_ban : $log->ma_ban }}</td>
                        @if($isSuper)<td class="px-4 py-3">{{ $log->ten_chi_nhanh ?? $log->ma_chi_nhanh }}</td>@endif
                        <td class="px-4 py-3 text-[#522C25]/55">{{ $log->ip }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $isSuper ? 4 : 3 }}" class="px-4 py-16 text-center text-[#522C25]/55">Chưa có lượt quét nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>{{ $logs->links() }}</div>
</div>
@endsection
