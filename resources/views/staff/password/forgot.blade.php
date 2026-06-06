<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - 8AM Coffee</title>
    <link rel="icon" type="image/jpeg" href="{{ \App\Support\Cdn::url('images/logo8am.jpg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Chivo:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F6F3F2] text-[#1A1A1A]">
<main class="grid min-h-screen place-items-center px-5 py-10">
    <div class="w-full max-w-md rounded-[2rem] bg-[#FCFAFA] p-7 ring-1 ring-[#522C25]/10 md:p-9">
        <img src="{{ \App\Support\Cdn::url('images/logo8am.jpg') }}" alt="8AM Coffee" class="mb-5 h-14 w-14 rounded-2xl object-cover ring-1 ring-[#522C25]/10">
        <h1 class="text-3xl font-semibold">Quên mật khẩu</h1>
        <p class="mt-3 text-sm leading-6 text-[#522C25]/65">Nhập <strong>tên đăng nhập</strong> hoặc <strong>email</strong>. Hệ thống sẽ gửi link đặt lại mật khẩu tới email của tài khoản.</p>

        @if(session('success'))
            <div class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-100">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-[#BB0011] ring-1 ring-red-100">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-[#522C25]/60">Tên đăng nhập hoặc email</label>
                <input type="text" name="tai_khoan" value="{{ old('tai_khoan') }}" required autofocus autocomplete="username"
                       class="w-full rounded-2xl border border-[#522C25]/10 bg-white px-4 py-3 text-sm focus:border-[#E82C2A] focus:ring-[#E82C2A]">
            </div>
            <button type="submit" class="w-full rounded-full bg-[#1A1A1A] py-3.5 text-sm font-semibold text-white transition hover:bg-[#E82C2A] active:scale-[0.98]">
                Gửi link đặt lại
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-5 inline-block text-sm text-[#8B5A2B] hover:underline">← Về đăng nhập</a>
    </div>
</main>
</body>
</html>
