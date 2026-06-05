<?php

use App\Support\Pii;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mã hóa PII trên các bảng hiện có (KHACH_HANG, NHAN_VIEN, ORDERS, NHA_CUNG_CAP).
 * Viết IDEMPOTENT + dùng SQL thô để:
 *   - Xóa unique index trên cccd trước khi đổi sang TEXT (1170 BLOB/TEXT key length).
 *   - Chỉ đổi cột khi chưa phải TEXT; chỉ thêm sdt_hash khi chưa có.
 *   - Chạy lại an toàn dù DB đang ở trạng thái nửa chừng.
 */
return new class extends Migration
{
    public function up(): void
    {
        $conn = Schema::getConnection();
        $db   = $conn->getDatabaseName();

        $isText = function (string $table, string $col) use ($conn, $db): bool {
            $r = $conn->selectOne(
                "SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?",
                [$db, $table, $col]
            );
            return $r && strtolower($r->DATA_TYPE) === 'text';
        };
        $indexExists = function (string $table, string $index) use ($conn, $db): bool {
            return (bool) $conn->selectOne(
                "SELECT 1 AS x FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1",
                [$db, $table, $index]
            );
        };

        // 1) Bỏ UNIQUE index trên cccd (không thể index cột TEXT mà thiếu key length).
        if ($indexExists('NHAN_VIEN', 'nhan_vien_cccd_unique')) {
            $conn->statement('ALTER TABLE `NHAN_VIEN` DROP INDEX `nhan_vien_cccd_unique`');
        }

        // 2) Đổi cột PII sang TEXT (chỉ khi chưa phải text).
        $cols = [
            'KHACH_HANG'   => ['ten_kh', 'sdt'],
            'NHAN_VIEN'    => ['email', 'sdt', 'cccd', 'dia_chi'],
            'ORDERS'       => ['ten_khach', 'sdt_khach'],
            'NHA_CUNG_CAP' => ['sdt', 'email', 'dia_chi'],
        ];
        foreach ($cols as $t => $cs) {
            foreach ($cs as $c) {
                if (! $isText($t, $c)) {
                    $conn->statement("ALTER TABLE `{$t}` MODIFY `{$c}` TEXT NULL");
                }
            }
        }

        // 3) Thêm blind index sdt_hash (chỉ khi chưa có).
        if (! Schema::hasColumn('KHACH_HANG', 'sdt_hash')) {
            Schema::table('KHACH_HANG', function (Blueprint $table) {
                $table->char('sdt_hash', 64)->nullable()->after('sdt');
                $table->index('sdt_hash');
            });
        }

        // 4) Mã hóa dữ liệu cũ + điền blind index (idempotent: encIfPlain bỏ qua ô đã mã hóa).
        foreach (DB::table('KHACH_HANG')->get() as $r) {
            DB::table('KHACH_HANG')->where('ma_kh', $r->ma_kh)->update([
                'ten_kh'   => Pii::encIfPlain($r->ten_kh),
                'sdt'      => Pii::encIfPlain($r->sdt),
                'sdt_hash' => Pii::phoneHash(Pii::tryDecrypt($r->sdt)),
            ]);
        }
        foreach (DB::table('NHAN_VIEN')->get() as $r) {
            DB::table('NHAN_VIEN')->where('ma_nv', $r->ma_nv)->update([
                'email'   => Pii::encIfPlain($r->email),
                'sdt'     => Pii::encIfPlain($r->sdt),
                'cccd'    => Pii::encIfPlain($r->cccd ?? null),
                'dia_chi' => Pii::encIfPlain($r->dia_chi ?? null),
            ]);
        }
        foreach (DB::table('ORDERS')->get() as $r) {
            if ($r->ten_khach === null && $r->sdt_khach === null) continue;
            DB::table('ORDERS')->where('ma_order', $r->ma_order)->update([
                'ten_khach' => Pii::encIfPlain($r->ten_khach),
                'sdt_khach' => Pii::encIfPlain($r->sdt_khach),
            ]);
        }
        foreach (DB::table('NHA_CUNG_CAP')->get() as $r) {
            DB::table('NHA_CUNG_CAP')->where('ma_ncc', $r->ma_ncc)->update([
                'sdt'     => Pii::encIfPlain($r->sdt),
                'email'   => Pii::encIfPlain($r->email),
                'dia_chi' => Pii::encIfPlain($r->dia_chi),
            ]);
        }
    }

    public function down(): void
    {
        // Giải mã trả lại (best-effort) trước khi thu nhỏ cột.
        foreach (DB::table('KHACH_HANG')->get() as $r) {
            DB::table('KHACH_HANG')->where('ma_kh', $r->ma_kh)->update([
                'ten_kh' => Pii::tryDecrypt($r->ten_kh),
                'sdt'    => Pii::tryDecrypt($r->sdt),
            ]);
        }
        foreach (DB::table('NHAN_VIEN')->get() as $r) {
            DB::table('NHAN_VIEN')->where('ma_nv', $r->ma_nv)->update([
                'email'   => Pii::tryDecrypt($r->email),
                'sdt'     => Pii::tryDecrypt($r->sdt),
                'cccd'    => Pii::tryDecrypt($r->cccd ?? null),
                'dia_chi' => Pii::tryDecrypt($r->dia_chi ?? null),
            ]);
        }
        foreach (DB::table('ORDERS')->get() as $r) {
            DB::table('ORDERS')->where('ma_order', $r->ma_order)->update([
                'ten_khach' => Pii::tryDecrypt($r->ten_khach),
                'sdt_khach' => Pii::tryDecrypt($r->sdt_khach),
            ]);
        }
        foreach (DB::table('NHA_CUNG_CAP')->get() as $r) {
            DB::table('NHA_CUNG_CAP')->where('ma_ncc', $r->ma_ncc)->update([
                'sdt'     => Pii::tryDecrypt($r->sdt),
                'email'   => Pii::tryDecrypt($r->email),
                'dia_chi' => Pii::tryDecrypt($r->dia_chi),
            ]);
        }
        if (Schema::hasColumn('KHACH_HANG', 'sdt_hash')) {
            Schema::table('KHACH_HANG', function (Blueprint $table) {
                $table->dropIndex(['sdt_hash']);
                $table->dropColumn('sdt_hash');
            });
        }
    }
};
