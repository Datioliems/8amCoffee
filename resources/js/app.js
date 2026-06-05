import Alpine from 'alpinejs';
window.Alpine = Alpine;

// ── Cart store dùng cho layout customer ──────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.data('cart', () => ({
        items: [],
        openCart: false,
        showCustomizer: false,
        selectedMon: null,
        selectedOptions: {
            temperature: 'Đá',
            sweetness: '50%',
            toppings: [],
            note: '',
            qty: 1,
        },
        toastMessage: '',
        toastTimer: null,
        activeCategory: null,
        checkoutError: '',

        init() {
            // Dọn giỏ hàng cũ: chỉ giữ lại giỏ của đơn hiện tại, xóa mọi giỏ đơn khác
            // để header không còn "lưu đơn cũ" khi khách chuyển bàn/đơn.
            this.purgeOtherCarts();
            if (!this.currentOrder) {
                // Trang chưa có đơn (quét QR / đăng nhập) → giỏ phải trống, xóa luôn giỏ "guest" cũ.
                localStorage.removeItem('cart_items_guest');
                this.items = [];
            } else {
                this.items = JSON.parse(localStorage.getItem(this.cartKey) || '[]');
            }
            localStorage.removeItem('cart_items');
        },

        purgeOtherCarts() {
            const keep = this.cartKey;
            const stale = [];
            for (let i = 0; i < localStorage.length; i++) {
                const k = localStorage.key(i);
                if (k && k.startsWith('cart_items_') && k !== keep) stale.push(k);
            }
            stale.forEach(k => localStorage.removeItem(k));
        },

        get currentOrder() {
            return document.querySelector('meta[name="ma-order"]')?.content || '';
        },

        get cartKey() {
            return this.currentOrder ? `cart_items_${this.currentOrder}` : 'cart_items_guest';
        },

        get totalItems() {
            return this.items.reduce((sum, i) => sum + i.qty, 0);
        },

        get totalPrice() {
            return this.items.reduce((sum, i) => {
                const extra = (i.options || []).reduce((s, o) => s + (Number(o.price) || 0), 0);
                return sum + (Number(i.don_gia) + extra) * i.qty;
            }, 0);
        },

        get checkoutUrl() {
            const maOrder = document.querySelector('meta[name="ma-order"]')?.content;
            return maOrder ? `/order/${maOrder}/cart` : '#';
        },

        addToCart(mon) {
            this.openCustomizer(mon);
        },

        openCustomizer(mon) {
            this.selectedMon = {
                ...mon,
                warnings: this.buildWarnings(mon),
                options: this.buildOptions(mon),
            };
            this.selectedOptions = {
                temperature: this.selectedMon.options.temperature[0] || '',
                sweetness: this.selectedMon.options.sweetness[1] || this.selectedMon.options.sweetness[0] || '',
                toppings: [],
                note: '',
                qty: 1,
            };
            this.showCustomizer = true;
        },

        closeCustomizer() {
            this.showCustomizer = false;
            this.selectedMon = null;
        },

        buildOptions(mon) {
            if (mon.options) {
                return {
                    temperature: mon.options.temperature || [],
                    sweetness: mon.options.sweetness?.length ? mon.options.sweetness : ['0%', '30%', '50%', '70%', '100%'],
                    toppings: mon.options.toppings || [],
                    topping_prices: mon.options.topping_prices || {},
                };
            }

            const text = `${mon.ten_mon || ''} ${mon.mo_ta || ''} ${mon.category || ''}`.toLowerCase();
            const isFood = text.includes('eats') || text.includes('bánh') || text.includes('hạt sen') || text.includes('đồ ăn');
            const isTea = text.includes('trà') || text.includes('chanh') || text.includes('ca cao');
            const isCoffee = text.includes('cà phê') || text.includes('coffee') || text.includes('espresso') || text.includes('latte') || text.includes('cold brew') || text.includes('americano');

            if (isFood) {
                return {
                    temperature: [],
                    sweetness: [],
                    toppings: ['Kem mặn', 'Sốt caramel', 'Ăn kèm đá lạnh'],
                };
            }

            return {
                temperature: text.includes('cold brew') || isTea ? ['Đá', 'Ít đá', 'Không đá'] : ['Đá', 'Nóng', 'Ít đá'],
                sweetness: ['0%', '30%', '50%', '70%', '100%'],
                toppings: isCoffee
                    ? ['Kem mặn', 'Caramel', 'Shot espresso', 'Sữa tươi']
                    : ['Trân châu', 'Thạch konjac', 'Sương sáo', 'Thạch dừa'],
            };
        },

        buildWarnings(mon) {
            const text = `${mon.ten_mon || ''} ${mon.mo_ta || ''} ${mon.category || ''}`.toLowerCase();
            const warnings = [];

            if (text.includes('sữa') || text.includes('latte') || text.includes('bạc xỉu') || text.includes('ca cao')) {
                warnings.push('Có sữa hoặc lactose. Người dị ứng sữa nên cân nhắc.');
            }
            if (text.includes('hạt') || text.includes('sen')) {
                warnings.push('Có thành phần hạt. Không phù hợp với người dị ứng các loại hạt.');
            }
            if (text.includes('cà phê') || text.includes('coffee') || text.includes('espresso') || text.includes('cold brew') || text.includes('latte') || text.includes('americano')) {
                warnings.push('Có caffeine. Trẻ em, phụ nữ mang thai, người mất ngủ hoặc bệnh tim mạch nên hạn chế.');
            }
            if (text.includes('rượu') || text.includes('bailey')) {
                warnings.push('Có cồn. Chỉ phù hợp khách từ 18 tuổi trở lên.');
            }
            if (text.includes('gừng')) {
                warnings.push('Có gừng. Người đang dùng thuốc chống đông hoặc đau dạ dày nên cân nhắc.');
            }

            return warnings.length ? warnings : ['Vui lòng báo nhân viên nếu bạn có dị ứng thực phẩm hoặc cần tư vấn thành phần.'];
        },

        toggleTopping(topping) {
            const index = this.selectedOptions.toppings.indexOf(topping);
            if (index >= 0) {
                this.selectedOptions.toppings.splice(index, 1);
            } else {
                this.selectedOptions.toppings.push(topping);
            }
        },

        get customizationSummary() {
            if (!this.selectedMon) return '';
            const parts = this.optionPayload.map(option => `${option.label}: ${option.value}`);
            if (this.selectedOptions.note.trim()) parts.push(`Ghi chú: ${this.selectedOptions.note.trim()}`);
            return parts.join(' | ');
        },

        get optionPayload() {
            if (!this.selectedMon) return [];
            const options = [];
            if (this.selectedOptions.temperature && this.selectedMon.options.temperature.includes(this.selectedOptions.temperature)) {
                options.push({ type: 'temperature', label: 'Nhiệt độ', value: this.selectedOptions.temperature, price: 0 });
            }
            if (this.selectedOptions.sweetness && this.selectedMon.options.sweetness.includes(this.selectedOptions.sweetness)) {
                options.push({ type: 'sweetness', label: 'Độ ngọt', value: this.selectedOptions.sweetness, price: 0 });
            }
            const tprices = this.selectedMon.options.topping_prices || {};
            for (const topping of this.selectedOptions.toppings) {
                options.push({ type: 'topping', label: 'Topping', value: topping, price: Number(tprices[topping]) || 0 });
            }
            return options;
        },

        addCustomizedToCart() {
            if (!this.selectedMon) return;
            const summary = this.customizationSummary;
            const variantKey = `${this.selectedMon.ma_mon}|${summary}`;
            const existing = this.items.find(i => i.variantKey === variantKey);
            if (existing) {
                existing.qty += this.selectedOptions.qty;
            } else {
                this.items.push({
                    ma_mon: this.selectedMon.ma_mon,
                    ten_mon: this.selectedMon.ten_mon,
                    don_gia: this.selectedMon.don_gia,
                    qty: this.selectedOptions.qty,
                    ghi_chu: summary,
                    options: this.optionPayload,
                    variantKey,
                });
            }
            this.saveCart();
            this.showToast(`Đã thêm ${this.selectedMon.ten_mon} vào giỏ hàng`);
            this.closeCustomizer();
        },

        showToast(message) {
            this.toastMessage = message;
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastMessage = '';
            }, 2600);
        },

        increment(itemKey) {
            const item = this.items.find(i => (i.variantKey || i.ma_mon) === itemKey);
            if (item) { item.qty++; this.saveCart(); }
        },

        decrement(itemKey) {
            const idx = this.items.findIndex(i => (i.variantKey || i.ma_mon) === itemKey);
            if (idx === -1) return;
            if (this.items[idx].qty > 1) {
                this.items[idx].qty--;
            } else {
                this.items.splice(idx, 1);
            }
            this.saveCart();
        },

        saveCart() {
            localStorage.setItem(this.cartKey, JSON.stringify(this.items));
        },

        formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
        },

        // Lấy CSRF token hiện tại (ưu tiên meta), có thể làm mới từ server khi gặp 419.
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        async refreshCsrf() {
            try {
                const r = await fetch('/csrf-token', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                if (!r.ok) return this.csrfToken();
                const data = await r.json();
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta && data.token) meta.setAttribute('content', data.token);
                return data.token || this.csrfToken();
            } catch { return this.csrfToken(); }
        },

        // POST JSON kèm tự động retry 1 lần khi token hết hạn (419 CSRF mismatch).
        async postJson(url, payload) {
            let token = this.csrfToken();
            let response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(payload),
            });
            if (response.status === 419) {
                token = await this.refreshCsrf();
                response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(payload),
                });
            }
            return response;
        },

        async checkout() {
            const maOrder = document.querySelector('meta[name="ma-order"]')?.content;

            if (!maOrder) {
                this.checkoutError = 'Không tìm thấy mã đơn hàng.';
                return;
            }

            if (this.items.length === 0) {
                this.checkoutError = 'Giỏ hàng đang trống.';
                return;
            }

            this.checkoutError = '';

            try {
                for (const item of this.items) {
                    const response = await this.postJson(`/order/${maOrder}/item`, {
                        ma_mon: item.ma_mon,
                        so_luong: item.qty,
                        ghi_chu: item.ghi_chu || null,
                        options: item.options || [],
                    });

                    if (!response.ok) {
                        const payload = await response.json().catch(() => null);
                        const message = Object.values(payload?.errors || {})?.flat()?.[0]
                            || payload?.message
                            || 'Không thể gửi món lên hệ thống.';
                        throw new Error(message);
                    }
                }

                this.clearCart();
                window.location.href = `/order/${maOrder}/cart`;
            } catch (error) {
                this.checkoutError = error.message || 'Không thể gửi giỏ hàng.';
            }
        },

        clearCart() {
            this.items = [];
            localStorage.removeItem(this.cartKey);
        }
    }));
});

Alpine.start();
