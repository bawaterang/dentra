<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_antrian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_antrian', 20);
            $table->date('tanggal_antrian');
            $table->enum('jenis_antrian', ['online', 'offline'])->default('offline');
            $table->foreignId('pasien_id')->nullable()->constrained('mst_pasien')->nullOnDelete();
            $table->string('nama_pasien_input_manual', 100)->nullable();
            $table->string('no_telepon_manual', 20)->nullable();
            $table->string('nik_manual', 20)->nullable();
            $table->string('kode_dokter', 20)->nullable();
            $table->string('kode_poli', 20)->nullable();
            $table->string('asuransi', 100)->nullable();
            $table->string('no_asuransi', 50)->nullable();
            $table->time('time_slot')->nullable()->comment('Waktu slot jika mode time_slot');
            $table->enum('status', ['menunggu', 'dipanggil', 'hadir', 'tidak_hadir', 'batal', 'selesai'])->default('menunggu');
            $table->timestamp('waktu_panggil')->nullable();
            $table->timestamp('waktu_hadir')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_antrian');
    }
};
