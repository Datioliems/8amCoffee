@props(['status'])
@php
$map = [
    'cho_xac_nhan' => ['Chờ xác nhận', 'bg-yellow-50 text-yellow-700'],
    'da_xac_nhan'  => ['Đã xác nhận', 'bg-blue-50 text-blue-700'],
    'dang_pha_che' => ['Đang pha chế', 'bg-orange-50 text-orange-700'],
    'da_phuc_vu'   => ['Đã phục vụ', 'bg-green-50 text-green-700'],
    'hoan_thanh'   => ['Đã thanh toán', 'bg-emerald-50 text-emerald-700'],
    'da_huy'       => ['Đã hủy', 'bg-red-50 text-red-600'],
];
[$label, $cls] = $map[$status] ?? [$status, 'bg-gray-100 text-gray-500'];
@endphp
<span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $cls }}">{{ $label }}</span>
