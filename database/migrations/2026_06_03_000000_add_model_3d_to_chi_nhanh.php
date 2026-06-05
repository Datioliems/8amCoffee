<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('CHI_NHANH', 'model_3d')) {
            Schema::table('CHI_NHANH', function (Blueprint $table) {
                $table->string('model_3d', 120)->nullable()->after('sdt');
            });
        }
        // Chi nhánh gốc dùng model hiện có
        DB::table('CHI_NHANH')->where('ma_chi_nhanh', 'CN001')->update(['model_3d' => 'cafe_opt.glb']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('CHI_NHANH', 'model_3d')) {
            Schema::table('CHI_NHANH', function (Blueprint $table) {
                $table->dropColumn('model_3d');
            });
        }
    }
};
