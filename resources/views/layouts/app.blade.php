<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '8AM Coffee - Quản trị')</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo8am.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chivo:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-[#F6F3F2] text-[#1A1A1A]" x-data="{ mobileNav: false }">

<aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-[#522C25]/10 bg-[#FCFAFA] lg:flex">
    <div class="flex h-20 items-center gap-3 border-b border-[#522C25]/10 px-5">
        <img src="{{ asset('images/logo8am.jpg') }}" alt="8AM Coffee" class="h-11 w-11 rounded-xl object-cover ring-1 ring-[#522C25]/10">
        <div>
            <p class="font-semibold leading-none">8am.coffee</p>
            <p class="mt-1 text-[11px] uppercase tracking-[0.18em] text-[#522C25]/60">quản trị</p>
        </div>
    </div>

    @if(session('chuc_vu') === 'superadmin')
    @php $__branches = \Illuminate\Support\Facades\DB::table('CHI_NHANH')->orderBy('ma_chi_nhanh')->get(); @endphp
    <form method="POST" action="{{ route('chinhanh.switch') }}" class="border-b border-[#522C25]/10 px-5 py-3">
        @csrf
        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.18em] text-[#522C25]/55">Chi nhánh (chủ chuỗi)</label>
        <select name="ma_chi_nhanh" onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#522C25]/15 bg-[#F2F2F2] px-2 py-1.5 text-sm">
            @foreach($__branches as $b)
            <option value="{{ $b->ma_chi_nhanh }}" {{ session('ma_chi_nhanh') === $b->ma_chi_nhanh ? 'selected' : '' }}>{{ $b->ten_chi_nhanh }}</option>
            @endforeach
        </select>
    </form>
    @endif

    <nav class="flex-1 space-y-1 px-3 py-5">
        @perm('dashboard.view')
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('dashboard') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('dashboard') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
            </span>
            Tổng quan
        </a>
        @endperm
        @perm('analytics.view')
        <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('analytics.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('analytics.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m7 14 3-4 4 3 5-7"/></svg>
            </span>
            Phân tích AI
        </a>
        @endperm
        @perm('floorplan.view')
        <a href="{{ route('floorplan') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('floorplan') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('floorplan') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7 9-4 9 4-9 4-9-4Z"/><path d="m3 12 9 4 9-4"/><path d="m3 17 9 4 9-4"/></svg>
            </span>
            Sơ đồ 3D
        </a>
        @endperm
        @perm('orders.manage')
        <a href="{{ route('orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('orders.*') || request()->routeIs('payment.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('orders.*') || request()->routeIs('payment.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3h8l2 3h3v15H3V6h3l2-3Z"/><path d="M8 10h8"/><path d="M8 14h5"/></svg>
            </span>
            Đơn hàng
        </a>
        @endperm
        @perm('inventory.manage')
        <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('inventory.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('inventory.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
            </span>
            Kho hàng
        </a>
        @endperm
        @perm('menu.manage')
        <a href="{{ route('menu.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('menu.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('menu.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Z"/><path d="M16 9h2a3 3 0 0 1 0 6h-2"/><path d="M6 2v2"/><path d="M10 2v2"/><path d="M14 2v2"/></svg>
            </span>
            Thực đơn
        </a>
        @endperm
        @perm('ban.manage')
        <a href="{{ route('ban.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('ban.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('ban.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10h16"/><path d="M6 10l-2 9"/><path d="M18 10l2 9"/><path d="M8 5h8a2 2 0 0 1 2 2v3H6V7a2 2 0 0 1 2-2Z"/></svg>
            </span>
            Bàn & QR
        </a>
        @endperm
        @perm('staff.manage')
        <a href="{{ route('nhanvien.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('nhanvien.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('nhanvien.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            Nhân viên & quyền
        </a>
        @endperm
        @perm('customer.view')
        <a href="{{ route('khachhang.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('khachhang.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('khachhang.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 12 0v1"/></svg>
            </span>
            Khách hàng
        </a>
        @endperm
        @perm('loyalty.manage')
        <a href="{{ route('loyalty.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('loyalty.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('loyalty.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>
            </span>
            Thẻ thành viên
        </a>
        @endperm
        @perm('scanlog.view')
        <a href="{{ route('scanlog.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('scanlog.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('scanlog.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg>
            </span>
            Log quét QR
        </a>
        @endperm
        @perm('anomaly.view')
        <a href="{{ route('scan-anomaly.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('scan-anomaly.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('scan-anomaly.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            </span>
            Cảnh báo QR bất thường
        </a>
        @endperm
        @perm('auditlog.view')
        <a href="{{ route('auditlog.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('auditlog.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('auditlog.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            Nhật ký đăng nhập
        </a>
        @endperm
        @perm('emaillog.view')
        <a href="{{ route('emaillog.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs('emaillog.*') ? 'bg-[#1A1A1A] text-white' : 'text-[#522C25] hover:bg-[#F2F2F2]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('emaillog.*') ? 'bg-white/15' : 'bg-white' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            </span>
            Nhật ký Email
        </a>
        @endperm
    </nav>

    <div class="border-t border-[#522C25]/10 p-4">
        <div class="mb-3 rounded-2xl bg-[#F2F2F2] p-3">
            <p class="text-sm font-semibold">{{ session('ten_nv', 'Nhân viên') }}</p>
            <p class="mt-1 text-xs text-[#522C25]/60">{{ session('chuc_vu', 'nhân viên') }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full rounded-xl px-3 py-2 text-left text-sm text-[#BB0011] hover:bg-red-50">
                Đăng xuất
            </button>
        </form>
    </div>
</aside>

<main class="min-h-screen lg:pl-64">
    <header class="sticky top-0 z-30 flex h-16 items-center border-b border-[#522C25]/10 bg-[#FCFAFA]/95 px-4 backdrop-blur md:px-6">
        <button class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#522C25] ring-1 ring-[#522C25]/10 lg:hidden" @click="mobileNav = !mobileNav" aria-label="Mở menu">
            ☰
        </button>
        <div>
            <p class="text-[11px] uppercase tracking-[0.18em] text-[#522C25]/60">vận hành 8am</p>
            <h1 class="mt-0.5 text-lg font-semibold">@yield('page-title', 'Bảng điều khiển')</h1>
        </div>
        <div class="ml-auto hidden items-center gap-3 md:flex">
            <span class="rounded-full bg-white px-3 py-1.5 text-xs text-[#522C25] ring-1 ring-[#522C25]/10">{{ now()->format('d/m/Y') }}</span>
            <span class="rounded-full bg-[#E82C2A] px-3 py-1.5 text-xs font-semibold text-white">{{ session('chuc_vu', '') }}</span>
        </div>
    </header>

    <div x-show="mobileNav" @click.outside="mobileNav = false" class="fixed left-4 right-4 top-20 z-50 rounded-2xl bg-white p-3 shadow-xl ring-1 ring-[#522C25]/10 lg:hidden" style="display: none;">
        @perm('dashboard.view')<a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2 text-sm">Tổng quan</a>@endperm
        @perm('analytics.view')<a href="{{ route('analytics.index') }}" class="block rounded-xl px-3 py-2 text-sm">Phân tích AI</a>@endperm
        @perm('orders.manage')<a href="{{ route('orders.index') }}" class="block rounded-xl px-3 py-2 text-sm">Đơn hàng</a>@endperm
        @perm('inventory.manage')<a href="{{ route('inventory.index') }}" class="block rounded-xl px-3 py-2 text-sm">Kho hàng</a>@endperm
        @perm('menu.manage')<a href="{{ route('menu.index') }}" class="block rounded-xl px-3 py-2 text-sm">Thực đơn</a>@endperm
        @perm('ban.manage')<a href="{{ route('ban.index') }}" class="block rounded-xl px-3 py-2 text-sm">Bàn & QR</a>@endperm
        @perm('staff.manage')<a href="{{ route('nhanvien.index') }}" class="block rounded-xl px-3 py-2 text-sm">Nhân viên & quyền</a>@endperm
        @perm('customer.view')<a href="{{ route('khachhang.index') }}" class="block rounded-xl px-3 py-2 text-sm">Khách hàng</a>@endperm
        @perm('loyalty.manage')<a href="{{ route('loyalty.index') }}" class="block rounded-xl px-3 py-2 text-sm">Thẻ thành viên</a>@endperm
        @perm('scanlog.view')<a href="{{ route('scanlog.index') }}" class="block rounded-xl px-3 py-2 text-sm">Log quét QR</a>@endperm
        @perm('anomaly.view')<a href="{{ route('scan-anomaly.index') }}" class="block rounded-xl px-3 py-2 text-sm">Cảnh báo QR bất thường</a>@endperm
        @perm('auditlog.view')<a href="{{ route('auditlog.index') }}" class="block rounded-xl px-3 py-2 text-sm">Nhật ký đăng nhập</a>@endperm
        @perm('emaillog.view')<a href="{{ route('emaillog.index') }}" class="block rounded-xl px-3 py-2 text-sm">Nhật ký Email</a>@endperm
    </div>

    <section class="p-4 md:p-6">
        @if(session('success'))
            <x-alert-toast type="success" :message="session('success')" />
        @endif
        @if(session('error'))
            <x-alert-toast type="error" :message="session('error')" />
        @endif
        @yield('content')
    </section>
</main>

</body>
</html>
