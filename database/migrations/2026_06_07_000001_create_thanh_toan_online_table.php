<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giao dịch thanh toán online qua cổng (VNPay sandbox).
 * Lưu lại mỗi lần khởi tạo thanh toán để đối soát với kết quả trả về / IPN.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('THANH_TOAN_ONLINE', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_giao_dich', 50)->unique();   // vnp_TxnRef do hệ thống sinh
            $table->string('ma_order', 20);
            $table->string('cong', 20)->default('vnpay');     // cổng thanh toán
            $table->decimal('so_tien', 12, 0);                // số tiền cần thu (sau chiết khấu)
            $table->decimal('chiet_khau', 5, 2)->default(0);
            // cho_xu_ly | thanh_cong | that_bai | huy
            $table->string('trang_thai', 20)->default('cho_xu_ly');
            $table->string('ma_gd_cong', 50)->nullable();     // vnp_TransactionNo
            $table->string('ma_phan_hoi', 10)->nullable();    // vnp_ResponseCode
            $table->string('ma_nv', 10)->nullable();          // nhân viên khởi tạo
            $table->string('ma_hoa_don', 30)->nullable();     // hóa đơn tạo ra khi thành công
            $table->json('du_lieu')->nullable();              // toàn bộ tham số trả về (đối soát)
            $table->dateTime('thoi_gian_tao')->useCurrent();
            $table->dateTime('thoi_gian_cap_nhat')->nullable();

            $table->index('ma_order');
            $table->index('trang_thai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('THANH_TOAN_ONLINE');
    }
};
