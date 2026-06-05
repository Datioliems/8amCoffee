@extends('layouts.app')
@section('title', 'Cấu hình hạng hội viên')
@section('page-title', 'Cấu hình hạng hội viên')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <a href="{{ route('loyalty.index') }}" class="text-sm text-[#8B5A2B] hover:underline">← Về danh sách thẻ</a>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error') || $errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#BB0011]">{{ session('error') ?? $errors->first() }}</div>
    @endif

    @if($chuaMigrate)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Đang hiển thị từ file cấu hình. Để <b>lưu thay đổi qua web</b>, chạy
            <code class="rounded bg-amber-100 px-1">php artisan migrate</code> (tạo bảng <code>CAU_HINH_HANG</code>) rồi tải lại trang.
        </div>
    @endif

    <form method="POST" action="{{ route('loyalty.tiers.update') }}" class="overflow-hidden rounded-2xl border border-[#522C25]/10 bg-white shadow-sm">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Hạng</th>
                        <th class="px-4 py-3">Nhãn</th>
                        <th class="px-4 py-3">Ngưỡng điểm</th>
                        <th class="px-4 py-3">Hệ số tích điểm</th>
                        <th class="px-4 py-3">Loại giảm</th>
                        <th class="px-4 py-3">Mức giảm</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @foreach($tiers as $t)
                    <tr>
                        <td class="px-4 py-3 font-mono text-[11px] text-[#522C25]/60">{{ $t->ma_hang }}</td>
                        <td class="px-4 py-3">
                            <input name="hang[{{ $t->ma_hang }}][nhan]" value="{{ $t->nhan }}" required
                                   class="w-28 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" min="0" step="1" name="hang[{{ $t->ma_hang }}][nguong]" value="{{ (int) $t->nguong }}" required
                                   class="w-24 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" min="0" max="99" step="0.05" name="hang[{{ $t->ma_hang }}][he_so]" value="{{ $t->he_so }}" required
                                   class="w-20 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
                        </td>
                        <td class="px-4 py-3">
                            <select name="hang[{{ $t->ma_hang }}][giam_loai]" class="rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm">
                                <option value="phan_tram" @selected($t->giam_loai === 'phan_tram')>%</option>
                                <option value="tien" @selected($t->giam_loai === 'tien')>Tiền (đ)</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" min="0" step="1" name="hang[{{ $t->ma_hang }}][giam_gia_tri]" value="{{ (int) $t->giam_gia_tri }}" required
                                   class="w-28 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-[#522C25]/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[11px] leading-5 text-[#522C25]/55">
                <b>Hệ số tích điểm</b>: tốc độ cộng điểm (vd 1.5 = ×1.5). <b>Loại giảm</b> "%" → mức giảm tính theo %;
                "Tiền" → mức giảm là số đồng (POS tự quy ra % hóa đơn). Ngưỡng tính theo <b>tổng điểm tích lũy</b>.
            </p>
            <button @if($chuaMigrate) disabled @endif
                    class="shrink-0 rounded-lg bg-[#8B5A2B] px-5 py-2 text-sm font-semibold text-white hover:bg-[#6F4621] disabled:cursor-not-allowed disabled:opacity-50">
                Lưu cấu hình
            </button>
        </div>
    </form>
</div>
@endsection
