@extends('layouts.app')

@section('title', isset($mon) ? 'Sửa món' : 'Thêm món')
@section('page-title', isset($mon) ? 'Sửa món' : 'Thêm món')

@section('content')
@php
    $oldImage = old('hinh_anh');
    $previewUrl = $oldImage
        ? (str_starts_with($oldImage, 'http') || str_starts_with($oldImage, '/') ? $oldImage : asset('images/' . $oldImage))
        : ($mon->image_url ?? null);
    $selectedTemperatures = old('temperature_options', isset($mon)
        ? $mon->options->where('loai_option', 'temperature')->where('trang_thai', 'active')->pluck('ten_option')->all()
        : ['Đá', 'Nóng', 'Ít đá']);
    $selectedToppings = old('topping_options', isset($mon)
        ? $mon->options->where('loai_option', 'topping')->where('trang_thai', 'active')->pluck('ten_option')->all()
        : []);
    $selectedSweetness = old('sweetness_options', isset($mon)
        ? $mon->options->where('loai_option', 'sweetness')->where('trang_thai', 'active')->pluck('ten_option')->all()
        : $sweetnessOptions ?? []);
@endphp

<div class="max-w-6xl">
    <form method="POST"
          action="{{ isset($mon) ? route('menu.update', $mon->ma_mon) : route('menu.store') }}"
          enctype="multipart/form-data"
          class="grid items-stretch gap-6 lg:grid-cols-2">
        @csrf
        @if(isset($mon))
            @method('PUT')
        @endif

        <div class="h-full space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Tên món</label>
                <input type="text" name="ten_mon" value="{{ old('ten_mon', $mon->ten_mon ?? '') }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                @error('ten_mon') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Danh mục</label>
                    <select name="ma_danh_muc"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                        @foreach($danhMucs as $danhMuc)
                        <option value="{{ $danhMuc->ma_danh_muc }}" {{ old('ma_danh_muc', $mon->ma_danh_muc ?? '') === $danhMuc->ma_danh_muc ? 'selected' : '' }}>
                            {{ $danhMuc->ten_danh_muc }}
                        </option>
                        @endforeach
                    </select>
                    @error('ma_danh_muc') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Đơn giá</label>
                    <input type="number" name="don_gia" min="1000" step="1000"
                           value="{{ old('don_gia', $mon->don_gia ?? '') }}"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    @error('don_gia') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Mô tả</label>
                <textarea name="mo_ta" rows="3"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">{{ old('mo_ta', $mon->mo_ta ?? '') }}</textarea>
                @error('mo_ta') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <input type="hidden" name="hinh_anh" id="hinh_anh" value="{{ old('hinh_anh', $mon->hinh_anh ?? '') }}">

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Tải ảnh mới</label>
                <input type="file" name="hinh_anh_file" id="hinh_anh_file" accept="image/jpeg,image/png,image/webp"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-amber-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-amber-700 hover:file:bg-amber-100">
                <p class="mt-1 text-xs text-gray-400">JPG, PNG hoặc WebP tối đa 4MB. Khi upload, hệ thống sẽ lưu vào public/images/menu.</p>
                @error('hinh_anh_file') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-800">Tùy chọn hiển thị cho khách</p>
                <div class="mt-4">
                    <p class="mb-2 text-xs font-semibold uppercase text-gray-500">Nhiệt độ</p>
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
                        @foreach($temperatureOptions as $option)
                            <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm text-gray-700 ring-1 ring-gray-200">
                                <input type="checkbox" name="temperature_options[]" value="{{ $option }}"
                                       class="rounded border-gray-300 text-amber-500 focus:ring-amber-300"
                                       {{ in_array($option, $selectedTemperatures, true) ? 'checked' : '' }}>
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4">
                    <p class="mb-2 text-xs font-semibold uppercase text-gray-500">Độ ngọt</p>
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                        @foreach($sweetnessOptions as $option)
                            <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm text-gray-700 ring-1 ring-gray-200">
                                <input type="checkbox" name="sweetness_options[]" value="{{ $option }}"
                                       class="rounded border-gray-300 text-amber-500 focus:ring-amber-300"
                                       {{ in_array($option, $selectedSweetness, true) ? 'checked' : '' }}>
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4">
                    <p class="mb-2 text-xs font-semibold uppercase text-gray-500">Topping thêm</p>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                        @foreach($toppings as $topping)
                            <label class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-sm text-gray-700 ring-1 ring-gray-200">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="topping_options[]" value="{{ $topping->ma_topping }}"
                                           class="rounded border-gray-300 text-amber-500 focus:ring-amber-300"
                                           {{ in_array($topping->ten_topping, $selectedToppings, true) || in_array($topping->ma_topping, $selectedToppings, true) ? 'checked' : '' }}>
                                    <span>{{ $topping->ten_topping }}</span>
                                </span>
                                <span class="text-xs text-gray-400">{{ number_format($topping->gia_them, 0, ',', '.') }}đ</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Trạng thái</label>
                <select name="trang_thai"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    @foreach(['active' => 'Đang bán', 'het_hang' => 'Hết hàng', 'an' => 'Ẩn'] as $value => $label)
                    <option value="{{ $value }}" {{ old('trang_thai', $mon->trang_thai ?? 'active') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('trang_thai') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

        </div>

        <aside class="flex h-full flex-col rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <p class="mb-3 text-sm font-medium text-gray-700">Ảnh thực đơn</p>
            <div class="min-h-80 flex-1 overflow-hidden rounded-xl bg-stone-100">
                @if($previewUrl)
                    <img id="image-preview" src="{{ $previewUrl }}" alt="{{ $mon->ten_mon ?? 'Xem trước' }}" class="h-full w-full object-cover">
                    <div id="image-empty" class="hidden h-full w-full items-center justify-center text-sm text-stone-400">Chưa có ảnh</div>
                @else
                    <div id="image-empty" class="flex h-full w-full items-center justify-center text-sm text-stone-400">Chưa có ảnh</div>
                    <img id="image-preview" src="" alt="Xem trước" class="hidden h-full w-full object-cover">
                @endif
            </div>
            <p class="mt-3 break-all text-xs text-gray-400" id="image-path">{{ $previewUrl ? 'Đang dùng ảnh thực đơn' : 'Chưa có ảnh' }}</p>
        </aside>

        <section class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800">Định lượng nguyên liệu</p>
                    <p class="mt-1 text-xs text-gray-500">Kết nối món với nguyên liệu trong kho. Khi đơn được xác nhận, tồn kho sẽ trừ theo định lượng này.</p>
                </div>
                <button type="button" id="add-ingredient-row"
                        class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-xs font-semibold text-white">
                    Thêm nguyên liệu
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2 text-left">Nguyên liệu</th>
                            <th class="px-3 py-2 text-left">Định lượng dùng</th>
                            <th class="px-3 py-2 text-left">Tồn kho chi nhánh</th>
                            <th class="px-3 py-2 text-left">Ghi chú</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody id="ingredient-rows" class="divide-y divide-gray-50">
                        @foreach($dinhMucRows as $index => $row)
                        <tr class="ingredient-row">
                            <td class="px-3 py-2">
                                <select name="dinh_muc[{{ $index }}][ma_nl]"
                                        class="ingredient-select w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <option value="">Chọn nguyên liệu</option>
                                    @foreach($nguyenLieus as $nguyenLieu)
                                        @php
                                            $tonKho = $nguyenLieu->tonKhos->first();
                                            $stockText = $tonKho ? number_format($tonKho->sl_ton_kho_he_thong, 2, ',', '.') . ' ' . $nguyenLieu->don_vi : 'Chưa có tồn';
                                        @endphp
                                        <option value="{{ $nguyenLieu->ma_nl }}"
                                                data-unit="{{ $nguyenLieu->don_vi }}"
                                                data-stock="{{ $stockText }}"
                                                {{ ($row['ma_nl'] ?? '') === $nguyenLieu->ma_nl ? 'selected' : '' }}>
                                            {{ $nguyenLieu->ten_nl }} ({{ $nguyenLieu->don_vi }})
                                        </option>
                                    @endforeach
                                </select>
                                @error("dinh_muc.$index.ma_nl") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <input type="number" name="dinh_muc[{{ $index }}][so_luong_dung]"
                                           min="0.01" step="0.01"
                                           value="{{ $row['so_luong_dung'] ?? '' }}"
                                           class="w-28 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <span class="ingredient-unit text-xs text-gray-500"></span>
                                </div>
                                @error("dinh_muc.$index.so_luong_dung") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </td>
                            <td class="ingredient-stock px-3 py-2 text-xs text-gray-500">—</td>
                            <td class="px-3 py-2">
                                <input type="text" name="dinh_muc[{{ $index }}][mo_ta]"
                                       value="{{ $row['mo_ta'] ?? '' }}"
                                       placeholder="VD: shot nền, sữa, syrup..."
                                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                @error("dinh_muc.$index.mo_ta") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" class="remove-ingredient-row rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-600">Xóa</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex items-center gap-3 lg:col-span-2">
            <button type="submit" class="rounded-lg bg-amber-500 px-5 py-2 text-sm font-medium text-white transition hover:bg-amber-600">
                {{ isset($mon) ? 'Cập nhật' : 'Thêm mới' }}
            </button>
            <a href="{{ route('menu.index') }}" class="px-5 py-2 text-sm text-gray-500 hover:text-gray-700">Hủy</a>
        </div>
    </form>
</div>

<template id="ingredient-row-template">
    <tr class="ingredient-row">
        <td class="px-3 py-2">
            <select data-name="ma_nl" class="ingredient-select w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">Chọn nguyên liệu</option>
                @foreach($nguyenLieus as $nguyenLieu)
                    @php
                        $tonKho = $nguyenLieu->tonKhos->first();
                        $stockText = $tonKho ? number_format($tonKho->sl_ton_kho_he_thong, 2, ',', '.') . ' ' . $nguyenLieu->don_vi : 'Chưa có tồn';
                    @endphp
                    <option value="{{ $nguyenLieu->ma_nl }}" data-unit="{{ $nguyenLieu->don_vi }}" data-stock="{{ $stockText }}">
                        {{ $nguyenLieu->ten_nl }} ({{ $nguyenLieu->don_vi }})
                    </option>
                @endforeach
            </select>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center gap-2">
                <input data-name="so_luong_dung" type="number" min="0.01" step="0.01"
                       class="w-28 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <span class="ingredient-unit text-xs text-gray-500"></span>
            </div>
        </td>
        <td class="ingredient-stock px-3 py-2 text-xs text-gray-500">—</td>
        <td class="px-3 py-2">
            <input data-name="mo_ta" type="text" placeholder="VD: shot nền, sữa, syrup..."
                   class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </td>
        <td class="px-3 py-2 text-right">
            <button type="button" class="remove-ingredient-row rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-600">Xóa</button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pathInput = document.getElementById('hinh_anh');
    const fileInput = document.getElementById('hinh_anh_file');
    const preview = document.getElementById('image-preview');
    const empty = document.getElementById('image-empty');
    const pathLabel = document.getElementById('image-path');

    const showPreview = (src, label) => {
        preview.src = src;
        preview.classList.remove('hidden');
        empty?.classList.add('hidden');
        pathLabel.textContent = label || src;
    };

    pathInput?.addEventListener('input', () => {
        const value = pathInput.value.trim();
        if (!value) {
            pathLabel.textContent = 'Chưa có đường dẫn';
            return;
        }
        const src = value.startsWith('http') || value.startsWith('/') ? value : `{{ asset('images') }}/${value}`;
        showPreview(src, value);
    });

    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (!file) return;
        showPreview(URL.createObjectURL(file), file.name);
    });

    const tbody = document.getElementById('ingredient-rows');
    const template = document.getElementById('ingredient-row-template');
    const addButton = document.getElementById('add-ingredient-row');

    const refreshIngredientRows = () => {
        tbody.querySelectorAll('.ingredient-row').forEach((row, index) => {
            row.querySelectorAll('[data-name]').forEach((input) => {
                input.name = `dinh_muc[${index}][${input.dataset.name}]`;
            });
            row.querySelectorAll('[name^="dinh_muc"]').forEach((input) => {
                input.name = input.name.replace(/dinh_muc\[\d+\]/, `dinh_muc[${index}]`);
            });

            const select = row.querySelector('.ingredient-select');
            const selected = select?.selectedOptions?.[0];
            row.querySelector('.ingredient-unit').textContent = selected?.dataset.unit || '';
            row.querySelector('.ingredient-stock').textContent = selected?.dataset.stock || '—';
        });
    };

    addButton?.addEventListener('click', () => {
        tbody.appendChild(template.content.cloneNode(true));
        refreshIngredientRows();
    });

    tbody?.addEventListener('change', (event) => {
        if (event.target.classList.contains('ingredient-select')) {
            refreshIngredientRows();
        }
    });

    tbody?.addEventListener('click', (event) => {
        if (!event.target.classList.contains('remove-ingredient-row')) return;
        const rows = tbody.querySelectorAll('.ingredient-row');
        if (rows.length === 1) {
            const row = rows[0];
            row.querySelectorAll('input').forEach((input) => input.value = '');
            row.querySelector('select').value = '';
        } else {
            event.target.closest('.ingredient-row').remove();
        }
        refreshIngredientRows();
    });

    refreshIngredientRows();
});
</script>
@endsection
