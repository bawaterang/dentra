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
        Schema::table('mst_kfa_ingredient', function (Blueprint $table) {
            if (!Schema::hasColumn('mst_kfa_ingredient', 'kfa_code_ingredient')) {
                $table->string('kfa_code_ingredient')->nullable()->after('zat_aktif');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_kfa_ingredient', function (Blueprint $table) {
            $table->dropColumn('kfa_code_ingredient');
        });
    }
};
