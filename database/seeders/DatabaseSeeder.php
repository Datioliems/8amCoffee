<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed dữ liệu từ file SQL Server đã có sẵn.
     * Chạy: php artisan db:seed
     * 
     * Lưu ý: Vì database đã được tạo bằng file 8am_coffee_database.sql
     * (chạy trực tiếp trên SQL Server), seeder này chỉ kiểm tra
     * và bỏ qua nếu dữ liệu đã tồn tại.
     */
    public function run(): void
    {
        // Seed dữ liệu nền (chỉ chạy khi DB còn trống)
        if (DB::table('CHI_NHANH')->count() === 0) {
            $this->command->info('Chạy seeders nền...');
            $this->call([
                ChiNhanhSeeder::class,
                NhanVienSeeder::class,
                TaiKhoanSeeder::class,
                DanhMucSeeder::class,
                MonSeeder::class,
                NguyenLieuSeeder::class,
                BanSeeder::class,
                NhaCungCapSeeder::class,
                DinhMucSeeder::class,
                TonKhoSeeder::class,
                MenuOptionSeeder::class,
            ]);
        } else {
            $this->command->info('Đã có dữ liệu nền, bỏ qua seeders nền.');
        }

        // Seeder bổ sung (idempotent - luôn chạy để đảm bảo đủ dữ liệu mới)
        $this->command->info('Chạy seeders bổ sung (superadmin, chi nhánh demo, bàn)...');
        $this->call([
            BanSeeder::class,          // 17 bàn + số ghế (updateOrInsert)
            SuperAdminSeeder::class,   // superadmin / Admin@123
            ChiNhanh2DemoSeeder::class // CN002 + 6 bàn + manager_hcm
        ]);

        $this->command->info('✅ Seed hoàn tất!');
    }
}
