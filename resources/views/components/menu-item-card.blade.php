@props(['mon', 'order'])
@php
    $imgUrl = $mon->image_url;
    $hetHang = $mon->trang_thai === 'het_hang';
    $displayOptions = [
        'temperature' => $mon->options?->where('loai_option', 'temperature')->where('trang_thai', 'active')->pluck('ten_option')->values()->all() ?? [],
        'sweetness' => $mon->options?->where('loai_option', 'sweetness')->where('trang_thai', 'active')->pluck('ten_option')->values()->all() ?? [],
        'toppings' => $mon->options?->where('loai_option', 'topping')->where('trang_thai', 'active')->pluck('ten_option')->values()->all() ?? [],
        // Bảng giá topping (tên => giá thêm) để cart cộng đúng tiền.
        'topping_prices' => $mon->options?->where('loai_option', 'topping')->where('trang_thai', 'active')->pluck('gia_them', 'ten_option')->all() ?? [],
    ];
    $optionText = mb_strtolower(($mon->ten_mon ?? '') . ' ' . ($mon->danhMuc?->ten_danh_muc ?? ''));
    if (str_contains($optionText, 'eats') || str_contains($optionText, 'bánh') || str_contains($optionText, 'hạt sen') || str_contains($optionText, 'đồ ăn')) {
        $displayOptions['temperature'] = [];
        $displayOptions['sweetness'] = [];
    }
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-[#522C25]/10 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#522C25]/10 {{ $hetHang ? 'opacity-70' : '' }}">
    <div class="relative aspect-[4/3] overflow-hidden bg-[#F2F2F2]">
        @if($imgUrl)
            <img src="{{ $imgUrl }}" alt="{{ $mon->ten_mon }}" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                 onerror="this.parentNode.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-stone-300\'><svg class=\'w-6 h-6\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z\' clip-rule=\'evenodd\'/></svg></div>'">
        @else
            <div class="flex h-full w-full items-center justify-center text-stone-300">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                </svg>
            </div>
        @endif

        @if($hetHang)
            <span class="absolute left-3 top-3 rounded-full bg-[#BB0011] px-3 py-1 text-xs font-semibold text-white shadow">
                Hết hàng
            </span>
        @endif

        @if($mon->model_3d_url)
            <button type="button"
                    data-model-url="{{ $mon->model_3d_url }}" data-model-name="{{ $mon->ten_mon }}"
                    onclick="window.viewMon3D(this.dataset.modelUrl, this.dataset.modelName)"
                    class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/55 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur transition hover:bg-black/80">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                Xem 3D
            </button>
        @endif
    </div>

    <div class="flex min-h-36 flex-1 flex-col p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="am-headline text-base font-semibold leading-tight text-[#1A1A1A]">
                    {{ $mon->ten_mon }}
                </p>
            </div>
            <p class="am-mono shrink-0 text-sm font-bold text-[#E82C2A]">
                {{ number_format($mon->don_gia, 0, ',', '.') }}đ
            </p>
        </div>

        @if($mon->mo_ta)
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-[#522C25]/65">{{ $mon->mo_ta }}</p>
        @endif

        <div class="mt-auto pt-4">
            @if(!$hetHang)
                <button @click="addToCart(@js([
                            'ma_mon' => $mon->ma_mon,
                            'ten_mon' => $mon->ten_mon,
                            'don_gia' => $mon->don_gia,
                            'mo_ta' => $mon->mo_ta,
                            'category' => $mon->danhMuc?->ten_danh_muc,
                            'image_url' => $imgUrl,
                            'options' => $displayOptions,
                        ]))"
                        class="am-headline flex w-full items-center justify-between rounded-full bg-[#E82C2A] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#BB0011] active:scale-[0.98]">
                    <span>Thêm món</span>
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">+</span>
                </button>
            @else
                <button disabled class="flex w-full cursor-not-allowed items-center justify-center rounded-full bg-[#F2F2F2] px-4 py-2.5 text-sm font-semibold text-[#522C25]/45">
                    Tạm hết hàng
                </button>
            @endif
        </div>
    </div>
</article>
