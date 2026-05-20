<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_reservasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_reservasi', 20)->unique();
            $table->date('tanggal_reservasi');
            $table->time('time_slot');
            $table->unsignedBigInteger('pasien_id')->nullable();
            $table->string('nama_pasien_manual', 100)->nullable();
            $table->string('no_telepon_manual', 20)->nullable();
            $table->string('nik_manual', 20)->nullable();
            $table->unsignedBigInteger('poli_id');
            $table->unsignedBigInteger('dokter_id');
            $table->text('keterangan')->nullable();
            $table->string('status', 20)->default('aktif'); // aktif, hadir, batal, expired
            $table->unsignedBigInteger('antrian_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pasien_id')->references('id')->on('mst_pasien')->nullOnDelete();
            $table->foreign('poli_id')->references('id')->on('mst_poli');
            $table->foreign('dokter_id')->references('id')->on('mst_dokter');
            $table->foreign('antrian_id')->references('id')->on('trx_antrian')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('mst_user')->nullOnDelete();

            $table->index(['tanggal_reservasi', 'status']);
            $table->index(['poli_id', 'dokter_id', 'tanggal_reservasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_reservasi');
    }
};
