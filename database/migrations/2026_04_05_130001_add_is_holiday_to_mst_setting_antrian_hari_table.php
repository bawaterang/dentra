<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_setting_antrian_hari', function (Blueprint $table) {
            $table->boolean('is_holiday')->default(false)->after('max_antrian');
        });
    }

    public function down(): void
    {
        Schema::table('mst_setting_antrian_hari', function (Blueprint $table) {
            $table->dropColumn('is_holiday');
        });
    }
};
