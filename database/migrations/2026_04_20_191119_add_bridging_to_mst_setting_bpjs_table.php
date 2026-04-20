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
        Schema::table('mst_setting_bpjs', function (Blueprint $table) {
            $table->enum('bridging', ['ON', 'OFF'])->default('OFF')->after('base_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_setting_bpjs', function (Blueprint $table) {
            $table->dropColumn('bridging');
        });
    }
};
