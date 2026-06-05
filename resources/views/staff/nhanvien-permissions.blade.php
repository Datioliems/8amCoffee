@extends('layouts.app')
@section('title', 'Phân quyền')
@section('page-title', 'Phân quyền tài khoản')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <a href="{{ route('nhanvien.index') }}" class="text-sm text-[#8B5A2B] hover:underline">← Về danh sách nhân viên</a>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error') || $errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#BB0011]">{{ session('error') ?? $errors->first() }}</div>
    @endif

    <div class="rounded-2xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
        <p class="text-sm">Tài khoản: <b>{{ $target->ten_tk }}</b> · Nhân viên: <b>{{ $tenNv }}</b>
            · Vai trò: <span class="rounded-full bg-[#FFF7E8] px-2 py-0.5 text-[11px] font-semibold text-[#8B5A2B]">{{ $roleLabels[$target->chuc_vu] ?? $target->chuc_vu }}</span></p>
        <p class="mt-1 text-[12px] text-[#522C25]/55">
            {{ $theoVaiTro ? 'Đang dùng quyền MẶC ĐỊNH theo vai trò.' : 'Đang dùng quyền RIÊNG (đã tuỳ chỉnh cho tài khoản này).' }}
        </p>
    </div>

    <form method="POST" action="{{ route('nhanvien.permissions.update', $target->ma_tai_khoan) }}"
          class="space-y-4 rounded-2xl border border-[#522C25]/10 bg-white p-5 shadow-sm">
        @csrf
        <p class="text-sm font-semibold text-[#8B5A2B]">Danh mục quyền</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach($catalog as $key => $label)
                @php $coTheCap = in_array($key, $grantable, true); $dangCo = in_array($key, $hieuLuc, true); @endphp
                <label class="flex items-center gap-2 rounded-lg border border-[#522C25]/10 px-3 py-2 text-sm {{ $coTheCap ? 'hover:bg-[#FFF7E8]' : 'opacity-60' }}">
                    <input type="checkbox" name="quyen[]" value="{{ $key }}"
                           @checked($dangCo) @disabled(! $coTheCap)
                           class="h-4 w-4 rounded border-[#522C25]/30 text-[#8B5A2B] focus:ring-[#8B5A2B]">
                    <span>{{ $label }}</span>
                    @unless($coTheCap)
                        <span class="ml-auto text-[10px] text-[#522C25]/45">ngoài thẩm quyền</span>
                    @endunless
                </label>
            @endforeach
        </div>

        <div class="flex flex-col gap-3 border-t border-[#522C25]/10 pt-3 sm:flex-row sm:items-center sm:justify-between">
            <label class="flex items-center gap-2 text-[12px] text-[#522C25]/70">
                <input type="checkbox" name="theo_vai_tro" value="1" class="h-4 w-4 rounded border-[#522C25]/30">
                Dùng quyền MẶC ĐỊNH theo vai trò (bỏ mọi tuỳ chỉnh)
            </label>
            <button class="shrink-0 rounded-lg bg-[#8B5A2B] px-5 py-2 text-sm font-semibold text-white hover:bg-[#6F4621]">Lưu quyền</button>
        </div>
        <p class="text-[11px] leading-5 text-[#522C25]/45">
            Bạn chỉ cấp/bỏ được những quyền MÌNH đang có. Mục "ngoài thẩm quyền" (tài khoản đã có sẵn) sẽ được giữ nguyên.
            Tick "Dùng quyền mặc định" để xoá tuỳ chỉnh và quay về quyền theo vai trò.
        </p>
    </form>
</div>
@endsection
