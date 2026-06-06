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

    <div class="flex justify-end">
        <a href="{{ route('loyalty.tiers') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-[#522C25]/20 px-3 py-2 text-sm font-semibold text-[#522C25] hover:bg-[#F2F2F2]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
            Cấu hình hạng
        </a>
    </div>

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

            {{-- UID nhập thủ công hoặc qua đầu đọc USB (gõ tay khi không có đầu đọc) --}}
            <label class=”mb-1 block text-[11px] font-semibold text-[#522C25]/55”>UID thẻ (hex)</label>
            <input type=”text” name=”uid” id=”uid-input” required placeholder=”Nhập UID thẻ (vd: 04A3B2C1)”
                   class=”w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm font-mono transition”>

            {{-- Chọn khách đủ điều kiện — gõ tên/SĐT, hiện gợi ý (autocomplete) --}}
            <label class="mt-3 mb-1 block text-[11px] font-semibold text-[#522C25]/55">Khách đủ điều kiện (gõ tên hoặc SĐT)</label>
            <div class="relative" id="kh-ac">
                <input type="text" id="kh-search" autocomplete="off" placeholder="Gõ tên hoặc số điện thoại khách..."
                       class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                <input type="hidden" name="ma_kh" id="kh-ma" value="">
                <div id="kh-list" class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-[#522C25]/15 bg-white shadow-lg">
                    @forelse($eligible as $e)
                        <button type="button" class="kh-item block w-full border-b border-[#522C25]/5 px-3 py-2 text-left text-sm hover:bg-[#FFF7E8]"
                                data-ma="{{ $e['ma_kh'] }}"
                                data-label="{{ $e['ten_kh'] ?: $e['ma_kh'] }} · {{ $e['sdt_che'] }}"
                                data-search="{{ \Illuminate\Support\Str::lower(($e['ten_kh'] ?? '') . ' ' . ($e['sdt'] ?? '') . ' ' . ($e['sdt_che'] ?? '')) }}">
                            <span class="font-medium text-[#1A1A1A]">{{ $e['ten_kh'] ?: $e['ma_kh'] }}</span>
                            <span class="text-[#522C25]/60"> · {{ $e['sdt_che'] }}</span>
                            <span class="text-[#8B5A2B]"> · {{ number_format($e['tong'], 0, ',', '.') }}đ</span>
                        </button>
                    @empty
                        <div class="px-3 py-2 text-[11px] text-[#522C25]/45">Chưa có khách đủ điều kiện.</div>
                    @endforelse
                </div>
            </div>
            @if(empty($eligible))
                <p class="mt-1 text-[11px] text-[#522C25]/45">Chưa có khách đạt mốc {{ number_format((int) config('loyalty.issue_threshold'), 0, ',', '.') }}đ (hoặc tất cả đã có thẻ).</p>
            @endif

            {{-- Hoặc nhập SĐT thủ công --}}
            <details class="mt-2">
                <summary class="cursor-pointer text-[11px] text-[#8B5A2B]">Hoặc nhập SĐT thủ công</summary>
                <input type="text" name="sdt" inputmode="numeric" pattern="0[0-9]{9}" placeholder="0901234567"
                       class="mt-1 w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                <p class="mt-1 text-[11px] text-[#522C25]/45">Dùng khi khách đủ mốc nhưng không có trong danh sách.</p>
            </details>

            <button class="mt-3 w-full rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white hover:bg-black">Phát thẻ</button>
            <p class="mt-1 text-[11px] text-[#522C25]/45">Điểm khởi tạo = tổng chi tiêu ÷ {{ number_format((int) config('loyalty.earn_per_amount'), 0, ',', '.') }}đ.</p>
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

<script>
// ── Autocomplete khách đủ điều kiện ──
document.addEventListener('DOMContentLoaded', () => {

    // ===== AUTOCOMPLETE khách đủ điều kiện =====
    (() => {
        const wrap = document.getElementById('kh-ac');
        const input = document.getElementById('kh-search');
        const list = document.getElementById('kh-list');
        const hidden = document.getElementById('kh-ma');
        if (!wrap || !input || !list) return;
        const items = Array.from(list.querySelectorAll('.kh-item'));

        const filter = () => {
            const q = input.value.trim().toLowerCase();
            items.forEach((it) => {
                const ok = q === '' || (it.dataset.search || '').includes(q);
                it.style.display = ok ? '' : 'none';
            });
        };

        input.addEventListener('focus', () => { list.classList.remove('hidden'); filter(); });
        input.addEventListener('input', () => { hidden.value = ''; list.classList.remove('hidden'); filter(); });
        items.forEach((it) => it.addEventListener('click', () => {
            hidden.value = it.dataset.ma;
            input.value = it.dataset.label;
            list.classList.add('hidden');
        }));
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) list.classList.add('hidden'); });
    })();
});
</script>
@endsection
