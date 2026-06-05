<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ORDER_LOGS', function (Blueprint $table) {
            $table->id();
            $table->string('ma_order', 20);
            $table->string('hanh_dong', 50);
            $table->string('trang_thai_cu', 15)->nullable();
            $table->string('trang_thai_moi', 15)->nullable();
            $table->string('noi_dung', 500)->nullable();
            $table->json('du_lieu')->nullable();
            $table->string('ma_nv', 10)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->index(['ma_order', 'created_at']);
            $table->index(['hanh_dong', 'created_at']);

            $table->foreign('ma_order')->references('ma_order')->on('ORDERS')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ORDER_LOGS');
    }
};
