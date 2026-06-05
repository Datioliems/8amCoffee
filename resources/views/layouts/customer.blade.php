<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '8AM Coffee - Đặt món')</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo8am.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chivo:wght@500;600;700&family=EB+Garamond:wght@400;500;600&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FCFAFA] text-[#1A1A1A]" x-data="cart()">

<header class="sticky top-0 z-40 border-b border-[#522C25]/10 bg-[#FCFAFA]/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 md:px-6">
        <a href="#" class="flex items-center gap-3">
            <span class="am-brand-mark">
                <img src="{{ asset('images/logo8am-brand.png') }}" alt="8AM Coffee" class="h-11 w-11 rounded-full object-cover shadow-sm ring-1 ring-[#522C25]/10">
            </span>
            <div>
                <p class="am-headline font-semibold leading-none text-[#1A1A1A]">8am.coffee</p>
                <p class="am-mono mt-1 text-[10px] uppercase tracking-[0.16em] text-[#522C25]/60">quầy sáng</p>
            </div>
        </a>
        <button @click="openCart = true" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-[#1A1A1A] text-white shadow-sm transition active:scale-95" aria-label="Mở giỏ hàng">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span x-show="totalItems > 0"
                  x-text="totalItems"
                  class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-[#E82C2A] text-xs font-semibold text-white">
            </span>
        </button>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-5 md:px-6 md:py-8">
    @yield('content')
</main>

<div x-show="toastMessage"
     x-transition
     class="fixed left-4 right-4 top-20 z-50 mx-auto max-w-md rounded-full bg-[#1A1A1A] px-5 py-3 text-center text-sm font-semibold text-white shadow-xl"
     style="display: none;"
     x-text="toastMessage"></div>

<div x-show="showCustomizer" class="fixed inset-0 z-50 overflow-y-auto bg-[#FCFAFA]"
     x-transition
     style="display: none;">
    <template x-if="selectedMon">
        <section class="mx-auto min-h-screen max-w-md pb-28">
            <header class="sticky top-0 z-20 flex h-18 items-center justify-between border-b border-[#522C25]/10 bg-[#FCFAFA]/95 px-5 py-4 backdrop-blur">
                <button @click="closeCustomizer()" class="text-3xl leading-none text-[#BB0011]" aria-label="Quay lại">←</button>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo8am-brand.png') }}" alt="8AM Coffee" class="h-7 w-7 rounded-full object-cover">
                    <span class="am-headline text-2xl font-semibold">8am.cafe</span>
                </div>
                <span class="am-mono rounded-full bg-[#E82C2A] px-4 py-2 text-xs font-bold tracking-[0.14em] text-white">Bàn</span>
            </header>

            <div class="px-5 py-5">
                <div class="overflow-hidden rounded-[1.6rem] bg-[#F2F2F2]">
                    <img :src="selectedMon.image_url" :alt="selectedMon.ten_mon" class="h-72 w-full object-cover">
                </div>

                <div class="mt-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="am-headline text-3xl font-semibold leading-tight" x-text="selectedMon.ten_mon"></h2>
                        <p class="mt-3 text-lg leading-8 text-[#5D3F3C]" x-text="selectedMon.mo_ta || 'Món đặc trưng của 8am.cafe.'"></p>
                    </div>
                    <p class="am-mono shrink-0 text-lg font-bold text-[#522C25]" x-text="formatPrice(selectedMon.don_gia)"></p>
                </div>

                <div class="mt-7 space-y-2 rounded-3xl bg-[#FFF4F2] p-4 ring-1 ring-[#E82C2A]/15">
                    <p class="am-mono text-xs uppercase tracking-[0.16em] text-[#BB0011]">Lưu ý thành phần</p>
                    <template x-for="warning in selectedMon.warnings" :key="warning">
                        <p class="text-sm leading-6 text-[#5D3F3C]" x-text="warning"></p>
                    </template>
                </div>

                <section class="mt-10" x-show="selectedMon.options.temperature.length">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="am-headline text-3xl font-semibold">Nhiệt độ</h3>
                        <span class="am-mono text-xs tracking-[0.16em] text-[#E82C2A]">Bắt buộc</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="option in selectedMon.options.temperature" :key="option">
                            <button @click="selectedOptions.temperature = option"
                                    :class="selectedOptions.temperature === option ? 'bg-[#E82C2A] text-white' : 'bg-[#F2F2F2] text-[#1A1A1A]'"
                                    class="am-mono rounded-[1.4rem] px-4 py-5 text-sm transition" x-text="option"></button>
                        </template>
                    </div>
                </section>

                <section class="mt-10" x-show="selectedMon.options.sweetness.length">
                    <h3 class="am-headline mb-4 text-3xl font-semibold">Độ ngọt</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="option in selectedMon.options.sweetness" :key="option">
                            <button @click="selectedOptions.sweetness = option"
                                    :class="selectedOptions.sweetness === option ? 'bg-[#E82C2A] text-white' : 'bg-[#F2F2F2] text-[#1A1A1A]'"
                                    class="am-mono rounded-[1.4rem] px-4 py-5 text-sm transition" x-text="option"></button>
                        </template>
                    </div>
                </section>

                <section class="mt-10" x-show="selectedMon.options.toppings.length">
                    <h3 class="am-headline mb-4 text-3xl font-semibold">Topping thêm</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="option in selectedMon.options.toppings" :key="option">
                            <button @click="toggleTopping(option)"
                                    :class="selectedOptions.toppings.includes(option) ? 'bg-[#E82C2A] text-white' : 'bg-[#F2F2F2] text-[#1A1A1A]'"
                                    class="am-mono rounded-[1.4rem] px-4 py-4 text-sm transition" x-text="option"></button>
                        </template>
                    </div>
                </section>

                <section class="mt-10">
                    <h3 class="am-headline mb-4 text-3xl font-semibold">Ghi chú thêm</h3>
                    <textarea x-model="selectedOptions.note"
                              class="w-full rounded-[1.6rem] border-0 bg-[#F2F2F2] px-5 py-5 text-base text-[#1A1A1A] placeholder:text-[#5D3F3C]/50 focus:ring-2 focus:ring-[#E82C2A]"
                              rows="4"
                              placeholder="VD: nóng hơn, ít bọt..."></textarea>
                </section>

                <section class="mt-8 flex items-center justify-between rounded-[1.6rem] bg-[#F2F2F2] p-5">
                    <h3 class="am-headline text-2xl font-semibold">Số lượng</h3>
                    <div class="flex items-center gap-5 rounded-full bg-white px-5 py-3 shadow-sm">
                        <button @click="selectedOptions.qty = Math.max(1, selectedOptions.qty - 1)" class="text-2xl">−</button>
                        <span class="am-mono text-lg font-bold" x-text="selectedOptions.qty"></span>
                        <button @click="selectedOptions.qty++" class="text-2xl">+</button>
                    </div>
                </section>
            </div>

            <footer class="fixed bottom-0 left-0 right-0 z-20 mx-auto max-w-md rounded-t-[1.6rem] bg-[#E82C2A] px-5 py-4 text-white">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="am-mono text-xs">Tổng tiền</p>
                        <p class="am-mono text-2xl font-bold" x-text="formatPrice(selectedMon.don_gia * selectedOptions.qty)"></p>
                    </div>
                    <button @click="addCustomizedToCart()" class="am-headline rounded-full bg-white px-8 py-4 text-xl font-semibold text-[#E82C2A] shadow">
                        Thêm vào giỏ
                    </button>
                </div>
            </footer>
        </section>
    </template>
</div>

<div x-show="openCart" class="fixed inset-0 z-50 flex justify-end"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     style="display: none;">
    <div @click="openCart = false" class="absolute inset-0 bg-[#1A1A1A]/45"></div>
    <aside class="relative flex h-full w-full max-w-sm flex-col overflow-y-auto bg-[#FCFAFA] p-5 shadow-2xl">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-[#522C25]/60">Giỏ hàng</p>
                <h2 class="mt-1 text-xl font-semibold text-[#1A1A1A]">Món đã chọn</h2>
            </div>
            <button @click="openCart = false" class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#522C25] ring-1 ring-[#522C25]/10" aria-label="Đóng giỏ hàng">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <template x-if="items.length === 0">
            <div class="rounded-2xl border border-dashed border-[#522C25]/20 bg-white p-8 text-center text-sm text-[#522C25]/60">
                Chưa có món nào trong giỏ.
            </div>
        </template>

        <div class="space-y-3">
            <template x-for="item in items" :key="item.variantKey || item.ma_mon">
                <div class="rounded-2xl bg-white p-4 ring-1 ring-[#522C25]/10">
                    <div>
                        <p class="text-sm font-semibold text-[#1A1A1A]" x-text="item.ten_mon"></p>
                        <p class="mt-1 text-xs text-[#522C25]/60" x-text="formatPrice(item.don_gia)"></p>
                        <p x-show="item.ghi_chu" class="mt-2 text-xs leading-5 text-[#522C25]/70" x-text="item.ghi_chu"></p>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 rounded-full bg-[#F2F2F2] p-1">
                            <button @click="decrement(item.variantKey || item.ma_mon)" class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#522C25]">-</button>
                            <span class="w-6 text-center text-sm font-semibold" x-text="item.qty"></span>
                            <button @click="increment(item.variantKey || item.ma_mon)" class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#522C25]">+</button>
                        </div>
                        <p class="font-semibold text-[#E82C2A]" x-text="formatPrice(item.don_gia * item.qty)"></p>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="items.length > 0" class="mt-auto pt-6">
            <div class="mb-4 flex justify-between rounded-2xl bg-[#1A1A1A] px-4 py-3 text-white">
                <span class="text-sm text-white/70">Tổng cộng</span>
                <span class="font-semibold" x-text="formatPrice(totalPrice)"></span>
            </div>
            <p x-show="checkoutError" x-text="checkoutError" class="mb-3 text-xs text-[#BB0011]"></p>
            <button type="button" @click="checkout()"
                    class="block w-full rounded-full bg-[#E82C2A] py-3 text-center text-sm font-semibold text-white shadow-sm transition active:scale-[0.98]">
                Xác nhận giỏ hàng
            </button>
        </div>
    </aside>
</div>

</body>
</html>
