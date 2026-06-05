<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('BAN', 'so_ghe')) {
            Schema::table('BAN', function (Blueprint $table) {
                $table->unsignedTinyInteger('so_ghe')->default(4)->after('vi_tri');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('BAN', 'so_ghe')) {
            Schema::table('BAN', function (Blueprint $table) {
                $table->dropColumn('so_ghe');
            });
        }
    }
};
