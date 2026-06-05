@extends('layouts.customer')
@section('title', 'Chào mừng - Bàn ' . $ban->so_ban)

@section('content')
<div class="mx-auto flex min-h-[calc(100vh-7rem)] max-w-md flex-col items-center justify-center overflow-hidden">
    <div class="relative flex h-[70vh] min-h-[560px] w-full flex-col items-center justify-start pt-10">
        <div class="am-logo-drop absolute top-6 z-10 flex flex-col items-center justify-center">
            <img src="{{ asset('images/logo8am-brand.png') }}" alt="8AM Coffee" class="h-32 w-32 rounded-full object-cover shadow-xl ring-8 ring-white">
        </div>

        <div class="am-logo-splash absolute bottom-32 h-5 w-24 rounded-full bg-[#E82C2A]/25 opacity-0 blur-md"></div>

        <div class="am-welcome-reveal absolute bottom-0 z-20 flex w-full flex-col items-center gap-4 px-2 pb-6 opacity-0">
            <div class="w-full text-center">
                <img src="{{ asset('images/latte.jpg') }}" alt="8AM Coffee"
                     class="mx-auto mb-4 h-28 w-full max-w-xs rounded-3xl object-cover shadow-md ring-1 ring-[#522C25]/10">
                <p class="am-mono mb-3 text-xs uppercase tracking-[0.18em] text-[#522C25]/55">
                    Bàn {{ str_pad($ban->so_ban, 2, '0', STR_PAD_LEFT) }} · {{ $ban->vi_tri }}
                </p>
                <h1 class="am-display text-5xl leading-none text-[#522C25]">Chào buổi sáng.</h1>
                <p class="mt-3 text-base leading-7 text-[#5D3F3C]">Cùng chọn ly cà phê hợp gu của bạn.</p>
            </div>

            <form method="POST" action="{{ route('customer.create', $ban->ma_ban) }}" class="w-full space-y-3">
                @csrf
                <input type="text" name="ten_kh" placeholder="Nhập tên của bạn" required value="{{ old('ten_kh', $profile['ten_kh'] ?? '') }}"
                       class="am-mono w-full rounded-3xl border-0 bg-[#F2F2F2] px-6 py-4 text-center text-sm text-[#1A1A1A] placeholder:text-[#5D3F3C]/45 focus:ring-2 focus:ring-[#E82C2A]">
                @error('ten_kh') <p class="text-center text-xs text-[#BB0011]">{{ $message }}</p> @enderror
                <input type="text" name="sdt_kh" placeholder="Số điện thoại (10 số, bắt đầu bằng 0)"
                       value="{{ old('sdt_kh', $profile['sdt_kh'] ?? '') }}"
                       inputmode="numeric" maxlength="10" pattern="0[0-9]{9}"
                       title="Số điện thoại phải gồm đúng 10 chữ số, bắt đầu bằng 0"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                       class="am-mono w-full rounded-3xl border-0 bg-[#F2F2F2] px-6 py-4 text-center text-sm text-[#1A1A1A] placeholder:text-[#5D3F3C]/45 focus:ring-2 focus:ring-[#E82C2A]">
                <p class="px-2 text-center text-xs leading-5 text-[#5D3F3C]/70">
                    Số điện thoại phải gồm <strong>đúng 10 chữ số, bắt đầu bằng 0</strong>. Có thể bỏ trống nếu không muốn lưu.
                </p>
                @error('sdt_kh') <p class="text-center text-xs text-[#BB0011]">{{ $message }}</p> @enderror
                <button type="submit"
                        class="am-headline flex w-full items-center justify-center gap-3 rounded-full bg-[#E82C2A] px-6 py-4 text-xl font-semibold text-white shadow-lg transition hover:bg-[#BB0011] active:scale-95">
                    <span>Bắt đầu gọi món</span>
                    <span aria-hidden="true">→</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
