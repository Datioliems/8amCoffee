<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DinhMucSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['MON001','NL001',18.00,'2 shot espresso Arabica'],
            ['MON001','NL005',30.00,'Nước pha espresso'],
            ['MON002','NL001',18.00,'2 shot espresso'],
            ['MON002','NL005',150.00,'Nước nóng pha loãng'],
            ['MON003','NL001',18.00,'1 shot espresso'],
            ['MON003','NL003',180.00,'Sữa tươi steam'],
            ['MON004','NL001',18.00,'1 shot espresso'],
            ['MON004','NL003',120.00,'Sữa tươi'],
            ['MON004','NL004',20.00,'Sữa đặc'],
            ['MON004','NL006',30.00,'Kem béo'],
            ['MON005','NL001',18.00,'1 shot espresso'],
            ['MON005','NL003',150.00,'Sữa tươi'],
            ['MON005','NL006',30.00,'Kem topping'],
            ['MON006','NL001',18.00,'1 shot espresso'],
            ['MON006','NL003',120.00,'Sữa tươi'],
            ['MON006','NL004',20.00,'Sữa đặc'],
            ['MON006','NL006',35.00,'Kem muối'],
            ['MON007','NL001',18.00,'1 shot espresso'],
            ['MON007','NL004',25.00,'Sữa đặc'],
            ['MON007','NL006',40.00,'Kem trứng'],
            ['MON008','NL001',30.00,'Cold brew coffee'],
            ['MON008','NL006',40.00,'Kem mặn'],
            ['MON008','NL024',25.00,'Mứt chanh leo tiramisu'],
            ['MON009','NL001',18.00,'1 shot espresso'],
            ['MON009','NL003',170.00,'Sữa tươi'],
            ['MON009','NL009',5.00,'Bột gừng'],
            ['MON010','NL001',20.00,'Cà phê hand brew'],
            ['MON010','NL005',280.00,'Nước pha V60'],
            ['MON011','NL001',20.00,'Cà phê hand brew'],
            ['MON011','NL005',280.00,'Nước pha Origami'],
            ['MON012','NL001',35.00,'Cà phê cold brew'],
            ['MON012','NL005',220.00,'Nước ủ cold brew'],
            ['MON013','NL001',35.00,'Cold brew'],
            ['MON013','NL012',35.00,'Mứt mơ'],
            ['MON014','NL001',35.00,'Cold brew'],
            ['MON014','NL013',35.00,'Mứt me'],
            ['MON015','NL001',35.00,'Cold brew'],
            ['MON015','NL017',160.00,'Tonic water'],
            ['MON016','NL001',35.00,'Cold brew'],
            ['MON016','NL018',25.00,'Mứt đào'],
            ['MON016','NL014',20.00,'Syrup dưa lưới'],
            ['MON017','NL002',18.00,'Robusta espresso'],
            ['MON017','NL005',30.00,'Nước pha espresso'],
            ['MON018','NL002',18.00,'Robusta espresso'],
            ['MON018','NL004',25.00,'Sữa đặc'],
            ['MON019','NL002',18.00,'Robusta espresso'],
            ['MON019','NL003',140.00,'Sữa tươi'],
            ['MON019','NL004',25.00,'Sữa đặc'],
            ['MON020','NL002',18.00,'Robusta espresso'],
            ['MON020','NL020',120.00,'Sữa chua'],
            ['MON020','NL004',20.00,'Sữa đặc'],
            ['MON021','NL021',25.00,'Ca cao nguyên chất'],
            ['MON021','NL003',160.00,'Sữa tươi'],
            ['MON022','NL022',30.00,'Chanh tươi'],
            ['MON022','NL023',20.00,'Xí muội'],
            ['MON022','NL005',150.00,'Nước pha'],
            ['MON023','NL019',45.00,'Chanh leo tươi'],
            ['MON023','NL024',25.00,'Mứt chanh leo'],
            ['MON023','NL006',30.00,'Kem béo'],
            ['MON024','NL025',8.00,'Lục trà'],
            ['MON024','NL026',35.00,'Mứt ổi'],
            ['MON024','NL005',180.00,'Nước pha trà'],
            ['MON025','NL025',8.00,'Lục trà'],
            ['MON025','NL018',30.00,'Mứt đào'],
            ['MON025','NL022',20.00,'Chanh tươi'],
            ['MON026','NL028',1.00,'Bánh sừng bò plain'],
            ['MON027','NL029',1.00,'Bánh sừng bò socola'],
            ['MON028','NL030',50.00,'Hạt sen sấy'],
        ];

        // Chỉ seed định mức cho nguyên liệu & món THỰC SỰ tồn tại (tránh lỗi FK
        // khi dữ liệu seed bị lệch — vd tham chiếu nguyên liệu chưa được tạo).
        $nlCo  = DB::table('NGUYEN_LIEU')->pluck('ma_nl')->flip();
        $monCo = DB::table('MON')->pluck('ma_mon')->flip();

        $boQua = 0;
        foreach ($rows as [$maMon, $maNl, $soLuong, $moTa]) {
            if (! isset($nlCo[$maNl]) || ! isset($monCo[$maMon])) {
                $boQua++;
                continue;
            }
            DB::table('DINH_MUC')->updateOrInsert(
                ['ma_mon' => $maMon, 'ma_nl' => $maNl],
                ['so_luong_dung' => $soLuong, 'mo_ta' => $moTa]
            );
        }

        if ($boQua > 0) {
            $this->command?->warn("DinhMucSeeder: bỏ qua {$boQua} dòng do nguyên liệu/món chưa tồn tại.");
        }
    }
}
