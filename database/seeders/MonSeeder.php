<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonSeeder extends Seeder
{
    public function run(): void
    {
        $mons = [
            ['ma_mon'=>'MON001','ten_mon'=>'Espresso','don_gia'=>35000,'mo_ta'=>'Double shot espresso','hinh_anh'=>'espresso.jpg','ma_danh_muc'=>'DM001','trang_thai'=>'active'],
            ['ma_mon'=>'MON002','ten_mon'=>'Americano','don_gia'=>40000,'mo_ta'=>'Espresso, nước','hinh_anh'=>'americano.jpg','ma_danh_muc'=>'DM001','trang_thai'=>'active'],
            ['ma_mon'=>'MON003','ten_mon'=>'Latte','don_gia'=>45000,'mo_ta'=>'Espresso, sữa tươi','hinh_anh'=>'latte.jpg','ma_danh_muc'=>'DM001','trang_thai'=>'active'],
            ['ma_mon'=>'MON004','ten_mon'=>':am êm','don_gia'=>45000,'mo_ta'=>'Espresso, sữa tươi, sữa đặc, kem béo','hinh_anh'=>'cam_em.jpg','ma_danh_muc'=>'DM001','trang_thai'=>'active'],
            ['ma_mon'=>'MON005','ten_mon'=>'Salted Caramel','don_gia'=>50000,'mo_ta'=>'Espresso, sữa tươi, caramel, kem mặn','hinh_anh'=>'salted_caramel.jpg','ma_danh_muc'=>'DM001','trang_thai'=>'active'],
            ['ma_mon'=>'MON006','ten_mon'=>'Cà phê muối','don_gia'=>50000,'mo_ta'=>'Espresso, sữa tươi, muối hồng, kem mặn, sữa đặc','hinh_anh'=>'ca_phe_muoi.jpg','ma_danh_muc'=>'DM001','trang_thai'=>'active'],

            ['ma_mon'=>'MON007','ten_mon'=>'Cà phê trứng','don_gia'=>45000,'mo_ta'=>'Espresso, kem trứng, sữa đặc','hinh_anh'=>'ca_phe_trung.png','ma_danh_muc'=>'DM002','trang_thai'=>'active'],
            ['ma_mon'=>'MON008','ten_mon'=>'Lady Sweet','don_gia'=>60000,'mo_ta'=>'Cold Brew, tiramisu, rượu Bailey, kem mặn','hinh_anh'=>'lady_sweet.jpg','ma_danh_muc'=>'DM002','trang_thai'=>'active'],
            ['ma_mon'=>'MON009','ten_mon'=>'Ginger Latte','don_gia'=>45000,'mo_ta'=>'Espresso, sữa tươi, bột gừng','hinh_anh'=>'ginger_latte.jpg','ma_danh_muc'=>'DM002','trang_thai'=>'active'],

            ['ma_mon'=>'MON010','ten_mon'=>'V60','don_gia'=>60000,'mo_ta'=>'Kenya / Ethiopia','hinh_anh'=>'v60.jpg','ma_danh_muc'=>'DM003','trang_thai'=>'active'],
            ['ma_mon'=>'MON011','ten_mon'=>'Origami','don_gia'=>60000,'mo_ta'=>'Kenya / Ethiopia','hinh_anh'=>'origami.jpg','ma_danh_muc'=>'DM003','trang_thai'=>'active'],

            ['ma_mon'=>'MON012','ten_mon'=>'Cold Brew Original','don_gia'=>50000,'mo_ta'=>'Specialty coffee beans around the world','hinh_anh'=>'cold_brew.jpg','ma_danh_muc'=>'DM004','trang_thai'=>'active'],
            ['ma_mon'=>'MON013','ten_mon'=>'Cold Brew Mơ','don_gia'=>55000,'mo_ta'=>'Cold Brew, mơ','hinh_anh'=>'cold_brew_mo.png','ma_danh_muc'=>'DM004','trang_thai'=>'active'],
            ['ma_mon'=>'MON014','ten_mon'=>'Cold Brew Me','don_gia'=>55000,'mo_ta'=>'Cold Brew, me','hinh_anh'=>'cold_brew.jpg','ma_danh_muc'=>'DM004','trang_thai'=>'active'],
            ['ma_mon'=>'MON015','ten_mon'=>'Cold Brew Tonic','don_gia'=>55000,'mo_ta'=>'Cold Brew, tonic','hinh_anh'=>'tonic.jpg','ma_danh_muc'=>'DM004','trang_thai'=>'active'],
            ['ma_mon'=>'MON016','ten_mon'=>'Cold Brew Nhiệt đới','don_gia'=>55000,'mo_ta'=>'Cold Brew, monin đào, monin dưa lưới','hinh_anh'=>'nhiet_doi.jpg','ma_danh_muc'=>'DM004','trang_thai'=>'active'],

            ['ma_mon'=>'MON017','ten_mon'=>'Cà phê đen','don_gia'=>30000,'mo_ta'=>'Double shot Espresso','hinh_anh'=>'ca_phe_den.webp','ma_danh_muc'=>'DM005','trang_thai'=>'active'],
            ['ma_mon'=>'MON018','ten_mon'=>'Cà phê nâu','don_gia'=>35000,'mo_ta'=>'Espresso, sữa đặc','hinh_anh'=>'ca_phe_nau.jpg','ma_danh_muc'=>'DM005','trang_thai'=>'active'],
            ['ma_mon'=>'MON019','ten_mon'=>'Bạc xỉu','don_gia'=>40000,'mo_ta'=>'Espresso, sữa tươi, sữa đặc','hinh_anh'=>'bac_xiu.jpg','ma_danh_muc'=>'DM005','trang_thai'=>'active'],
            ['ma_mon'=>'MON020','ten_mon'=>'Sữa chua cà phê','don_gia'=>40000,'mo_ta'=>'Espresso, sữa chua, sữa đặc','hinh_anh'=>'sua_chua_ca_phe.jpg','ma_danh_muc'=>'DM005','trang_thai'=>'active'],

            ['ma_mon'=>'MON021','ten_mon'=>'Ca cao','don_gia'=>40000,'mo_ta'=>'Ca cao','hinh_anh'=>'ca_cao.jpg','ma_danh_muc'=>'DM006','trang_thai'=>'active'],
            ['ma_mon'=>'MON022','ten_mon'=>'Chanh xí muội','don_gia'=>50000,'mo_ta'=>'Chanh, xí muội','hinh_anh'=>'chanh_xi_muoi.jpg','ma_danh_muc'=>'DM006','trang_thai'=>'active'],
            ['ma_mon'=>'MON023','ten_mon'=>'Mứt chanh leo','don_gia'=>40000,'mo_ta'=>'Chanh leo, monin Tiramisu, kem béo','hinh_anh'=>'chanh_leo.png','ma_danh_muc'=>'DM006','trang_thai'=>'active'],
            ['ma_mon'=>'MON024','ten_mon'=>'Trà ổi hồng','don_gia'=>40000,'mo_ta'=>'Lục trà, monin ổi','hinh_anh'=>'tra_oi_hong.jpg','ma_danh_muc'=>'DM006','trang_thai'=>'active'],
            ['ma_mon'=>'MON025','ten_mon'=>'Trà chanh đào','don_gia'=>40000,'mo_ta'=>'Lục trà, chanh, monin đào','hinh_anh'=>'tra_chanh_dao.jpg','ma_danh_muc'=>'DM006','trang_thai'=>'active'],

            ['ma_mon'=>'MON026','ten_mon'=>'Bánh sừng bò','don_gia'=>25000,'mo_ta'=>'Bánh sừng bò truyền thống','hinh_anh'=>'banh_sung_bo.jpg','ma_danh_muc'=>'DM007','trang_thai'=>'active'],
            ['ma_mon'=>'MON027','ten_mon'=>'Bánh sừng bò socola','don_gia'=>25000,'mo_ta'=>'Bánh sừng bò vị socola','hinh_anh'=>'banh_sung_bo_socola.jpg','ma_danh_muc'=>'DM007','trang_thai'=>'active'],
            ['ma_mon'=>'MON028','ten_mon'=>'Hạt sen sấy','don_gia'=>50000,'mo_ta'=>'Hạt sen sấy khô','hinh_anh'=>'hat_sen_say.jpg','ma_danh_muc'=>'DM007','trang_thai'=>'active'],
        ];

        foreach ($mons as $mon) {
            DB::table('MON')->updateOrInsert(
                ['ma_mon' => $mon['ma_mon']],
                $mon
            );
        }
    }
}
