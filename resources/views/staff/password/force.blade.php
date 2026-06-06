<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đổi mật khẩu lần đầu — 8AM Coffee</title>
    <link rel="icon" type="image/jpeg" href="{{ \App\Support\Cdn::url('images/logo8am.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#F6F3F2] p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl ring-1 ring-[#522C25]/10">
        <div class="mb-6 text-center">
            <img src="{{ \App\Support\Cdn::url('images/logo8am.jpg') }}" class="mx-auto h-14 w-14 rounded-xl object-cover ring-1 ring-[#522C25]/10" alt="8AM Coffee">
            <h1 class="mt-4 text-xl font-bold text-[#1A1A1A]">Đổi mật khẩu lần đầu</h1>
            <p class="mt-1 text-sm leading-6 text-[#522C25]/65">Vì lý do bảo mật, vui lòng đặt mật khẩu mới của riêng bạn trước khi sử dụng hệ thống.</p>
        </div>

        @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/70">Mật khẩu hiện tại</label>
                <input type="password" name="mat_khau_cu" required autocomplete="current-password"
                       class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E82C2A]/40">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/70">Mật khẩu mới (≥ 8 ký tự)</label>
                <input type="password" name="mat_khau_moi" required autocomplete="new-password"
                       class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E82C2A]/40">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/70">Nhập lại mật khẩu mới</label>
                <input type="password" name="mat_khau_moi_confirmation" required autocomplete="new-password"
                       class="w-full rounded-lg border border-[#522C25]/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E82C2A]/40">
            </div>
            <button type="submit" class="w-full rounded-lg bg-[#E82C2A] py-2.5 text-sm font-semibold text-white transition hover:bg-[#c91f1d]">
                Đặt mật khẩu mới
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button class="text-xs text-[#522C25]/55 transition hover:text-[#522C25]">Đăng xuất</button>
        </form>
    </div>
</body>
</html>
