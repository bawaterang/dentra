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
        Schema::create('trx_pasien_bpjs', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('no_kartu')->nullable();
            $blueprint->string('nik')->nullable();
            $blueprint->string('nama')->nullable();
            $blueprint->string('pisa')->nullable();
            $blueprint->string('sex')->nullable();
            $blueprint->date('tgl_lahir')->nullable();
            $blueprint->string('kd_provider')->nullable();
            $blueprint->string('nm_provider')->nullable();
            $blueprint->string('kd_cabang')->nullable();
            $blueprint->string('nm_cabang')->nullable();
            $blueprint->date('tgl_cetak_kartu')->nullable();
            $blueprint->string('jns_kelas')->nullable();
            $blueprint->string('jns_peserta')->nullable();
            $blueprint->string('status_peserta')->nullable();
            $blueprint->string('no_rm')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_pasien_bpjs');
    }
};
