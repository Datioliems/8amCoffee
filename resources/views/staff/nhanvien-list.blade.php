@extends('layouts.app')

@section('title', 'Nhân viên & Phân quyền - 8AM')
@section('page-title', 'Nhân viên & phân quyền')

@php
    $roleBadge = [
        'superadmin' => 'bg-[#1A1A1A] text-white',
        'admin'      => 'bg-[#E82C2A] text-white',
        'nhan_vien'  => 'bg-sky-100 text-sky-800',
    ];
    $rank = ['nhan_vien' => 1, 'admin' => 2, 'superadmin' => 3];
    $myRank = $rank[session('chuc_vu')] ?? 0;
@endphp

@section('content')
<div class="mx-auto max-w-6xl space-y-6">

    @if(session('success'))
    <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-disc space-y-0.5 pl-5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- ── Form tạo tài khoản mới ────────────────────────────── --}}
    <div class="rounded-3xl border border-[#522C25]/10 bg-white p-6 shadow-sm">
        <h2 class="mb-1 text-base font-semibold">Tạo tài khoản nhân viên mới</h2>
        <p class="mb-4 text-sm text-[#522C25]/60">
            Tên đăng nhập (dạng <strong>staff0001</strong>) và mật khẩu được tạo tự động; mật khẩu sẽ gửi tới email nhân viên.
            @unless($isSuperAdmin) Bạn chỉ tạo được nhân viên cho chi nhánh {{ $myBranch }}. @endunless
        </p>

        <form method="POST" action="{{ route('nhanvien.store') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Họ tên</label>
                <input name="ten_nv" value="{{ old('ten_nv') }}" required
                       class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm" placeholder="Nguyễn Văn A">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Email (nhận mật khẩu)</label>
                <input name="email" type="email" value="{{ old('email') }}" required
                       pattern="[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}"
                       title="Email hợp lệ, vd: nhanvien@email.com"
                       class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm" placeholder="nhanvien@email.com">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Số điện thoại</label>
                <input name="sdt" value="{{ old('sdt') }}" required
                       inputmode="numeric" maxlength="10" pattern="0[0-9]{9}"
                       title="Số điện thoại gồm đúng 10 chữ số, bắt đầu bằng 0"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                       class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm" placeholder="0901234567">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Vai trò</label>
                <select name="chuc_vu" required class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm">
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" @selected(old('chuc_vu')===$key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#522C25]/60">Chi nhánh</label>
                @if($isSuperAdmin)
                <select name="ma_chi_nhanh" required class="w-full rounded-xl border border-[#522C25]/15 px-3 py-2 text-sm">
                    @foreach($branches as $b)
                        <option value="{{ $b->ma_chi_nhanh }}" @selected(old('ma_chi_nhanh')===$b->ma_chi_nhanh)>{{ $b->ten_chi_nhanh }}</option>
                    @endforeach
                </select>
                @else
                <input value="{{ $myBranch }}" disabled class="w-full rounded-xl border border-[#522C25]/15 bg-[#F2F2F2] px-3 py-2 text-sm text-[#522C25]/70">
                <input type="hidden" name="ma_chi_nhanh" value="{{ $myBranch }}">
                @endif
            </div>
            <div class="flex items-end">
                <button class="rounded-xl bg-[#1A1A1A] px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
                    + Tạo tài khoản
                </button>
            </div>
        </form>
    </div>

    {{-- ── Danh sách tài khoản ───────────────────────────────── --}}
    <div class="rounded-3xl border border-[#522C25]/10 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-[#522C25]/10 px-6 py-4">
            <h2 class="text-base font-semibold">Danh sách tài khoản ({{ $accounts->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F8F6F5] text-left text-xs uppercase tracking-wide text-[#522C25]/60">
                    <tr>
                        <th class="px-4 py-3">Đăng nhập</th>
                        <th class="px-4 py-3">Họ tên / Email</th>
                        <th class="px-4 py-3">Chi nhánh</th>
                        <th class="px-4 py-3">Vai trò</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#522C25]/8">
                    @foreach($accounts as $a)
                    @php
                        $isSelf = $a->ma_tai_khoan === session('tai_khoan_id');
                        // Không thao tác được người ngang/cao hơn; quản lý chỉ trong chi nhánh mình
                        $targetRank = $rank[$a->chuc_vu] ?? 0;
                        $locked = $isSelf || $targetRank >= $myRank
                            || (! $isSuperAdmin && $a->ma_chi_nhanh !== $myBranch);
                    @endphp
                    <tr class="align-middle">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $a->ten_tk }}</p>
                            <p class="text-xs text-[#522C25]/50">{{ $a->ma_tai_khoan }} · {{ $a->ma_nv }}</p>
                        </td>
                        <td class="px-4 py-3">
                            {{ $a->ten_nv }}
                            @if($a->email)<p class="text-xs text-[#522C25]/50">{{ $a->email }}</p>@endif
                            @if($a->sdt)<p class="text-xs text-[#522C25]/50">{{ $a->sdt }}</p>@endif
                        </td>
                        <td class="px-4 py-3">{{ $a->ma_chi_nhanh }}</td>

                        @if($locked)
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleBadge[$a->chuc_vu] ?? 'bg-gray-100' }}">{{ $roleLabels[$a->chuc_vu] ?? $a->chuc_vu }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $a->trang_thai==='active' ? 'bg-green-100 text-green-700' : ($a->trang_thai==='cho_xac_minh' ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600') }}">
                                    {{ $a->trang_thai==='active' ? 'Đang dùng' : ($a->trang_thai==='cho_xac_minh' ? 'Chờ kích hoạt' : 'Đã khoá') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-[#522C25]/40">
                                {{ $isSelf ? 'Tài khoản của bạn' : '—' }}
                            </td>
                        @else
                            <form method="POST" action="{{ route('nhanvien.update', $a->ma_tai_khoan) }}" class="contents">
                                @csrf
                                @method('PUT')
                                <td class="px-4 py-3">
                                    <select name="chuc_vu" class="rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-xs">
                                        @foreach($roles as $key => $label)
                                            <option value="{{ $key }}" @selected($a->chuc_vu===$key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    @if($a->trang_thai==='cho_xac_minh')
                                        <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Chờ kích hoạt</span>
                                    @endif
                                    <select name="trang_thai" class="rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-xs">
                                        <option value="active"   @selected($a->trang_thai==='active')>Đang dùng</option>
                                        <option value="inactive" @selected($a->trang_thai==='inactive')>Khoá</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        @if($isSuperAdmin)
                                        <select name="ma_chi_nhanh" title="Chuyển chi nhánh" class="rounded-lg border border-[#522C25]/15 px-2 py-1.5 text-xs">
                                            @foreach($branches as $b)
                                                <option value="{{ $b->ma_chi_nhanh }}" @selected($a->ma_chi_nhanh===$b->ma_chi_nhanh)>{{ $b->ma_chi_nhanh }}</option>
                                            @endforeach
                                        </select>
                                        <label class="flex items-center gap-1 text-xs text-[#522C25]/70">
                                            <input type="checkbox" name="reset_mat_khau" value="1"> Đặt lại MK
                                        </label>
                                        @endif
                                        <button class="rounded-lg bg-[#1A1A1A] px-3 py-1.5 text-xs font-semibold text-white hover:bg-black">Lưu</button>
                                    </div>
                                </td>
                            </form>
                        @endif
                    </tr>
                    @if(! $locked)
                    <tr>
                        <td colspan="6" class="px-4 pb-3">
                            <div class="flex items-center justify-end gap-4">
                                @if($a->trang_thai==='cho_xac_minh')
                                <form method="POST" action="{{ route('nhanvien.resend', $a->ma_tai_khoan) }}">
                                    @csrf
                                    <button class="text-xs font-semibold text-[#8B5A2B] hover:underline">Gửi lại email kích hoạt</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('nhanvien.destroy', $a->ma_tai_khoan) }}"
                                      onsubmit="return confirm('Xóa tài khoản {{ $a->ten_tk }}?');">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-[#BB0011] hover:underline">Xóa tài khoản</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
