<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('NHAT_KY_HANH_DONG', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_nv', 20)->nullable()->index();
            $table->string('ten_nv', 100)->nullable();
            $table->string('hanh_dong', 80)->index();
            $table->string('doi_tuong_loai', 40)->nullable();
            $table->string('doi_tuong_ma', 80)->nullable();
            $table->text('mo_ta')->nullable();
            $table->json('chi_tiet')->nullable();
            $table->string('dia_chi_ip', 45)->nullable();
            $table->dateTime('thoi_gian')->index();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('NHAT_KY_HANH_DONG');
    }
};
