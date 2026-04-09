<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mst_setting_antrian', function (Blueprint $table) {
            $table->string('format_nomor_antrian', 50)->default('[nomor]')->after('mode_antrian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_setting_antrian', function (Blueprint $table) {
            $table->dropColumn('format_nomor_antrian');
        });
    }
};
