<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TOPPING', function (Blueprint $table) {
            $table->string('ma_topping', 10)->primary();
            $table->string('ten_topping', 100);
            $table->decimal('gia_them', 12, 0)->default(0);
            $table->string('canh_bao', 255)->nullable();
            $table->string('trang_thai', 10)->default('active');
        });

        Schema::create('MON_OPTION', function (Blueprint $table) {
            $table->string('ma_option', 10)->primary();
            $table->string('ma_mon', 10)->nullable();
            $table->string('loai_option', 30);
            $table->string('ten_option', 100);
            $table->decimal('gia_them', 12, 0)->default(0);
            $table->boolean('bat_buoc')->default(false);
            $table->unsignedTinyInteger('thu_tu')->default(0);
            $table->string('trang_thai', 10)->default('active');

            $table->index(['ma_mon', 'loai_option', 'trang_thai']);
            $table->foreign('ma_mon')->references('ma_mon')->on('MON')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('CHI_TIET_ORDER_OPTION', function (Blueprint $table) {
            $table->id('id');
            $table->string('ma_order', 20);
            $table->string('ma_mon', 10);
            $table->string('loai_option', 30);
            $table->string('ten_lua_chon', 100);
            $table->decimal('gia_them', 12, 0)->default(0);

            $table->index(['ma_order', 'ma_mon']);
            $table->foreign(['ma_order', 'ma_mon'])
                ->references(['ma_order', 'ma_mon'])
                ->on('CHI_TIET_ORDER')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CHI_TIET_ORDER_OPTION');
        Schema::dropIfExists('MON_OPTION');
        Schema::dropIfExists('TOPPING');
    }
};
