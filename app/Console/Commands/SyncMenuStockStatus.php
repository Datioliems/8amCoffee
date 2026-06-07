<?php

namespace App\Console\Commands;

use App\Models\Mon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMenuStockStatus extends Command
{
    protected $signature   = 'menu:sync-stock-status';
    protected $description = 'Tự động ẩn món hết nguyên liệu và hiện lại khi tồn kho đủ';

    public function handle(): int
    {
        // Mon nào có ít nhất 1 nguyên liệu được theo dõi mà tổng tồn kho (toàn hệ thống) < định mức
        $outOfStockMonIds = DB::table('DINH_MUC as dm')
            ->joinSub(
                DB::table('TON_KHO')
                    ->select('ma_nl', DB::raw('SUM(sl_ton_kho_he_thong) as total_stock'))
                    ->groupBy('ma_nl'),
                'tk',
                fn ($join) => $join->on('tk.ma_nl', '=', 'dm.ma_nl')
            )
            ->where('dm.so_luong_dung', '>', 0)
            ->whereRaw('tk.total_stock < dm.so_luong_dung')
            ->distinct()
            ->pluck('dm.ma_mon');

        // ── 1. Tự động ẩn ───────────────────────────────────────────────────
        // active + tu_dong_an IS NULL (chưa bị ẩn bao giờ, chưa override) + hết kho
        $toHide = Mon::where('trang_thai', 'active')
            ->whereNull('tu_dong_an')
            ->whereIn('ma_mon', $outOfStockMonIds)
            ->get();

        foreach ($toHide as $mon) {
            $mon->update(['trang_thai' => 'het_hang', 'tu_dong_an' => 1]);
            $this->line("  ↓ Ẩn: {$mon->ten_mon} ({$mon->ma_mon})");
        }

        // ── 2. Tự động hiện lại ─────────────────────────────────────────────
        // het_hang + tu_dong_an = 1 (đã tự ẩn) + tồn kho đã đủ trở lại
        $toRestore = Mon::where('trang_thai', 'het_hang')
            ->where('tu_dong_an', 1)
            ->whereNotIn('ma_mon', $outOfStockMonIds)
            ->get();

        foreach ($toRestore as $mon) {
            $mon->update(['trang_thai' => 'active', 'tu_dong_an' => null]);
            $this->line("  ↑ Hiện: {$mon->ten_mon} ({$mon->ma_mon})");
        }

        $hidden   = $toHide->count();
        $restored = $toRestore->count();

        $this->info("Xong — ẩn: {$hidden}, hiện lại: {$restored}.");

        if ($hidden > 0 || $restored > 0) {
            Log::info("menu:sync-stock-status — ẩn {$hidden} món, hiện lại {$restored} món.");
        }

        return Command::SUCCESS;
    }
}
