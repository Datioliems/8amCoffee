@extends('layouts.app')

@section('title', 'Quản lý bàn & QR - 8AM Coffee')
@section('page-title', 'Bàn & QR')

@section('content')
@php
    $stats = [
        'co_khach' => $bans->where('trang_thai', 'co_khach')->count(),
        'trong' => $bans->where('trang_thai', 'trong')->count(),
        'dat_truoc' => $bans->where('trang_thai', 'dat_truoc')->count(),
        'dong' => $bans->where('trang_thai', 'dong')->count(),
    ];
    $totalSeats = $bans->sum(fn($ban) => $ban->so_ghe ?? 4);
    // Bàn coi như "có khách" nếu cờ trạng thái co_khach hoặc có order đang xử lý
    $isOccupied = fn($ban) => $ban->trang_thai === 'co_khach' || (($orderCounts[$ban->ma_ban] ?? 0) > 0);
    $occupiedSeats = $bans->filter($isOccupied)->sum(fn($ban) => $ban->so_ghe ?? 4);
    $statusMeta = [
        'trong' => ['label' => 'Trống', 'dot' => 'bg-[#CADCAC]', 'pill' => 'bg-green-100 text-green-800', 'iso' => '#CADCAC'],
        'co_khach' => ['label' => 'Có khách', 'dot' => 'bg-[#E82C2A]', 'pill' => 'bg-[#ffdad4] text-[#93000b]', 'iso' => '#E82C2A'],
        'dat_truoc' => ['label' => 'Đặt trước', 'dot' => 'bg-[#80534a]', 'pill' => 'bg-[#ffc4b9] text-[#653c34]', 'iso' => '#80534a'],
        'dong' => ['label' => 'Đóng', 'dot' => 'bg-[#916f6b]', 'pill' => 'bg-gray-100 text-gray-500', 'iso' => '#916f6b'],
    ];
    // Nhóm bàn theo tầng (vi_tri) để vẽ sơ đồ isometric
    $bansByFloor = $bans->groupBy(fn($b) => $b->vi_tri ?: 'Khác');
@endphp

