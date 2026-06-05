<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Dọn tài khoản nhân viên TẠO NHƯNG CHƯA KÍCH HOẠT (email không tồn tại /
 * không xác nhận). Quy trình 2 bước (có THÔNG BÁO TRƯỚC):
 *   1) Cảnh báo: tài khoản sắp tới hạn → gửi email nhắc (1 lần) + ghi EMAIL_LOG.
 *   2) Xoá: tài khoản quá hạn → xoá cả TAI_KHOAN lẫn NHAN_VIEN tạo kèm.
 */
class PurgeUnconfirmedAccounts extends Command
{
    protected $signature = 'accounts:purge-unconfirmed {--days=} {--warn-days=}';
    protected $description = 'Canh bao truoc va xoa tai khoan chua kich hoat qua N ngay';

    public function handle(): int
    {
        $days     = (int) ($this->option('days') ?: config('security.purge_unconfirmed_days', 7));
        $warnDays = (int) ($this->option('warn-days') ?: config('security.purge_warn_days', 2));

        $hanXoa    = now()->subDays($days);                 // tạo trước thời điểm này → xoá
        $hanCanhBao = now()->subDays(max(0, $days - $warnDays)); // tạo trước thời điểm này → cảnh báo

        // ── (1) CẢNH BÁO TRƯỚC ──────────────────────────────────────
        $sapXoa = DB::table('TAI_KHOAN as t')
            ->join('NHAN_VIEN as n', 'n.ma_nv', '=', 't.ma_nv')
            ->where('t.trang_thai', 'cho_xac_minh')
            ->whereNotNull('t.tao_luc')
            ->where('t.tao_luc', '<', $hanCanhBao)
            ->where('t.tao_luc', '>=', $hanXoa)            // chưa tới hạn xoá
            ->select('t.ma_tai_khoan', 't.ten_tk', 'n.email')
            ->get();

        $soCanhBao = 0;
        foreach ($sapXoa as $acc) {
            $email = $this->plainEmail($acc->email);
            if (! $email) continue;
            // Chỉ cảnh báo 1 lần: bỏ qua nếu đã có log 'canh_bao_xoa' cho tài khoản này.
            $daCanhBao = EmailLog::where('loai', 'canh_bao_xoa')->where('ma_tham_chieu', $acc->ma_tai_khoan)->exists();
            if ($daCanhBao) continue;

            $conLai = $warnDays;
            try {
                Mail::raw(
                    "Tai khoan 8AM Coffee ({$acc->ten_tk}) cua ban chua kich hoat. "
                    . "Tai khoan se bi xoa sau {$conLai} ngay neu khong kich hoat. "
                    . "Vui long mo email kich hoat truoc do hoac lien he quan ly.",
                    fn($m) => $m->to($email)->subject('[8AM Coffee] Tai khoan sap bi xoa do chua kich hoat')
                );
                EmailLog::ghi('canh_bao_xoa', $email, true, 'Canh bao xoa tai khoan chua kich hoat', $acc->ma_tai_khoan);
                $soCanhBao++;
            } catch (\Throwable $e) {
                EmailLog::ghi('canh_bao_xoa', $email, false, 'Canh bao xoa tai khoan chua kich hoat', $acc->ma_tai_khoan, $e->getMessage());
            }
        }

        // ── (2) XOÁ TÀI KHOẢN QUÁ HẠN ───────────────────────────────
        $accounts = DB::table('TAI_KHOAN')
            ->where('trang_thai', 'cho_xac_minh')
            ->whereNotNull('tao_luc')
            ->where('tao_luc', '<', $hanXoa)
            ->get(['ma_tai_khoan', 'ma_nv', 'ten_tk']);

        $soXoa = 0;
        foreach ($accounts as $acc) {
            DB::transaction(function () use ($acc, &$soXoa) {
                DB::table('TAI_KHOAN')->where('ma_tai_khoan', $acc->ma_tai_khoan)->delete();
                if (DB::table('TAI_KHOAN')->where('ma_nv', $acc->ma_nv)->count() === 0) {
                    DB::table('NHAN_VIEN')->where('ma_nv', $acc->ma_nv)->delete();
                }
                $soXoa++;
            });
            $this->line("  - Xoa {$acc->ten_tk} ({$acc->ma_tai_khoan})");
        }

        $this->info("Da canh bao {$soCanhBao} tai khoan, xoa {$soXoa} tai khoan chua kich hoat qua {$days} ngay.");
        return self::SUCCESS;
    }

    /** Giải mã email nếu đang mã hóa; nếu là plaintext thì trả nguyên (tương thích trước/sau khi bật mã hóa). */
    private function plainEmail(?string $value): ?string
    {
        if (! $value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value; // chưa mã hóa
        }
    }
}
