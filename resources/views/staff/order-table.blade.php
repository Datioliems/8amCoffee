@extends('layouts.app')
@section('page-title', 'Bàn ' . $ban->so_ban . ' · Thêm đơn hàng')

@section('content')
@php
    $monsByCat = $mons->groupBy(fn($m) => $m->danhMuc->ten_danh_muc ?? 'Khác');
    $cats = $monsByCat->keys()->values();
    $toppingData = $toppings->map(fn($t) => ['value' => $t->ten_topping, 'price' => (int) $t->gia_them])->values();
@endphp

<div class="mx-auto max-w-6xl">

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{{ $errors->first() }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <div>
            <p class="text-xs uppercase tracking-[0.18em] text-[#522C25]/55">{{ $openOrder ? 'Sửa đơn tại bàn' : 'Thêm đơn hàng' }}</p>
            <h2 class="mt-1 text-2xl font-semibold">Bàn {{ $ban->so_ban }}
                <span class="text-sm font-normal text-[#522C25]/55">· {{ $ban->vi_tri ?: 'Khác' }} · {{ $ban->so_ghe ?? 4 }} ghế</span>
            </h2>
            @if($openOrder)
            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                Đang sửa đơn #{{ $openOrder->ma_order }} · {{ \App\Services\OrderService::statusLabel($openOrder->trang_thai) }}
            </span>
            @endif
        </div>
        <a href="{{ route('orders.index') }}" class="rounded-full bg-[#F2F2F2] px-5 py-2 text-sm font-semibold text-[#522C25] hover:bg-[#E9DDD0]">Hủy</a>
    </div>

    <div x-data="tableOrder(@js($toppingData), @js($cartSeed), @js($cust), @js($hinhThuc))">
        <div class="grid items-start gap-5 lg:grid-cols-[1fr_380px]">

            {{-- CỘT TRÁI --}}
            <div class="space-y-5">
                <section class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10">
                    <h3 class="mb-4 text-sm font-bold uppercase tracking-[0.14em] text-[#522C25]/70">Thông tin khách hàng</h3>
                    <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-[#522C25]/60">Tên khách hàng</span>
                            <input x-model="cust.ten" placeholder="Nhập tên KH..."
                                   class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm focus:border-[#E82C2A] focus:ring-[#E82C2A]">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-[#522C25]/60">Số điện thoại</span>
                            <input x-model="cust.sdt" @input="cust.sdt = cust.sdt.replace(/[^0-9]/g,'').slice(0,10)"
                                   inputmode="numeric" placeholder="Nhập SĐT..."
                                   class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm focus:border-[#E82C2A] focus:ring-[#E82C2A]">
                        </label>
                        <div x-show="cust.ten || cust.sdt" x-cloak class="flex items-center gap-2 rounded-xl bg-[#FDECEA] px-3 py-2 ring-1 ring-[#522C25]/10">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold" x-text="cust.ten || 'Khách'"></p>
                                <p class="text-xs text-[#522C25]/55" x-text="cust.sdt"></p>
                            </div>
                            <button type="button" @click="cust.ten=''; cust.sdt=''" class="text-[#522C25]/50 hover:text-[#BB0011]">&times;</button>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10">
                    <h3 class="mb-3 text-sm font-bold uppercase tracking-[0.14em] text-[#522C25]/70">Lựa chọn sản phẩm</h3>
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach($cats as $i => $catName)
                        <button type="button" @click="cat = {{ $i }}"
                                class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                                :class="cat === {{ $i }} ? 'bg-[#E82C2A] text-white' : 'bg-[#FBEAE9] text-[#BB0011] hover:bg-[#F7D9D6]'">
                            {{ $catName }}
                        </button>
                        @endforeach
                    </div>

                    @foreach($cats as $i => $catName)
                    @php $isDrink = strcasecmp($catName, 'Eats') !== 0; @endphp
                    <div x-show="cat === {{ $i }}" x-cloak class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach($monsByCat[$catName] as $mon)
                        <div class="flex flex-col overflow-hidden rounded-2xl ring-1 ring-[#522C25]/10">
                            <div class="relative aspect-[4/3] w-full overflow-hidden bg-[#F2F2F2]">
                                @if($mon->image_url)<img src="{{ $mon->image_url }}" alt="{{ $mon->ten_mon }}" class="h-full w-full object-cover" loading="lazy">@endif
                                @if($mon->model_3d_url)
                                <button type="button"
                                        data-model-url="{{ $mon->model_3d_url }}" data-model-name="{{ $mon->ten_mon }}"
                                        onclick="window.viewMon3D(this.dataset.modelUrl, this.dataset.modelName)"
                                        class="absolute right-2 top-2 rounded-full bg-black/55 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur hover:bg-black/80">3D</button>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col p-3">
                                <p class="line-clamp-2 text-sm font-semibold text-[#1A1A1A]">{{ $mon->ten_mon }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-sm font-bold text-[#E82C2A]">{{ number_format($mon->don_gia, 0, ',', '.') }}đ</span>
                                    <button type="button"
                                            @click="openAdd('{{ $mon->ma_mon }}', @js($mon->ten_mon), {{ (int) $mon->don_gia }}, {{ $isDrink ? 'true' : 'false' }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-[#E82C2A] text-lg font-bold text-white transition hover:bg-[#BB0011] active:scale-90">+</button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </section>
            </div>

            {{-- CỘT PHẢI: giỏ hàng + form --}}
            <form method="POST" action="{{ route('orders.table.store', $ban->ma_ban) }}"
                  @submit="if (items.length === 0) { $event.preventDefault(); alert('Vui lòng chọn ít nhất một món.'); }"
                  class="rounded-2xl bg-white p-5 ring-1 ring-[#522C25]/10 lg:sticky lg:top-6">
                @csrf
                <input type="hidden" name="ten_kh"    :value="cust.ten">
                <input type="hidden" name="sdt_kh"    :value="cust.sdt">
                <input type="hidden" name="hinh_thuc" :value="hinhThuc">
                <input type="hidden" name="action"    :value="act">
                <template x-for="(it, idx) in items" :key="it.key">
                    <div>
                        <input type="hidden" :name="`items[${idx}][ma_mon]`"   :value="it.ma_mon">
                        <input type="hidden" :name="`items[${idx}][so_luong]`" :value="it.qty">
                        <input type="hidden" :name="`items[${idx}][ghi_chu]`"  :value="it.ghi_chu">
                        <template x-for="(op, oi) in optionsOf(it)" :key="oi">
                            <span>
                                <input type="hidden" :name="`items[${idx}][options][${oi}][type]`"  :value="op.type">
                                <input type="hidden" :name="`items[${idx}][options][${oi}][value]`" :value="op.value">
                                <input type="hidden" :name="`items[${idx}][options][${oi}][price]`" :value="op.price">
                            </span>
                        </template>
                    </div>
                </template>

                <h3 class="mb-4 text-sm font-bold uppercase tracking-[0.14em] text-[#522C25]/70">Chi tiết đơn hàng</h3>
                <p x-show="items.length === 0" class="py-6 text-center text-sm text-[#522C25]/45">Chưa chọn món nào.</p>

                <div class="max-h-[300px] space-y-3 overflow-y-auto pr-1" x-show="items.length > 0" x-cloak>
                    <template x-for="it in items" :key="it.key">
                        <div class="border-b border-[#522C25]/8 pb-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#1A1A1A]" x-text="it.ten"></p>
                                    <p class="text-[11px] text-[#522C25]/55" x-show="descLine(it)" x-text="descLine(it)"></p>
                                    <p class="text-[11px] italic text-[#522C25]/45" x-show="it.ghi_chu" x-text="it.ghi_chu"></p>
                                </div>
                                <button type="button" @click="remove(it.key)" class="text-[#BB0011]/70 hover:text-[#BB0011]">&times;</button>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="dec(it.key)" class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#F2F2F2] text-base font-bold">−</button>
                                    <span class="w-6 text-center text-sm font-semibold" x-text="it.qty"></span>
                                    <button type="button" @click="inc(it.key)" class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#E82C2A] text-base font-bold text-white">+</button>
                                </div>
                                <span class="text-sm font-semibold text-[#1A1A1A]" x-text="money(lineTotal(it))"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4">
                    <p class="mb-2 text-xs font-semibold text-[#522C25]/60">Hình thức phục vụ</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="hinhThuc = 'tai_ban'"
                                class="rounded-xl border py-2 text-sm font-medium transition"
                                :class="hinhThuc === 'tai_ban' ? 'border-[#52613B] bg-[#E8F0DD] text-[#3f5325]' : 'border-[#522C25]/15 bg-white text-[#522C25]'">Uống tại bàn</button>
                        <button type="button" @click="hinhThuc = 'mang_ve'"
                                class="rounded-xl border py-2 text-sm font-medium transition"
                                :class="hinhThuc === 'mang_ve' ? 'border-[#9a3412] bg-[#FFE3D6] text-[#9a3412]' : 'border-[#522C25]/15 bg-white text-[#522C25]'">Mang về (cốc nhựa)</button>
                    </div>
                </div>

                <div class="mt-4 space-y-1 border-t border-[#522C25]/10 pt-4 text-sm">
                    <div class="flex items-center justify-between text-[#522C25]/70">
                        <span>Tạm tính (<span x-text="count"></span> món)</span>
                        <span x-text="money(subtotal)"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <span class="font-semibold">Tổng tạm tính</span>
                        <span class="text-xl font-bold text-[#E82C2A]" x-text="money(subtotal)"></span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="submit" @click="act='confirm'"
                            class="rounded-xl bg-[#1A1A1A] py-3 text-sm font-semibold text-white transition hover:bg-[#522C25] active:scale-[0.99]">{{ $openOrder ? 'Cập nhật & xác nhận' : 'Xác nhận đơn' }}</button>
                    <button type="submit" @click="act='pay'"
                            class="rounded-xl bg-[#52613B] py-3 text-sm font-semibold text-white transition hover:bg-[#445230] active:scale-[0.99]">Thanh toán</button>
                </div>
                <p class="mt-2 text-center text-[11px] text-[#522C25]/45">“Xác nhận” → đơn vào trạng thái đã xác nhận &amp; quay về danh sách đơn. “Thanh toán” → sang trang thu tiền.</p>
            </form>
        </div>

        {{-- ── POPUP tùy chọn món (nóng/lạnh, topping, ghi chú) ──── --}}
        <div x-show="showModal" x-cloak @keydown.escape.window="showModal=false"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="showModal=false">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" x-show="showModal" x-transition>
                <div class="mb-4 flex items-start justify-between">
                    <h3 class="text-lg font-semibold" x-text="draft ? draft.ten : ''"></h3>
                    <button type="button" @click="showModal=false" class="text-2xl leading-none text-[#522C25]/50 hover:text-[#BB0011]">&times;</button>
                </div>

                <template x-if="draft">
                    <div class="space-y-4">
                        <div>
                            <p class="mb-2 text-xs font-semibold text-[#522C25]/60">Nóng / Lạnh</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="draft.temp='Nóng'" class="rounded-xl border py-2 text-sm font-medium"
                                        :class="draft.temp==='Nóng' ? 'border-[#9a3412] bg-[#FFE3D6] text-[#9a3412]' : 'border-[#522C25]/15'">Nóng</button>
                                <button type="button" @click="draft.temp='Lạnh'" class="rounded-xl border py-2 text-sm font-medium"
                                        :class="draft.temp==='Lạnh' ? 'border-[#64748B] bg-[#F1F5F9] text-[#475569]' : 'border-[#522C25]/15'">Lạnh</button>
                            </div>
                        </div>

                        <div x-show="toppings.length">
                            <p class="mb-2 text-xs font-semibold text-[#522C25]/60">Topping</p>
                            <div class="max-h-40 space-y-1 overflow-y-auto">
                                <template x-for="(t, i) in toppings" :key="i">
                                    <label class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-[#F8F6F5]">
                                        <span class="flex items-center gap-2">
                                            <input type="checkbox" x-model="draft.tops[i]" class="rounded border-[#522C25]/30 text-[#E82C2A]">
                                            <span x-text="t.value"></span>
                                        </span>
                                        <span class="text-[#522C25]/55" x-text="'+' + money(t.price)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-semibold text-[#522C25]/60">Ghi chú</p>
                            <textarea x-model="draft.ghi_chu" rows="2" placeholder="Ví dụ: ít đường, nhiều đá..."
                                      class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm focus:border-[#E82C2A] focus:ring-[#E82C2A]"></textarea>
                        </div>

                        <button type="button" @click="confirmDraft()"
                                class="w-full rounded-xl bg-[#E82C2A] py-3 text-sm font-semibold text-white hover:bg-[#BB0011]">Thêm vào đơn</button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function tableOrder(toppings, seed, cust, hinhThuc) {
    // Khóa dòng theo CẤU HÌNH (món + nóng/lạnh + topping + ghi chú):
    // cùng món nhưng khác topping/nhiệt độ/ghi chú => DÒNG RIÊNG.
    const keyOf = (ma, temp, tops, note) => {
        const t = (tops || []).map(x => x.value).sort().join(',');
        return ma + '|' + (temp || '') + '|' + t + '|' + (note || '');
    };
    const cart = {};
    (seed || []).forEach(it => {
        const k = keyOf(it.ma_mon, it.temp, it.toppings, it.ghi_chu);
        cart[k] = {
            key: k, ma_mon: it.ma_mon, ten: it.ten, gia: it.gia, qty: it.qty,
            temp: it.temp || '', toppings: it.toppings || [], ghi_chu: it.ghi_chu || ''
        };
    });
    return {
        cart: cart, cat: 0, act: 'confirm',
        keyOf: keyOf,
        hinhThuc: hinhThuc || 'tai_ban',
        cust: { ten: (cust && cust.ten) || '', sdt: (cust && cust.sdt) || '' },
        toppings: toppings || [],
        showModal: false, draft: null,
        openAdd(ma, ten, gia, isDrink) {
            if (!isDrink) { this.push(ma, ten, gia, '', [], ''); return; }
            this.draft = { ma_mon: ma, ten: ten, gia: gia, temp: 'Nóng', tops: {}, ghi_chu: '' };
            this.showModal = true;
        },
        confirmDraft() {
            const d = this.draft;
            const tops = this.toppings.filter((t, i) => d.tops[i]).map(t => ({ value: t.value, price: t.price }));
            this.push(d.ma_mon, d.ten, d.gia, d.temp, tops, d.ghi_chu);
            this.showModal = false; this.draft = null;
        },
        push(ma, ten, gia, temp, tops, note) {
            const k = this.keyOf(ma, temp, tops, note);
            if (this.cart[k]) {
                this.cart[k].qty++;
            } else {
                this.cart[k] = { key: k, ma_mon: ma, ten: ten, gia: gia, qty: 1, temp: temp, toppings: tops, ghi_chu: note };
            }
        },
        inc(k) { if (this.cart[k]) this.cart[k].qty++; },
        dec(k) { if (this.cart[k]) { this.cart[k].qty--; if (this.cart[k].qty <= 0) delete this.cart[k]; } },
        remove(k) { delete this.cart[k]; },
        optionsOf(it) {
            const o = [];
            if (it.temp) o.push({ type: 'temperature', value: it.temp, price: 0 });
            (it.toppings || []).forEach(t => o.push({ type: 'topping', value: t.value, price: t.price }));
            return o;
        },
        extra(it) { return (it.toppings || []).reduce((s, t) => s + t.price, 0); },
        lineTotal(it) { return (it.gia + this.extra(it)) * it.qty; },
        descLine(it) {
            let p = []; if (it.temp) p.push(it.temp);
            (it.toppings || []).forEach(t => p.push(t.value));
            return p.join(' · ');
        },
        get items() { return Object.values(this.cart); },
        get count() { return this.items.reduce((s, i) => s + i.qty, 0); },
        get subtotal() { return this.items.reduce((s, i) => s + this.lineTotal(i), 0); },
        money(v) { return new Intl.NumberFormat('vi-VN').format(Math.round(v)) + 'đ'; },
    };
}
</script>
@vite('resources/js/mon-viewer.js')
@endsection
