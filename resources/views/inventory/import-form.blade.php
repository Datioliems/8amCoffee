@extends('layouts.app')

@section('title', 'Tạo phiếu nhập kho')
@section('page-title', 'Tạo phiếu nhập kho')

@section('content')
<div class="max-w-3xl mx-auto">
    <form method="POST" action="{{ route('inventory.import.store') }}" class="space-y-5" x-data="importForm()">
        @csrf

        {{-- Inline supplier modal --}}
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl p-6 space-y-4" @click.stop>
                <h3 class="font-semibold text-gray-800">Thêm nhà cung cấp mới</h3>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tên nhà cung cấp <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newNcc.ten_ncc" placeholder="VD: Công ty ABC"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại</label>
                    <input type="text" x-model="newNcc.sdt" placeholder="Tùy chọn"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" x-model="newNcc.email" placeholder="Tùy chọn"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                </div>

                <p x-show="modalError" x-text="modalError" class="text-xs text-red-500"></p>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="saveSupplier()"
                            :disabled="saving"
                            class="flex-1 rounded-lg bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white text-sm font-medium py-2 transition">
                        <span x-show="!saving">Lưu</span>
                        <span x-show="saving">Đang lưu…</span>
                    </button>
                    <button type="button" @click="closeModal()"
                            class="flex-1 rounded-lg border border-gray-200 text-gray-600 text-sm font-medium py-2 hover:bg-gray-50 transition">
                        Hủy
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-medium text-gray-700 mb-4">Thông tin phiếu</h2>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                        <button type="button" @click="showModal = true"
                                class="text-xs text-amber-600 hover:text-amber-700 font-medium">
                            + Thêm nhà cung cấp mới
                        </button>
                    </div>
                    <select name="ma_ncc" x-ref="supplierSelect" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        @foreach($nhaCungCaps as $ncc)
                        <option value="{{ $ncc->ma_ncc }}" {{ old('ma_ncc') === $ncc->ma_ncc ? 'selected' : '' }}>
                            {{ $ncc->ten_ncc }}
                        </option>
                        @endforeach
                    </select>
                    @error('ma_ncc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <input type="text" name="ghi_chu" value="{{ old('ghi_chu') }}" placeholder="Tùy chọn"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    @error('ghi_chu') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-medium text-gray-700">Danh sách nguyên liệu</h2>
                <button type="button" @click="addRow()"
                        class="text-sm bg-amber-50 text-amber-600 hover:bg-amber-100 px-3 py-1 rounded-lg transition">
                    + Thêm dòng
                </button>
            </div>

            <table class="w-full text-sm">
                <thead class="text-gray-500 text-xs border-b border-gray-100">
                    <tr>
                        <th class="pb-2 text-left">Nguyên liệu</th>
                        <th class="pb-2 text-right">Số lượng</th>
                        <th class="pb-2 text-right">Đơn giá (VNĐ)</th>
                        <th class="pb-2 w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, index) in rows" :key="index">
                        <tr class="border-b border-gray-50">
                            <td class="py-2 pr-2">
                                <select :name="`items[${index}][ma_nl]`" x-model="row.ma_nl" required
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-300">
                                    <option value="">-- Chọn --</option>
                                    @foreach($nguyenLieus as $nl)
                                    <option value="{{ $nl->ma_nl }}">{{ $nl->ten_nl }} ({{ $nl->don_vi }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 pr-2">
                                <input type="number" :name="`items[${index}][so_luong]`"
                                       x-model="row.so_luong" min="0.01" step="0.01" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-amber-300">
                            </td>
                            <td class="py-2 pr-2">
                                <input type="number" :name="`items[${index}][don_gia]`"
                                       x-model="row.don_gia" min="1" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-amber-300">
                            </td>
                            <td class="py-2 text-center">
                                <button type="button" @click="removeRow(index)"
                                        class="text-red-400 hover:text-red-600 text-lg leading-none">×</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                Tạo phiếu nhập
            </button>
            <a href="{{ route('inventory.import.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-5 py-2">Hủy</a>
        </div>
    </form>
</div>

<script>
function importForm() {
    return {
        rows: [{ ma_nl: '{{ $preselectedNl ?? '' }}', so_luong: '', don_gia: '' }],
        showModal: false,
        saving: false,
        modalError: '',
        newNcc: { ten_ncc: '', sdt: '', email: '' },

        addRow() { this.rows.push({ so_luong: '', don_gia: '' }); },
        removeRow(index) {
            if (this.rows.length > 1) this.rows.splice(index, 1);
        },

        closeModal() {
            this.showModal = false;
            this.modalError = '';
            this.newNcc = { ten_ncc: '', sdt: '', email: '' };
        },

        async saveSupplier() {
            if (!this.newNcc.ten_ncc.trim()) {
                this.modalError = 'Vui lòng nhập tên nhà cung cấp.';
                return;
            }
            this.saving = true;
            this.modalError = '';
            try {
                const res = await fetch('{{ route('inventory.supplier.quick') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.newNcc),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.modalError = data.errors?.ten_ncc?.[0] ?? 'Có lỗi xảy ra.';
                    return;
                }
                const select = this.$refs.supplierSelect;
                const option = new Option(data.ten_ncc, data.ma_ncc, true, true);
                select.add(option);
                this.closeModal();
            } catch {
                this.modalError = 'Không thể kết nối server.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
