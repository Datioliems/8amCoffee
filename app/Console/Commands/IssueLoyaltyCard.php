<?php

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;

/**
 * Phát thẻ thành viên RFID + cộng điểm khởi tạo theo tổng chi tiêu của khách.
 *
 * Ví dụ:
 *   php artisan loyalty:issue-card 04A1B2C3 0901234567 --branch=CN001 --device=ARD001
 */
class IssueLoyaltyCard extends Command
{
    protected $signature = 'loyalty:issue-card
        {uid : UID RFID của thẻ (hex)}
        {sdt : Số điện thoại khách (dùng blind index để tra)}
        {--branch= : Mã chi nhánh phát thẻ}
        {--device= : Mã thiết bị đầu đọc}';

    protected $description = 'Phát thẻ thành viên RFID cho khách + cộng điểm khởi tạo theo tổng chi tiêu.';

    public function handle(LoyaltyService $loyalty): int
    {
        $res = $loyalty->issueCard(
            (string) $this->argument('uid'),
            (string) $this->argument('sdt'),
            $this->option('branch') ?: null,
            $this->option('device') ?: null,
        );

        if (! ($res['ok'] ?? false)) {
            $this->error($res['message'] ?? 'Phát thẻ thất bại.');
            return self::FAILURE;
        }

        $this->info($res['message']);
        $this->table(
            ['Mã thẻ', 'Khách', 'Tổng chi tiêu', 'Điểm khởi tạo', 'Hạng'],
            [[
                $res['card']->ma_the,
                $res['ten_kh'] ?: $res['card']->ma_kh,
                number_format($res['spend'] ?? 0, 0, ',', '.') . 'đ',
                $res['diem'] ?? 0,
                $res['card']->hang_the,
            ]]
        );

        return self::SUCCESS;
    }
}
