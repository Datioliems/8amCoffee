@extends('layouts.customer')

@section('title', 'Bàn đang có khách')

@section('content')
<div class="mx-auto max-w-md text-center">
    <div class="rounded-[2rem] bg-white p-8 ring-1 ring-[#522C25]/10 am-shadow">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#FFF4F2] text-3xl">🔒</div>
        <h1 class="am-display mt-5 text-3xl leading-tight text-[#1A1A1A]">Bàn đang có phiên đặt</h1>
        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-[#522C25]/65">
            Bàn <strong>{{ $ban->so_ban ?? $ban->ma_ban }}</strong> hiện đang có khách đặt món.
            Mỗi bàn chỉ phục vụ <strong>một phiên đặt</strong> tại một thời điểm để tránh nhầm đơn.
        </p>
        <p class="mt-4 text-xs text-[#522C25]/50">
            Vui lòng đợi phiên hiện tại kết thúc (sau khi thanh toán) hoặc liên hệ nhân viên để được hỗ trợ.
        </p>
    </div>
</div>
@endsection
