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
        Schema::table('trx_pemeriksaan', function (Blueprint $table) {
            $table->text('rekomendasi_diet')->nullable()->after('planning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_pemeriksaan', function (Blueprint $table) {
            $table->dropColumn('rekomendasi_diet');
        });
    }
};
