@extends('layouts.app')
@section('title', 'Cảnh báo QR bất thường - 8AM')
@section('page-title', 'Cảnh báo QR bất thường')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">

    {{-- ── Stat cards ─────────────────────────────────────────────────────── --}}
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-[2rem] bg-[#1A1A1A] p-5 text-white">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Tổng cảnh báo</p>
            <p class="mt-3 text-4xl font-bold">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs text-white/40">is_alert = true</p>
        </div>
        <div class="rounded-[2rem] bg-white p-5 ring-1 ring-[#522C25]/10">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#522C25]/50">Critical</p>
            <p class="mt-3 text-4xl font-bold text-[#E82C2A]">{{ $stats['critical'] }}</p>
            <p class="mt-1 text-xs text-[#522C25]/40">risk_score ≥ 80</p>
        </div>
        <div class="rounded-[2rem] bg-white p-5 ring-1 ring-[#522C25]/10">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#522C25]/50">High</p>
            <p class="mt-3 text-4xl font-bold text-orange-500">{{ $stats['high'] }}</p>
            <p class="mt-1 text-xs text-[#522C25]/40">risk_score 60–79</p>
        </div>
        <div class="rounded-[2rem] bg-white p-5 ring-1 ring-[#522C25]/10">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#522C25]/50">Chờ review</p>
            <p class="mt-3 text-4xl font-bold text-[#522C25]">{{ $stats['pending'] }}</p>
            <p class="mt-1 text-xs text-[#522C25]/40">Chưa có phản hồi</p>
        </div>
    </section>

    {{-- ── Bảng chính ─────────────────────────────────────────────────────── --}}
    <div class="rounded-[2rem] bg-white ring-1 ring-[#522C25]/10">

        {{-- Toolbar --}}
        <div class="space-y-3 border-b border-[#522C25]/8 px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">IP bất thường phát hiện</h2>
                    <p class="mt-0.5 text-xs text-[#522C25]/50">Rule-based + Isolation Forest ML · {{ $alerts->total() }} bản ghi</p>
                </div>
                <a href="{{ route('scan-anomaly.index', array_merge(request()->query(), ['show_all' => $showAll ? 0 : 1])) }}"
                   class="rounded-xl bg-[#F8F6F5] px-3 py-1.5 text-xs font-medium text-[#522C25] transition hover:bg-[#F0EDEC]">
                    {{ $showAll ? 'Chỉ cảnh báo' : 'Xem cả theo dõi' }}
                </a>
            </div>

            {{-- Filter chips --}}
            <div class="flex flex-wrap items-center gap-4">
                {{-- Risk level filter --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[#522C25]/40">Mức rủi ro</span>
                    @php
                        $riskFilters = [
                            ''         => ['label' => 'Tất cả', 'color' => 'bg-[#1A1A1A] text-white',                            'inactive' => 'bg-[#F8F6F5] text-[#522C25]/60 hover:bg-[#F0EDEC]'],
                            'critical' => ['label' => 'Critical', 'color' => 'bg-[#E82C2A] text-white',                          'inactive' => 'bg-red-50 text-red-600 hover:bg-red-100'],
                            'high'     => ['label' => 'High',     'color' => 'bg-orange-500 text-white',                         'inactive' => 'bg-orange-50 text-orange-600 hover:bg-orange-100'],
                            'medium'   => ['label' => 'Medium',   'color' => 'bg-yellow-400 text-[#1A1A1A]',                     'inactive' => 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100'],
                            'low'      => ['label' => 'Low',      'color' => 'bg-gray-200 text-gray-700',                        'inactive' => 'bg-[#F8F6F5] text-[#522C25]/50 hover:bg-[#F0EDEC]'],
                        ];
                    @endphp
                    @foreach($riskFilters as $val => $cfg)
                    @php
                        $isActive  = $riskLevel === $val || ($val === '' && ! $riskLevel);
                        $chipClass = $isActive ? $cfg['color'] : $cfg['inactive'];
                        $params    = array_merge(request()->query(), ['risk_level' => $val ?: null]);
                    @endphp
                    <a href="{{ route('scan-anomaly.index', array_filter($params, fn($v) => $v !== null)) }}"
                       class="rounded-full px-3 py-1 text-[11px] font-semibold transition {{ $chipClass }}">
                        {{ $cfg['label'] }}
                    </a>
                    @endforeach
                </div>

                <div class="h-5 w-px bg-[#522C25]/10"></div>

                {{-- Method filter --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[#522C25]/40">Phương pháp</span>
                    @php
                        $methodFilters = [
                            ''       => ['label' => 'Tất cả', 'color' => 'bg-[#1A1A1A] text-white',       'inactive' => 'bg-[#F8F6F5] text-[#522C25]/60 hover:bg-[#F0EDEC]'],
                            'hybrid' => ['label' => 'Hybrid', 'color' => 'bg-purple-600 text-white',       'inactive' => 'bg-purple-50 text-purple-700 hover:bg-purple-100'],
                            'ml'     => ['label' => 'ML',     'color' => 'bg-blue-600 text-white',         'inactive' => 'bg-blue-50 text-blue-700 hover:bg-blue-100'],
                            'rule'   => ['label' => 'Rule',   'color' => 'bg-[#522C25] text-white',        'inactive' => 'bg-[#F8F6F5] text-[#522C25]/70 hover:bg-[#F0EDEC]'],
                        ];
                    @endphp
                    @foreach($methodFilters as $val => $cfg)
                    @php
                        $isActive  = $method === $val || ($val === '' && ! $method);
                        $chipClass = $isActive ? $cfg['color'] : $cfg['inactive'];
                        $params    = array_merge(request()->query(), ['method' => $val ?: null]);
                    @endphp
                    <a href="{{ route('scan-anomaly.index', array_filter($params, fn($v) => $v !== null)) }}"
                       class="rounded-full px-3 py-1 text-[11px] font-semibold transition {{ $chipClass }}">
                        {{ $cfg['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-[10px] font-semibold uppercase tracking-[0.15em] text-[#522C25]/50">
                    <tr>
                        <th class="px-5 py-3">Phát hiện</th>
                        <th class="px-5 py-3">IP{{ $isSuper ? ' / Chi nhánh' : '' }}</th>
                        <th class="px-5 py-3 text-center">Hoạt động</th>
                        <th class="px-5 py-3 text-center">Đơn</th>
                        <th class="px-5 py-3">Mức rủi ro</th>
                        <th class="px-5 py-3">Lý do</th>
                        <th class="px-5 py-3">Phản hồi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/6">
                    @forelse($alerts as $alert)
                    @php
                        $fb            = $alert->feedbacks->first();
                        $riskBarClass  = match($alert->risk_level) {
                            'critical' => 'bg-[#E82C2A]',
                            'high'     => 'bg-orange-400',
                            'medium'   => 'bg-yellow-400',
                            default    => 'bg-gray-300',
                        };
                        $riskTextClass = match($alert->risk_level) {
                            'critical' => 'text-[#E82C2A]',
                            'high'     => 'text-orange-500',
                            'medium'   => 'text-yellow-600',
                            default    => 'text-gray-400',
                        };
                        $reasonTagClass = match($alert->risk_level) {
                            'critical' => 'bg-red-50 text-red-700 ring-red-200 hover:bg-red-100',
                            'high'     => 'bg-orange-50 text-orange-700 ring-orange-200 hover:bg-orange-100',
                            'medium'   => 'bg-yellow-50 text-yellow-700 ring-yellow-200 hover:bg-yellow-100',
                            default    => 'bg-[#F8F6F5] text-[#522C25]/70 ring-[#522C25]/10 hover:bg-[#F0EDEC]',
                        };
                    @endphp
                    <tr class="transition hover:bg-[#FDFAF9]">

                        {{-- Phát hiện --}}
                        <td class="px-5 py-3.5">
                            <p class="text-xs font-medium text-[#1A1A1A]">{{ optional($alert->detected_at)->format('d/m/Y') }}</p>
                            <p class="mt-0.5 text-[11px] text-[#522C25]/50">{{ optional($alert->detected_at)->format('H:i:s') }}</p>
                            @if($alert->is_alert)
                                <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-600 ring-1 ring-red-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Cảnh báo
                                </span>
                            @else
                                <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-[#F8F6F5] px-2 py-0.5 text-[10px] text-[#522C25]/60 ring-1 ring-[#522C25]/10">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#522C25]/30"></span> Theo dõi
                                </span>
                            @endif
                        </td>

                        {{-- IP / Chi nhánh --}}
                        <td class="px-5 py-3.5">
                            <p class="font-mono text-xs font-semibold text-[#1A1A1A]">{{ $alert->ip_masked }}</p>
                            @if($isSuper && $alert->ma_chi_nhanh)
                                <span class="mt-1 inline-block rounded-full bg-[#F2F2F2] px-2 py-0.5 text-[10px] text-[#522C25]/70">
                                    {{ $alert->ma_chi_nhanh }}
                                </span>
                            @endif
                        </td>

                        {{-- Hoạt động --}}
                        <td class="px-5 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="text-center">
                                    <p class="text-lg font-bold text-[#1A1A1A]">{{ $alert->scan_count }}</p>
                                    <p class="text-[10px] text-[#522C25]/45">lượt quét</p>
                                </div>
                                <div class="h-8 w-px bg-[#522C25]/10"></div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-[#522C25]">{{ $alert->distinct_tables }}</p>
                                    <p class="text-[10px] text-[#522C25]/45">bàn</p>
                                </div>
                                @if($alert->distinct_branches > 1)
                                <div class="h-8 w-px bg-[#522C25]/10"></div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-orange-500">{{ $alert->distinct_branches }}</p>
                                    <p class="text-[10px] text-[#522C25]/45">CN</p>
                                </div>
                                @endif
                            </div>
                        </td>

                        {{-- Đơn hàng --}}
                        <td class="px-5 py-3.5 text-center">
                            @if($alert->created_orders === 0)
                                <p class="text-lg font-bold text-[#E82C2A]">0</p>
                                <p class="text-[10px] text-[#522C25]/45">đơn</p>
                            @else
                                <p class="text-lg font-bold text-green-600">{{ $alert->created_orders }}</p>
                                <p class="text-[10px] text-[#522C25]/45">{{ number_format($alert->conversion_rate * 100, 0) }}%</p>
                            @endif
                        </td>

                        {{-- Risk: score bar + số, bỏ badge label --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-[#F2F2F2]">
                                    <div class="h-full rounded-full {{ $riskBarClass }}" style="width: {{ $alert->risk_score }}%"></div>
                                </div>
                                <span class="text-sm font-bold {{ $riskTextClass }}">{{ $alert->risk_score }}</span>
                            </div>
                        </td>

                        {{-- Lý do: tag button + popover --}}
                        <td class="px-5 py-3.5">
                            @if($alert->reason)
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open"
                                        class="inline-flex max-w-[180px] items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 transition {{ $reasonTagClass }}">
                                    <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                                    </svg>
                                    <span class="truncate">{{ \Illuminate\Support\Str::limit($alert->reason, 28) }}</span>
                                </button>

                                {{-- Popover --}}
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     @click.outside="open = false"
                                     class="absolute left-0 top-full z-50 mt-2 w-72 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-[#522C25]/10"
                                     style="display:none">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.15em] text-[#522C25]/50">Lý do phát hiện</p>
                                    <p class="text-xs leading-relaxed text-[#1A1A1A]">{{ $alert->reason }}</p>
                                    @if($alert->suggested_action)
                                    <div class="mt-3 border-t border-[#522C25]/8 pt-3">
                                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.15em] text-[#522C25]/50">Đề xuất</p>
                                        <p class="text-xs leading-relaxed text-[#522C25]/70">{{ $alert->suggested_action }}</p>
                                    </div>
                                    @endif
                                    <button @click="open = false"
                                            class="mt-3 w-full rounded-xl bg-[#F8F6F5] py-1.5 text-xs font-medium text-[#522C25]/70 hover:bg-[#F0EDEC]">
                                        Đóng
                                    </button>
                                </div>
                            </div>
                            @else
                                <span class="text-[11px] text-[#522C25]/30">—</span>
                            @endif
                        </td>

                        {{-- Phản hồi --}}
                        <td class="px-5 py-3.5">
                            <form method="POST"
                                  action="{{ route('scan-anomaly.feedback', $alert->id) }}"
                                  class="flex items-center gap-1.5">
                                @csrf
                                <button type="submit" name="is_true_positive" value="1"
                                    class="rounded-lg px-2.5 py-1 text-xs font-medium ring-1 transition
                                        {{ ($fb && $fb->is_true_positive) ? 'bg-[#E82C2A] text-white ring-[#E82C2A]' : 'bg-white text-[#522C25]/60 ring-[#522C25]/15 hover:bg-red-50 hover:text-[#E82C2A] hover:ring-red-200' }}">
                                    Đúng
                                </button>
                                <button type="submit" name="is_true_positive" value="0"
                                    class="rounded-lg px-2.5 py-1 text-xs font-medium ring-1 transition
                                        {{ ($fb && ! $fb->is_true_positive) ? 'bg-green-600 text-white ring-green-600' : 'bg-white text-[#522C25]/60 ring-[#522C25]/15 hover:bg-green-50 hover:text-green-600 hover:ring-green-200' }}">
                                    Sai
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-20 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F8F6F5]">
                                    <svg class="h-6 w-6 text-[#522C25]/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-[#522C25]/50">Không có bản ghi nào khớp bộ lọc</p>
                                <a href="{{ route('scan-anomaly.index') }}"
                                   class="text-xs text-[#E82C2A] underline underline-offset-2">Xoá bộ lọc</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($alerts->hasPages())
        <div class="border-t border-[#522C25]/8 px-6 py-4">
            {{ $alerts->links() }}
        </div>
        @endif
    </div>

    {{-- ── Hướng dẫn ───────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-[#522C25]/8 bg-[#FDFCFB] px-6 py-4">
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.15em] text-[#522C25]/50">Hướng dẫn vận hành</p>
        <div class="grid gap-x-8 gap-y-1 text-xs text-[#522C25]/60 sm:grid-cols-2">
            <p>• <strong class="text-[#522C25]/80">Cảnh báo</strong>: risk_score ≥ 60 · <strong class="text-[#522C25]/80">Theo dõi</strong>: 40–59</p>
            <p>• Phản hồi <em>Đúng/Sai</em> giúp cải thiện mô hình ML theo thời gian</p>
            <p>• Phân tích thủ công: <code class="rounded bg-white px-1.5 py-0.5 ring-1 ring-[#522C25]/10">php artisan scan:analyze-anomalies</code></p>
            <p>• Re-train: <code class="rounded bg-white px-1.5 py-0.5 ring-1 ring-[#522C25]/10">scan:export-features</code> → <code class="rounded bg-white px-1.5 py-0.5 ring-1 ring-[#522C2525]/10">python ml/train_qr_anomaly_model.py</code></p>
        </div>
    </div>

</div>
@endsection
