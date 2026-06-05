@props(['item'])
@php
$pct = $item['nguong'] > 0 ? min(100, ($item['ton'] / ($item['nguong'] * 4)) * 100) : 100;
$color = $item['ton'] <= 0 ? 'bg-red-400'
    : ($item['ton'] < $item['nguong'] ? 'bg-yellow-400' : 'bg-green-400');
@endphp
<div class="flex items-center gap-3">
    <div class="w-28 text-xs text-gray-600 truncate">{{ $item['ten'] }}</div>
    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full rounded-full {{ $color }}" style="width: {{ $pct }}%"></div>
    </div>
    <div class="text-xs text-gray-500 w-20 text-right">
        {{ number_format($item['ton'], 0) }} / {{ $item['don_vi'] }}
    </div>
</div>
