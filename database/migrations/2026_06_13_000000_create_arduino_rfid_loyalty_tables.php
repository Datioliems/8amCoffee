<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cơ chế Arduino + RFID + Tích điểm (loyalty) cho 8AM Coffee.
 *
 *  - THIET_BI_ARDUINO : đầu đọc RFID (Arduino/ESP) đặt tại quầy/chi nhánh.
 *  - THE_THANH_VIEN   : thẻ RFID thành viên (tái sử dụng nhiều lần), gắn với 1 khách.
 *  - GIAO_DICH_DIEM   : sổ cái điểm (earn/redeem/khởi tạo) — NGUỒN SỰ THẬT của số dư.
 *  - LICH_SU_QUET_THE : log mọi lần quẹt thẻ từ Arduino (kể cả thẻ lạ) để audit.
 *
 * PII: các bảng này KHÔNG lưu PII trực tiếp; danh tính khách tham chiếu qua KHACH_HANG
 * (tên/SĐT vẫn mã hóa ở bảng gốc, tra cứu qua blind index sdt_hash).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Thiết bị Arduino / đầu đọc RFID
        if (! Schema::hasTable('THIET_BI_ARDUINO')) {
            Schema::create('THIET_BI_ARDUINO', function (Blueprint $table) {
                $table->string('ma_thiet_bi', 20)->primary();          // ARD001
                $table->string('ten_thiet_bi', 100);
                $table->string('ma_chi_nhanh', 10);
                $table->string('vi_tri', 100)->nullable();             // "Quầy thu ngân"
                $table->char('api_key_hash', 64)->nullable();          // sha256(token) — thiết bị gửi token để xác thực
                $table->string('trang_thai', 20)->default('hoat_dong'); // hoat_dong | tam_dung
                $table->dateTime('lan_ket_noi_cuoi')->nullable();
                $table->string('ip_cuoi', 45)->nullable();
                $table->dateTime('tao_luc')->useCurrent();

                $table->index('ma_chi_nhanh');
                $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH')->cascadeOnUpdate();
            });
        }

        // 2) Thẻ RFID thành viên (tái sử dụng nhiều lần)
        if (! Schema::hasTable('THE_THANH_VIEN')) {
            Schema::create('THE_THANH_VIEN', function (Blueprint $table) {
                $table->string('ma_the', 20)->primary();               // TV000001
                $table->string('uid_rfid', 32)->unique();              // UID vật lý của thẻ (hex), do đầu đọc gửi lên
                $table->string('ma_kh', 10)->nullable();               // chủ thẻ; NULL = thẻ trắng chưa phát
                $table->integer('diem_hien_tai')->default(0);          // số dư điểm (denormalize để Arduino đọc nhanh)
                $table->unsignedBigInteger('tong_diem_tich_luy')->default(0); // tổng điểm từng tích (để xếp hạng)
                $table->string('hang_the', 20)->default('thuong');     // thuong | bac | vang | kim_cuong
                $table->string('trang_thai', 20)->default('hoat_dong'); // hoat_dong | khoa | mat
                $table->date('ngay_phat')->nullable();
                $table->string('ma_chi_nhanh', 10)->nullable();        // chi nhánh phát thẻ
                $table->string('ghi_chu', 255)->nullable();
                $table->dateTime('tao_luc')->useCurrent();

                $table->index('ma_kh');
                $table->index('trang_thai');
                $table->foreign('ma_kh')->references('ma_kh')->on('KHACH_HANG')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH')->nullOnDelete()->cascadeOnUpdate();
            });
        }

        // 3) Sổ cái điểm — nguồn sự thật của số dư (số dư = SUM(so_diem))
        if (! Schema::hasTable('GIAO_DICH_DIEM')) {
            Schema::create('GIAO_DICH_DIEM', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('ma_the', 20);
                $table->string('ma_kh', 10)->nullable();               // denormalize để truy vấn
                // khoi_tao | tich_diem | doi_diem | dieu_chinh | het_han
                $table->string('loai', 20);
                $table->integer('so_diem');                            // CÓ DẤU: + tích / - đổi
                $table->integer('so_diem_sau')->nullable();            // snapshot số dư sau giao dịch
                $table->decimal('so_tien_lien_quan', 12, 0)->nullable(); // tiền hóa đơn (tích) hoặc tiền giảm (đổi)
                $table->string('ma_order', 20)->nullable();
                $table->string('ma_hoa_don', 20)->nullable();
                $table->string('ma_thiet_bi', 20)->nullable();         // đầu đọc thực hiện
                $table->string('mo_ta', 255)->nullable();
                $table->dateTime('thoi_gian')->useCurrent();

                $table->index(['ma_the', 'thoi_gian']);
                $table->index('ma_kh');
                $table->index('loai');
                $table->foreign('ma_the')->references('ma_the')->on('THE_THANH_VIEN')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('ma_kh')->references('ma_kh')->on('KHACH_HANG')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('ma_order')->references('ma_order')->on('ORDERS')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('ma_hoa_don')->references('ma_hoa_don')->on('HOA_DON')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('ma_thiet_bi')->references('ma_thiet_bi')->on('THIET_BI_ARDUINO')->nullOnDelete()->cascadeOnUpdate();
            });
        }

        // 4) Log quẹt thẻ thô từ Arduino (kể cả thẻ lạ)
        if (! Schema::hasTable('LICH_SU_QUET_THE')) {
            Schema::create('LICH_SU_QUET_THE', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('uid_rfid', 32);                        // UID quẹt được (có thể chưa có trong hệ thống)
                $table->string('ma_the', 20)->nullable();              // thẻ khớp (nếu có)
                $table->string('ma_thiet_bi', 20)->nullable();
                $table->string('ma_chi_nhanh', 10)->nullable();
                // nhan_dien | tich_diem | doi_diem | phat_the | khong_xac_dinh
                $table->string('hanh_dong', 30)->default('nhan_dien');
                $table->string('ket_qua', 20)->default('thanh_cong');  // thanh_cong | that_bai
                $table->string('chi_tiet', 255)->nullable();
                $table->string('ip', 45)->nullable();
                $table->dateTime('thoi_gian')->useCurrent();

                $table->index(['ma_thiet_bi', 'thoi_gian']);
                $table->index('uid_rfid');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('LICH_SU_QUET_THE');
        Schema::dropIfExists('GIAO_DICH_DIEM');
        Schema::dropIfExists('THE_THANH_VIEN');
        Schema::dropIfExists('THIET_BI_ARDUINO');
    }
};
