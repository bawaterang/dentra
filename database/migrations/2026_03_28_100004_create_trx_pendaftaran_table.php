<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 20)->unique()->comment('Format: YYYYMMDDXXXX');
            $table->foreignId('antrian_id')->nullable()->constrained('trx_antrian')->nullOnDelete();
            $table->foreignId('pasien_id')->constrained('mst_pasien');
            $table->foreignId('poli_id')->constrained('mst_poli');
            $table->foreignId('dokter_id')->constrained('mst_dokter');
            $table->foreignId('asuransi_id')->nullable()->constrained('mst_asuransi')->nullOnDelete();
            $table->string('no_kartu_asuransi', 50)->nullable();
            // Data medis awal
            $table->string('kesadaran', 50)->nullable();
            $table->string('tekanan_darah', 20)->nullable();
            $table->integer('nadi')->nullable();
            $table->decimal('suhu', 4, 1)->nullable();
            $table->decimal('berat_badan', 5, 1)->nullable();
            $table->decimal('tinggi_badan', 5, 1)->nullable();
            $table->text('riwayat_penyakit')->nullable();
            $table->text('alergi')->nullable();
            $table->text('keterangan_lain')->nullable();
            $table->enum('status', ['terdaftar', 'menunggu_screening', 'selesai'])->default('terdaftar');
            $table->timestamps(); // created_at = tanggal daftar
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_pendaftaran');
    }
};
