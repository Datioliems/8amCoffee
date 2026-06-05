@extends('layouts.app')

@section('title', 'Chi tiết đơn - '.$order->ma_order)
@section('page-title', 'Chi tiết đơn hàng')

@section('content')
<div class="max-w-5xl space-y-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('orders.index', ['status' => $order->trang_thai]) }}" class="text-gray-400 hover:text-gray-600">Quay lại</a>
        <x-order-status-badge :status="$order->trang_thai" />
    </div>

    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
        {{ $errors->first() }}
    </div>
    @endif

    @if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
        <div class="space-y-5">
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Mã đơn</p>
                        <p class="font-mono font-medium">{{ $order->ma_order }}</p>
                    </div>
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Bàn số</p>
                        <p class="font-medium">{{ $order->ban ? $order->ban->so_ban : 'Mang về' }}</p>
                    </div>
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Hình thức</p>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $order->dung_coc_nhua ? 'bg-[#FFE3D6] text-[#9a3412]' : 'bg-[#E8F0DD] text-[#3f5325]' }}">
                            {{ $order->dung_coc_nhua ? 'Mang về (cốc nhựa)' : 'Tại bàn' }}
                        </span>
                    </div>
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Số ghế</p>
                        <p class="font-medium">{{ $order->ban->so_ghe ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Khách hàng</p>
                        <p class="font-medium">{{ $order->customer_name ?? 'Khách vãng lai' }}</p>
                        @if($order->sdt_khach)<p class="text-xs text-gray-400">{{ $order->sdt_khach }}</p>@endif
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3 text-sm font-medium text-gray-700">Món đặt</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-2 text-left">Tên món</th>
                            <th class="px-5 py-2 text-center">SL</th>
                            <th class="px-5 py-2 text-right">Đơn giá</th>
                            <th class="px-5 py-2 text-right">Thành tiền</th>
                            <th class="px-5 py-2 text-right">Tách</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($order->chiTietOrders as $item)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">
                                {{ $item->mon->ten_mon ?? $item->ma_mon }}
                                @if($item->ghi_chu)
                                    <p class="mt-1 whitespace-pre-line text-xs font-normal leading-5 text-gray-500">{{ $item->ghi_chu }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $item->so_luong }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ number_format($item->don_gia_tai_thoi_diem, 0, ',', '.') }}đ</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-800">
                                {{ number_format($item->don_gia_tai_thoi_diem * $item->so_luong, 0, ',', '.') }}đ
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($item->so_luong > 1 && !in_array($order->trang_thai, ['hoan_thanh', 'da_huy']))
                                <form action="{{ route('orders.split', $order->ma_order) }}" method="POST" class="flex justify-end gap-2">
                                    @csrf
                                    <input type="hidden" name="ma_mon" value="{{ $item->ma_mon }}">
                                    <input type="number" name="so_luong_tach" min="1" max="{{ $item->so_luong - 1 }}"
                                           class="w-16 rounded-lg border border-gray-200 px-2 py-1 text-right text-xs">
                                    <button type="submit" class="rounded-lg bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">Tách</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right font-semibold text-gray-700">Tổng cộng</td>
                            <td class="px-5 py-3 text-right text-base font-bold text-amber-600">
                                {{ number_format($order->chiTietOrders->sum(fn($i) => $i->don_gia_tai_thoi_diem * $i->so_luong), 0, ',', '.') }}đ
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-3 text-sm font-semibold text-gray-800">Thao tác đơn</p>
                @if($order->trang_thai === 'cho_xac_nhan' && !$order->ban)
                <a href="{{ route('payment.show', $order->ma_order) }}" class="mb-2 block rounded-lg bg-green-500 px-4 py-2 text-center text-sm font-medium text-white">Thanh toán ngay</a>
                <form action="{{ route('orders.status', $order->ma_order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="trang_thai" value="da_huy">
                    <button onclick="return confirm('Hủy đơn này?')" class="w-full rounded-lg bg-red-50 px-4 py-2 text-sm font-medium text-red-600">Hủy đơn</button>
                </form>
                @elseif($order->trang_thai === 'cho_xac_nhan')
                <form action="{{ route('orders.confirm', $order->ma_order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PUT')
                    <button class="w-full rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white">Xác nhận đơn</button>
                </form>
                <form action="{{ route('orders.status', $order->ma_order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="trang_thai" value="da_huy">
                    <button onclick="return confirm('Hủy đơn này?')" class="w-full rounded-lg bg-red-50 px-4 py-2 text-sm font-medium text-red-600">Hủy đơn</button>
                </form>
                @elseif($order->trang_thai === 'da_xac_nhan')
                <form action="{{ route('orders.status', $order->ma_order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="trang_thai" value="dang_pha_che">
                    <button class="w-full rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white">Chuyển sang đang pha chế</button>
                </form>
                @elseif($order->trang_thai === 'dang_pha_che')
                <form action="{{ route('orders.status', $order->ma_order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="trang_thai" value="da_phuc_vu">
                    <button class="w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white">Chuyển sang đã phục vụ</button>
                </form>
                <a href="{{ route('payment.show', $order->ma_order) }}" class="block rounded-lg bg-green-500 px-4 py-2 text-center text-sm font-medium text-white">Thanh toán</a>
                @elseif($order->trang_thai === 'da_phuc_vu')
                <a href="{{ route('payment.show', $order->ma_order) }}" class="block rounded-lg bg-green-500 px-4 py-2 text-center text-sm font-medium text-white">Thanh toán</a>
                @else
                <p class="text-sm text-gray-500">Đơn không còn thao tác xử lý.</p>
                @endif
            </div>

            @if(!in_array($order->trang_thai, ['hoan_thanh', 'da_huy']))
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-3 text-sm font-semibold text-gray-800">Gộp đơn</p>
                <form action="{{ route('orders.merge', $order->ma_order) }}" method="POST" class="space-y-3">
                    @csrf
                    <select name="target_order" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">Chọn đơn cần gộp vào đơn này</option>
                        @foreach($mergeTargets as $target)
                            <option value="{{ $target->ma_order }}">
                                {{ $target->ma_order }} - {{ $target->ban ? 'Bàn ' . $target->ban->so_ban : 'Mang về' }} - {{ $target->trang_thai }}
                            </option>
                        @endforeach
                    </select>
                    <button class="w-full rounded-lg bg-[#1A1A1A] px-4 py-2 text-sm font-medium text-white">Gộp đơn</button>
                </form>
            </div>
            @endif
        </aside>
    </div>
</div>
@endsection
