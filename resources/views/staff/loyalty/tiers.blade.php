@extends('layouts.app')
@section('title', 'Cấu hình hạng hội viên')
@section('page-title', 'Cấu hình hạng hội viên')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
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
                        <th class="px-4 py-3">Mã hạng</th>
                        <th class="px-4 py-3">Nhãn</th>
                        <th class="px-4 py-3">Ngưỡng điểm</th>
                        <th class="px-4 py-3">Hệ số tích điểm</th>
                        <th class="px-4 py-3">Loại giảm</th>
                        <th class="px-4 py-3">Mức giảm</th>
                        <th class="px-4 py-3 w-16"></th>
                    </tr>
                </thead>
                <tbody id="tier-tbody" class="divide-y divide-[#522C25]/8">
                    @foreach($tiers as $t)
                    <tr>
                        {{-- Mã hạng: readonly cho hạng đã có --}}
                        <td class="px-4 py-3 font-mono text-[11px] text-[#522C25]/60">{{ $t->ma_hang }}</td>
                        <td class="px-4 py-3">
                            <input name="hang[{{ $t->ma_hang }}][nhan]" value="{{ $t->nhan }}" required maxlength="50"
                                   class="w-28 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" min="0" step="1" name="hang[{{ $t->ma_hang }}][nguong]"
                                   value="{{ (int) $t->nguong }}" required
                                   class="w-24 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" min="0" max="99" step="0.05" name="hang[{{ $t->ma_hang }}][he_so]"
                                   value="{{ $t->he_so }}" required
                                   class="w-20 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
                        </td>
                        <td class="px-4 py-3">
                            <select name="hang[{{ $t->ma_hang }}][giam_loai]"
                                    class="rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm">
                                <option value="phan_tram" @selected($t->giam_loai === 'phan_tram')>%</option>
                                <option value="tien"      @selected($t->giam_loai === 'tien')>Tiền (đ)</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" min="0" step="1" name="hang[{{ $t->ma_hang }}][giam_gia_tri]"
                                   value="{{ (int) $t->giam_gia_tri }}" required
                                   class="w-28 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(!$chuaMigrate)
                            <button type="button"
                                    data-ma="{{ $t->ma_hang }}"
                                    data-nhan="{{ $t->nhan }}"
                                    data-url="{{ route('loyalty.tiers.delete', $t->ma_hang) }}"
                                    onclick="confirmDelete(this)"
                                    class="rounded-lg border border-red-200 px-2.5 py-1 text-[11px] font-semibold text-red-500 hover:bg-red-50">
                                Xóa
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Footer: ghi chú + nút thêm hạng + lưu --}}
        <div class="flex flex-col gap-3 border-t border-[#522C25]/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[11px] leading-5 text-[#522C25]/55">
                <b>Hệ số tích điểm</b>: ×1.0 = thường, ×1.5 = nhanh hơn 50%.&nbsp;
                <b>Loại giảm</b> "%" → giảm theo %; "Tiền" → số đồng cố định.&nbsp;
                <b>Ngưỡng</b> tính theo <b>tổng điểm tích lũy</b>.
            </p>
            <div class="flex shrink-0 gap-2">
                @if(!$chuaMigrate)
                <button type="button" id="btn-add-tier"
                        class="rounded-lg border border-emerald-600/30 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    + Thêm hạng
                </button>
                @endif
                <button @if($chuaMigrate) disabled @endif
                        class="rounded-lg bg-[#8B5A2B] px-5 py-2 text-sm font-semibold text-white hover:bg-[#6F4621] disabled:cursor-not-allowed disabled:opacity-50">
                    Lưu cấu hình
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Template hàng hạng mới (ẩn, nhân bản bằng JS) --}}
<template id="new-tier-tpl">
    <tr class="bg-emerald-50/50">
        <td class="px-4 py-3">
            <input name="hang_moi[__IDX__][ma_hang]"
                   placeholder="vd: bach_kim"
                   pattern="[a-z][a-z0-9_]+"
                   title="Chữ thường, số, gạch dưới — bắt đầu bằng chữ"
                   maxlength="20" required
                   class="w-28 rounded-lg border border-emerald-400/60 bg-white px-2 py-1.5 text-sm font-mono placeholder:text-[#522C25]/30">
            <p class="mt-0.5 text-[10px] text-[#522C25]/40">a-z, 0-9, _</p>
        </td>
        <td class="px-4 py-3">
            <input name="hang_moi[__IDX__][nhan]" placeholder="Tên hiển thị" required maxlength="50"
                   class="w-28 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm">
        </td>
        <td class="px-4 py-3">
            <input type="number" min="0" step="1" name="hang_moi[__IDX__][nguong]" value="0" required
                   class="w-24 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
        </td>
        <td class="px-4 py-3">
            <input type="number" min="0" max="99" step="0.05" name="hang_moi[__IDX__][he_so]" value="1.00" required
                   class="w-20 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
        </td>
        <td class="px-4 py-3">
            <select name="hang_moi[__IDX__][giam_loai]" class="rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm">
                <option value="phan_tram">%</option>
                <option value="tien">Tiền (đ)</option>
            </select>
        </td>
        <td class="px-4 py-3">
            <input type="number" min="0" step="1" name="hang_moi[__IDX__][giam_gia_tri]" value="0" required
                   class="w-28 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm text-right">
        </td>
        <td class="px-4 py-3 text-right">
            <button type="button" onclick="this.closest('tr').remove()"
                    class="rounded-lg border border-red-200 px-2.5 py-1 text-[11px] font-semibold text-red-400 hover:bg-red-50">
                Bỏ
            </button>
        </td>
    </tr>
</template>

{{-- Form ẩn để thực hiện DELETE (tránh dùng fetch) --}}
<form id="delete-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
// ===== Thêm hàng mới =====
let newTierIdx = 0;
const addBtn  = document.getElementById('btn-add-tier');
const tbody   = document.getElementById('tier-tbody');
const tpl     = document.getElementById('new-tier-tpl');

if (addBtn && tbody && tpl) {
    addBtn.addEventListener('click', () => {
        const clone = tpl.content.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/__IDX__/g, newTierIdx);
        });
        newTierIdx++;
        tbody.appendChild(clone);
        // Focus ô mã hạng của hàng vừa thêm
        tbody.lastElementChild?.querySelector('input')?.focus();
    });
}

// ===== Xóa hạng (xác nhận → submit form DELETE) =====
function confirmDelete(btn) {
    const nhan = btn.dataset.nhan;
    const url  = btn.dataset.url;
    if (!confirm('Xóa hạng "' + nhan + '"?\n\nThao tác này không thể hoàn tác.')) return;
    const form = document.getElementById('delete-form');
    form.action = url;
    form.submit();
}
</script>
@endsection
