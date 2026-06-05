@extends('layouts.app')
@section('title', 'Quản lý topping - 8AM')
@section('page-title', 'Quản lý topping')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    @if(session('success'))
    <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-disc space-y-0.5 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Form thêm topping --}}
    <div class="rounded-3xl border border-[#522C25]/10 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold">Thêm topping mới</h2>
        <form method="POST" action="{{ route('menu.toppings.store') }}" class="grid grid-cols-1 gap-4 md:grid-cols-[2fr_1fr_2fr_auto]">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Tên topping</label>
                <input name="ten_topping" value="{{ old('ten_topping') }}" required
                       class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm" placeholder="Trân châu, Kem mặn…">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Giá thêm (đ)</label>
                <input name="gia_them" type="number" min="0" value="{{ old('gia_them', 0) }}" required
                       class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Cảnh báo (tùy chọn)</label>
                <input name="canh_bao" value="{{ old('canh_bao') }}"
                       class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm" placeholder="Có sữa, hạt…">
            </div>
            <div class="flex items-end">
                <button class="rounded-xl bg-[#1A1A1A] px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">+ Thêm</button>
            </div>
        </form>
    </div>

    {{-- Danh sách topping --}}
    <div class="rounded-3xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="border-b border-[#522C25]/10 px-6 py-4">
            <h2 class="text-base font-semibold">Danh sách topping ({{ $toppings->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Mã</th>
                        <th class="px-4 py-3">Tên</th>
                        <th class="px-4 py-3">Giá thêm</th>
                        <th class="px-4 py-3">Cảnh báo</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @forelse($toppings as $t)
                    <tr>
                        <form method="POST" action="{{ route('menu.toppings.update', $t->ma_topping) }}" class="contents">
                            @csrf @method('PUT')
                            <td class="px-4 py-3 font-mono text-xs text-[#522C25]/50">{{ $t->ma_topping }}</td>
                            <td class="px-4 py-3"><input name="ten_topping" value="{{ $t->ten_topping }}" class="w-40 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm"></td>
                            <td class="px-4 py-3"><input name="gia_them" type="number" min="0" value="{{ (int) $t->gia_them }}" class="w-24 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm"></td>
                            <td class="px-4 py-3"><input name="canh_bao" value="{{ $t->canh_bao }}" class="w-40 rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-sm" placeholder="—"></td>
                            <td class="px-4 py-3">
                                <select name="trang_thai" class="rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-xs">
                                    <option value="active"   @selected($t->trang_thai==='active')>Đang bán</option>
                                    <option value="inactive" @selected($t->trang_thai!=='active')>Ẩn</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button class="rounded-lg bg-[#1A1A1A] px-3 py-1.5 text-xs font-semibold text-white hover:bg-black">Lưu</button>
                            </td>
                        </form>
                    </tr>
                    <tr>
                        <td colspan="6" class="px-4 pb-2 text-right">
                            <form method="POST" action="{{ route('menu.toppings.destroy', $t->ma_topping) }}" onsubmit="return confirm('Xóa topping {{ $t->ten_topping }}?');">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-[#BB0011] hover:underline">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center text-[#522C25]/55">Chưa có topping nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