<div class="max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs uppercase tracking-[0.18em] text-[#522C25]/55">Table map</p>
            <h2 class="mt-1 text-2xl font-semibold text-[#1A1A1A]">Quản lý sơ đồ bàn và mã QR</h2>
            <p class="mt-1 text-sm text-[#522C25]/65">Cấu hình vị trí, số ghế, trạng thái và QR gọi món cho từng bàn.</p>
        </div>
        <div class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#522C25] ring-1 ring-[#522C25]/10">
            Tổng sức chứa: {{ $totalSeats }} ghế
        </div>
    </div>

    @if(session('chuc_vu') === 'superadmin' && $chiNhanh)
    {{-- Super-admin sửa thông tin chi nhánh ngay trên giao diện --}}
    <form method="POST" action="{{ route('chinhanh.update', $chiNhanh->ma_chi_nhanh) }}"
          class="grid gap-3 rounded-[20px] border border-[#522C25]/10 bg-white p-4 md:grid-cols-[1fr_1fr_auto]">
        @csrf @method('PUT')
        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-[#522C25]/55">Tên chi nhánh</label>
            <input name="ten_chi_nhanh" value="{{ $chiNhanh->ten_chi_nhanh }}" required
                   class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-[#522C25]/55">Địa chỉ</label>
            <input name="dia_chi" value="{{ $chiNhanh->dia_chi }}"
                   class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm">
        </div>
        <div class="flex items-end">
            <button class="rounded-xl bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white">Lưu chi nhánh</button>
        </div>
    </form>
    @endif

    {{-- Sơ đồ bàn đã chuyển sang trang Đơn hàng (đặt món tại bàn). Ở đây chỉ giữ
         tổng quan sức chứa + danh sách cấu hình bàn/QR. --}}
    <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl bg-white p-4 ring-1 ring-[#522C25]/10">
            <p class="text-xs text-[#522C25]/60">Có khách</p>
            <p class="mt-1 font-mono text-2xl font-bold text-[#E82C2A]">{{ $stats['co_khach'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 ring-1 ring-[#522C25]/10">
            <p class="text-xs text-[#522C25]/60">Trống</p>
            <p class="mt-1 font-mono text-2xl font-bold text-[#52613B]">{{ $stats['trong'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 ring-1 ring-[#522C25]/10">
            <p class="text-xs text-[#522C25]/60">Đặt trước</p>
            <p class="mt-1 font-mono text-2xl font-bold text-[#80534a]">{{ $stats['dat_truoc'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 ring-1 ring-[#522C25]/10">
            <p class="text-xs text-[#522C25]/60">Ghế đang dùng</p>
            <p class="mt-1 font-mono text-2xl font-bold text-[#1A1A1A]">{{ $occupiedSeats }}/{{ $totalSeats }}</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-[25px] border border-[#e6bdb8]/35 bg-white">
        <div class="flex flex-col gap-3 border-b border-[#522C25]/10 p-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.16em] text-[#522C25]/55">Table directory</p>
                <h3 class="mt-1 text-lg font-semibold">Danh sách bàn</h3>
            </div>
            <div class="rounded-full bg-[#F2F2F2] px-4 py-2 text-sm font-semibold text-[#522C25]">
                {{ $bans->count() }} bàn
            </div>
        </div>

        {{-- Lưới thẻ bàn — responsive: 1 cột (mobile) → 2 (md) → 3 (xl) --}}
        <div class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($bans as $ban)
            @php $meta = $statusMeta[$ban->trang_thai] ?? $statusMeta['dong']; @endphp
            <div id="table-{{ $ban->ma_ban }}" class="flex flex-col gap-3 rounded-2xl border border-[#522C25]/10 bg-white p-4 transition hover:shadow-md">

                {{-- Header: mã bàn + trạng thái + QR --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-lg font-bold text-[#1A1A1A]">B-{{ str_pad($ban->so_ban, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $meta['pill'] }}">{{ $meta['label'] }}</span>
                    </div>
                    <a href="{{ route('ban.qr.poster', $ban->ma_ban) }}" target="_blank" title="Mở poster QR bàn {{ $ban->so_ban }}"
                       class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-[#1A1A1A] p-1">
                        <img src="{{ route('ban.qr', $ban->ma_ban) }}" alt="QR bàn {{ $ban->so_ban }}" class="h-9 w-9 rounded-sm bg-white">
                    </a>
                </div>

                <p class="text-xs text-[#522C25]/60">{{ $orderCounts[$ban->ma_ban] ?? 0 }} order đang xử lý</p>

                {{-- Form cập nhật bàn --}}
                <form method="POST" action="{{ route('ban.update', $ban->ma_ban) }}" class="grid grid-cols-2 gap-2">
                    @csrf @method('PUT')
                    <label class="col-span-2 text-[11px] font-semibold text-[#522C25]/55">Vị trí
                        <input type="text" name="vi_tri" value="{{ old('vi_tri', $ban->vi_tri) }}"
                               class="mt-1 w-full rounded-xl border-0 bg-[#F2F2F2] px-3 py-2 text-sm focus:ring-2 focus:ring-[#E82C2A]/20">
                    </label>
                    <label class="text-[11px] font-semibold text-[#522C25]/55">Số ghế
                        <input type="number" name="so_ghe" min="1" max="20" value="{{ old('so_ghe', $ban->so_ghe ?? 4) }}"
                               class="mt-1 w-full rounded-xl border-0 bg-[#F2F2F2] px-3 py-2 text-sm focus:ring-2 focus:ring-[#E82C2A]/20">
                    </label>
                    <label class="text-[11px] font-semibold text-[#522C25]/55">Trạng thái
                        <select name="trang_thai" class="mt-1 w-full rounded-xl border-0 bg-[#F2F2F2] px-3 py-2 text-sm focus:ring-2 focus:ring-[#E82C2A]/20">
                            @foreach(['trong' => 'Trống', 'co_khach' => 'Có khách', 'dat_truoc' => 'Đặt trước', 'dong' => 'Đóng'] as $value => $label)
                                <option value="{{ $value }}" {{ $ban->trang_thai === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="col-span-2 mt-1 rounded-full bg-[#E82C2A] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#BB0011]">
                        Lưu thay đổi
                    </button>
                </form>

                {{-- Ảnh bàn --}}
                <div class="flex items-center gap-3 border-t border-[#522C25]/10 pt-3">
                    @if($ban->anh)
                        <img src="{{ asset('images/'.$ban->anh) }}?v={{ $ban->updated_at?->timestamp ?? time() }}"
                             class="h-14 w-20 shrink-0 rounded-lg object-cover ring-1 ring-[#522C25]/10" alt="Ảnh bàn {{ $ban->so_ban }}">
                    @else
                        <div class="flex h-14 w-20 shrink-0 items-center justify-center rounded-lg bg-[#F2F2F2] text-[10px] text-[#522C25]/40">Chưa có</div>
                    @endif
                    <form method="POST" action="{{ route('ban.photo', $ban->ma_ban) }}" enctype="multipart/form-data" class="flex flex-1 flex-col gap-1">
                        @csrf
                        <input type="file" name="anh" accept="image/*" required
                               class="w-full text-[11px] file:mr-2 file:rounded-full file:border-0 file:bg-[#522C25]/10 file:px-2 file:py-1 file:text-[11px]">
                        <button class="self-start rounded-full bg-[#1A1A1A] px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-black">Tải ảnh</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
