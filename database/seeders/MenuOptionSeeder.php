<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuOptionSeeder extends Seeder
{
    public function run(): void
    {
        $toppings = [
            ['ma_topping' => 'TP001', 'ten_topping' => 'Kem mặn', 'gia_them' => 10000, 'canh_bao' => 'Có sữa/lactose.', 'trang_thai' => 'active'],
            ['ma_topping' => 'TP002', 'ten_topping' => 'Caramel', 'gia_them' => 8000, 'canh_bao' => 'Có đường, không phù hợp người cần kiêng ngọt.', 'trang_thai' => 'active'],
            ['ma_topping' => 'TP003', 'ten_topping' => 'Shot espresso', 'gia_them' => 12000, 'canh_bao' => 'Tăng lượng caffeine.', 'trang_thai' => 'active'],
            ['ma_topping' => 'TP004', 'ten_topping' => 'Sữa tươi', 'gia_them' => 8000, 'canh_bao' => 'Có sữa/lactose.', 'trang_thai' => 'active'],
            ['ma_topping' => 'TP005', 'ten_topping' => 'Trân châu', 'gia_them' => 10000, 'canh_bao' => 'Có tinh bột và đường.', 'trang_thai' => 'active'],
            ['ma_topping' => 'TP006', 'ten_topping' => 'Thạch konjac', 'gia_them' => 10000, 'canh_bao' => 'Trẻ nhỏ nên dùng cẩn thận.', 'trang_thai' => 'active'],
            ['ma_topping' => 'TP007', 'ten_topping' => 'Sương sáo', 'gia_them' => 8000, 'canh_bao' => null, 'trang_thai' => 'active'],
            ['ma_topping' => 'TP008', 'ten_topping' => 'Thạch dừa', 'gia_them' => 10000, 'canh_bao' => null, 'trang_thai' => 'active'],
        ];

        foreach ($toppings as $topping) {
            DB::table('TOPPING')->updateOrInsert(['ma_topping' => $topping['ma_topping']], $topping);
        }

        $options = [];
        $monIds = DB::table('MON')->pluck('ma_mon')->all();
        $sequence = 1;

        foreach ($monIds as $maMon) {
            foreach (['Đá', 'Nóng', 'Ít đá', 'Không đá'] as $index => $label) {
                $options[] = $this->option($sequence++, $maMon, 'temperature', $label, 0, $index === 0, $index);
            }

            foreach (['0%', '30%', '50%', '70%', '100%'] as $index => $label) {
                $options[] = $this->option($sequence++, $maMon, 'sweetness', $label, 0, $index === 2, $index);
            }
        }

        foreach ($options as $option) {
            DB::table('MON_OPTION')->updateOrInsert(['ma_option' => $option['ma_option']], $option);
        }
    }

    private function option(int $sequence, string $maMon, string $type, string $name, int $price, bool $required, int $order): array
    {
        return [
            'ma_option' => 'OP' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'ma_mon' => $maMon,
            'loai_option' => $type,
            'ten_option' => $name,
            'gia_them' => $price,
            'bat_buoc' => $required,
            'thu_tu' => $order,
            'trang_thai' => 'active',
        ];
    }
}
