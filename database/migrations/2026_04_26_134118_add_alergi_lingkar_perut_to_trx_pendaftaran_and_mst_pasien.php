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
        Schema::table('trx_pendaftaran', function (Blueprint $table) {
            $table->string('kode_alergi', 50)->nullable()->after('riwayat_penyakit');
            $table->decimal('lingkar_perut', 5, 1)->nullable()->after('tinggi_badan');
        });

        Schema::table('mst_pasien', function (Blueprint $table) {
            $table->string('kode_alergi', 50)->nullable()->after('golongan_darah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_pendaftaran_and_mst_pasien', function (Blueprint $table) {
            //
        });
    }
};
