\<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('BAN', function (Blueprint $table) {
            $table->unsignedTinyInteger('so_ghe')->default(4)->after('so_ban');
        });
    }

    public function down(): void
    {
        Schema::table('BAN', function (Blueprint $table) {
            $table->dropColumn('so_ghe');
        });
    }
};
