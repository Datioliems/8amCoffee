<?php

namespace App\Support;

use App\Models\TaiKhoan;

/**
 * Phân quyền theo danh mục quyền + vai trò, có override theo từng tài khoản.
 *
 *  - superadmin LUÔN có mọi quyền (bỏ qua kiểm tra).
 *  - Quyền hiệu lực của tài khoản = cột `quyen` (override) nếu có, else mặc định vai trò.
 *  - Người đang đăng nhập: đọc từ session('quyen') (đặt lúc login), fallback mặc định vai trò.
 */
class Perm
{
    /** Danh mục quyền: key => nhãn. */
    public static function catalog(): array
    {
        return (array) config('permissions.catalog', []);
    }

    public static function label(string $key): string
    {
        return (string) (self::catalog()[$key] ?? $key);
    }

    /** Quyền mặc định của một vai trò (đã mở rộng '*' thành toàn bộ catalog). */
    public static function roleDefaults(?string $role): array
    {
        $r = (array) config('permissions.roles.' . (string) $role, []);
        if (in_array('*', $r, true)) {
            return array_keys(self::catalog());
        }
        return array_values(array_intersect($r, array_keys(self::catalog())));
    }

    /** Quyền HIỆU LỰC của một tài khoản. */
    public static function effectiveFor(TaiKhoan $tk): array
    {
        if ($tk->chuc_vu === 'superadmin') {
            return array_keys(self::catalog());
        }
        $override = $tk->quyen; // array|null (đã cast)
        if (is_array($override)) {
            return array_values(array_intersect($override, array_keys(self::catalog())));
        }
        return self::roleDefaults($tk->chuc_vu);
    }

    /** Quyền hiệu lực của NGƯỜI ĐANG ĐĂNG NHẬP. */
    public static function current(): array
    {
        if (session('chuc_vu') === 'superadmin') {
            return array_keys(self::catalog());
        }
        $q = session('quyen');
        return is_array($q) ? $q : self::roleDefaults(session('chuc_vu'));
    }

    public static function can(string $key): bool
    {
        if (session('chuc_vu') === 'superadmin') {
            return true;
        }
        $cur = self::current();
        return in_array('*', $cur, true) || in_array($key, $cur, true);
    }

    public static function canAny(array $keys): bool
    {
        foreach ($keys as $k) {
            if (self::can($k)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cấp bậc: superadmin > admin > nhan_vien. Editor có được sửa quyền của target?
     * - superadmin: sửa mọi tài khoản (trừ tài khoản superadmin khác — vô nghĩa vì luôn full).
     * - admin: chỉ sửa nhân viên CÙNG chi nhánh.
     */
    public static function canEdit(string $editorRole, ?string $editorBranch, TaiKhoan $target): bool
    {
        if ($editorRole === 'superadmin') {
            return $target->chuc_vu !== 'superadmin';
        }
        if ($editorRole === 'admin') {
            return $target->chuc_vu === 'nhan_vien'
                && $editorBranch !== null
                && optional($target->nhanVien)->ma_chi_nhanh === $editorBranch;
        }
        return false;
    }
}
