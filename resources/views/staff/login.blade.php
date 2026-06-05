<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - 8AM Coffee</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo8am.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chivo:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F6F3F2] text-[#1A1A1A]">
<main class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
    <section class="relative hidden overflow-hidden bg-[#1A1A1A] text-white lg:block">
        <img src="{{ asset('images/8-AM-Coffee-Roastery-16.jpg') }}" alt="8AM Coffee" class="absolute inset-0 h-full w-full object-cover opacity-45">
        <div class="absolute inset-0 bg-gradient-to-t from-[#1A1A1A] via-[#1A1A1A]/60 to-transparent"></div>
        <div class="relative flex h-full flex-col justify-between p-10">
            <img src="{{ asset('images/logo8am.jpg') }}" alt="8AM Coffee" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-white/20">
            <div class="max-w-xl">
                <p class="text-xs uppercase tracking-[0.2em] text-white/65">vận hành 8am</p>ph
                <h1 class="mt-4 text-6xl font-semibold leading-none" style="font-family: Chivo, Inter, sans-serif;">Quản lý ca sáng rõ ràng hơn.</h1>
                <p class="mt-5 text-base leading-7 text-white/75">Theo dõi đơn hàng, bàn, menu và kho hàng trong một giao diện dành cho vận hành quán.</p>
            </div>
        </div>
    </section>

    <section class="flex items-center justify-center px-5 py-10">
        <div class="w-full max-w-md rounded-[2rem] bg-[#FCFAFA] p-7 ring-1 ring-[#522C25]/10 am-shadow md:p-9">
            <div class="mb-8">
                <img src="{{ asset('images/logo8am.jpg') }}" alt="8AM Coffee" class="mb-5 h-14 w-14 rounded-2xl object-cover ring-1 ring-[#522C25]/10 lg:hidden">
                <p class="text-xs uppercase tracking-[0.18em] text-[#522C25]/60">Quản trị doanh nghiệp</p>
                <h1 class="mt-2 text-3xl font-semibold">Đăng nhập 8AM</h1>
                <p class="mt-3 text-sm leading-6 text-[#522C25]/65">Dành cho nhân viên và quản lý chi nhánh.</p>
            </div>

            @if($errors->any())
            <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-[#BB0011] ring-1 ring-red-100">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-[#522C25]/60">Tên đăng nhập</label>
                    <input type="text" name="ten_tk" value="{{ old('ten_tk') }}" required
                           class="w-full rounded-2xl border border-[#522C25]/10 bg-white px-4 py-3 text-sm focus:border-[#E82C2A] focus:ring-[#E82C2A]">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-[#522C25]/60">Mật khẩu</label>
                    <input type="password" name="mat_khau" required
                           class="w-full rounded-2xl border border-[#522C25]/10 bg-white px-4 py-3 text-sm focus:border-[#E82C2A] focus:ring-[#E82C2A]">
                </div>
                <button type="submit"
                        class="w-full rounded-full bg-[#1A1A1A] py-3.5 text-sm font-semibold text-white transition hover:bg-[#E82C2A] active:scale-[0.98]">
                    Đăng nhập
                </button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
