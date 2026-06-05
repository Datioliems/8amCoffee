@extends('layouts.app')
@section('title', 'Phân tích AI - 8AM')
@section('page-title', 'Phân tích & dự báo (AI)')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" x-data="{ guide: false }">

    {{-- Hướng dẫn sử dụng --}}
    <div class="rounded-2xl border border-[#522C25]/10 bg-[#FFF7E8] p-4">
        <button type="button" @click="guide = !guide" class="flex w-full items-center justify-between text-left">
            <span class="text-sm font-semibold text-[#8B5A2B]">Hướng dẫn đọc trang Phân tích AI</span>
            <span class="text-[#8B5A2B]" x-text="guide ? '−' : '+'"></span>
        </button>
        <div x-show="guide" x-cloak class="mt-3 space-y-2 text-sm leading-6 text-[#522C25]/75">
            <p><strong>1. Dự báo doanh thu:</strong> dùng hồi quy tuyến tính trên doanh thu các ngày gần đây để ước tính 7 ngày tới. <em>Xu hướng</em> (tăng/giảm) cho biết chiều biến động; <strong>R²</strong> càng gần 1 thì dự báo càng đáng tin (R² thấp = dữ liệu dao động, chỉ tham khảo).</p>
            <p><strong>2. Phân tích giỏ hàng (luật kết hợp):</strong> tìm các cặp món hay được mua cùng nhau.</p>
            <ul class="ml-4 list-disc">
                <li><strong>Số đơn:</strong> số đơn có chứa cả hai món.</li>
                <li><strong>Độ tin (confidence):</strong> trong các đơn có món A, bao nhiêu % cũng có món B.</li>
                <li><strong>Lift:</strong> &gt; 1 nghĩa là hai món đi cùng nhau nhiều hơn mức ngẫu nhiên → nên gợi ý/combo. ≤ 1 thì liên hệ yếu.</li>
            </ul>
            <p><strong>3. Tùy chỉnh:</strong> chọn <em>phạm vi theo ngày</em> hoặc <em>số đơn gần nhất</em>, đặt <em>ngưỡng tối thiểu</em> (lọc cặp hiếm) rồi bấm <strong>Áp dụng</strong>. Dữ liệu càng nhiều, luật càng chính xác.</p>
        </div>
    </div>

    {{-- ── DỰ BÁO DOANH THU ─────────────────────────────────────── --}}
    <section>
        <div class="mb-3">
            <h2 class="text-base font-semibold">Dự báo doanh thu 7 ngày tới</h2>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Xu hướng</p>
                <p class="mt-1 text-2xl font-semibold {{ $forecast['trend']==='tăng' ? 'text-emerald-600' : ($forecast['trend']==='giảm' ? 'text-[#BB0011]' : 'text-[#522C25]') }}">
                    {{ ucfirst($forecast['trend']) }}
                </p>
            </div>
            <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-[#522C25]/55">DT trung bình/ngày</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($forecast['avg'], 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Dự báo 7 ngày tới</p>
                <p class="mt-1 text-2xl font-semibold text-[#2563eb]">{{ number_format($forecast['next_total'], 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-[#522C25]/55">Độ khớp mô hình (R²)</p>
                <p class="mt-1 text-2xl font-semibold">{{ $forecast['r2'] }}</p>
                <p class="text-[11px] text-[#522C25]/45">càng gần 1 càng đáng tin</p>
            </div>
        </div>

        <div class="mt-4 rounded-3xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
            <div class="h-72"><canvas id="chart-forecast"></canvas></div>
            <p class="mt-3 text-xs leading-5 text-[#522C25]/55">
                Mô hình hồi quy tuyến tính (least squares) trên doanh thu 30 ngày gần nhất.
                Đường nét đứt là dự báo — chỉ mang tính tham khảo, độ tin cậy phụ thuộc R².
            </p>
        </div>
    </section>

    {{-- ── LUẬT GỢI Ý MÓN MUA KÈM ───────────────────────────────── --}}
    <section>
        <div class="mb-3">
            <h2 class="text-base font-semibold">Phân tích giỏ hàng — món hay đi cùng nhau</h2>
        </div>

        <div class="rounded-3xl border border-[#522C25]/10 bg-white shadow-sm">
            <div class="border-b border-[#522C25]/10 px-6 py-4">
                <p class="mb-3 text-sm text-[#522C25]/65">Luật kết hợp (market-basket) tính từ <strong>{{ $totalDon }}</strong> đơn theo phạm vi đã chọn.</p>
                <form method="GET" class="flex flex-wrap items-end gap-3" x-data="{ pv: '{{ $filters['pham_vi'] }}' }">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Phạm vi</label>
                        <select name="pham_vi" x-model="pv" class="rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                            <option value="ngay">Theo số ngày</option>
                            <option value="don">Theo số đơn gần nhất</option>
                        </select>
                    </div>
                    <div x-show="pv==='ngay'">
                        <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Số ngày gần nhất</label>
                        <input type="number" name="days" min="1" max="365" value="{{ $filters['days'] }}"
                               class="w-28 rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                    </div>
                    <div x-show="pv==='don'" x-cloak>
                        <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Số đơn gần nhất</label>
                        <input type="number" name="max_orders" min="5" max="5000" value="{{ $filters['maxOrders'] }}"
                               class="w-28 rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Ngưỡng tối thiểu (số đơn chứa cặp)</label>
                        <input type="number" name="min_support" min="1" max="50" value="{{ $filters['minSupport'] }}"
                               class="w-28 rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-[#522C25]/55">Số luật hiển thị</label>
                        <input type="number" name="top" min="5" max="100" value="{{ $filters['topN'] }}"
                               class="w-24 rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm">
                    </div>
                    <button class="rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-semibold text-white hover:bg-black">Áp dụng</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                        <tr>
                            <th class="px-4 py-3">Khi khách mua</th>
                            <th class="px-4 py-3">Thường mua kèm</th>
                            <th class="px-4 py-3 text-right">Số đơn</th>
                            <th class="px-4 py-3 text-right">Độ tin (confidence)</th>
                            <th class="px-4 py-3 text-right">Lift</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#522C25]/8">
                        @forelse($rules as $r)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $tenMon[$r['a']] ?? $r['a'] }}</td>
                            <td class="px-4 py-3 text-[#8B5A2B] font-medium">{{ $tenMon[$r['b']] ?? $r['b'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $r['support'] }}</td>
                            <td class="px-4 py-3 text-right">{{ round($r['confidence']*100) }}%</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $r['lift'] >= 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-[#F1ECEA] text-[#522C25]/70' }}">
                                    ×{{ $r['lift'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-16 text-center text-[#522C25]/55">
                            Chưa đủ dữ liệu đơn hàng để rút ra luật gợi ý. Hãy tạo thêm vài đơn có nhiều món.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[#522C25]/10 px-6 py-3 text-[11px] leading-5 text-[#522C25]/50">
                <strong>Lift &gt; 1</strong>: hai món đi cùng nhau nhiều hơn mức ngẫu nhiên → nên gợi ý/combo.
                <strong>Confidence</strong>: tỉ lệ đơn có món A thì cũng có món B.
            </div>
        </div>
    </section>
</div>

<script type="application/json" id="forecast-data">@json($forecast)</script>
@vite('resources/js/analytics.js')
@endsection
