@props(['type' => 'success', 'message'])
@php
$cls = match($type) {
    'success' => 'bg-green-50 border-green-200 text-green-700',
    'info'    => 'bg-blue-50 border-blue-200 text-blue-700',
    default   => 'bg-red-50 border-red-200 text-red-700',
};
@endphp
<div x-data="{ show: true }" x-show="show" x-transition
     x-init="setTimeout(() => show = false, 4000)"
     class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3
            border rounded-xl text-sm shadow-sm {{ $cls }}">
    {{ $message }}
    <button @click="show = false" class="ml-2 text-base leading-none opacity-60">&times;</button>
</div>
