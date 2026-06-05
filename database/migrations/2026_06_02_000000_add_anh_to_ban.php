<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('BAN', 'anh')) {
            Schema::table('BAN', function (Blueprint $table) {
                $table->string('anh', 255)->nullable()->after('so_ghe');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('BAN', 'anh')) {
            Schema::table('BAN', function (Blueprint $table) {
                $table->dropColumn('anh');
            });
        }
    }
};
