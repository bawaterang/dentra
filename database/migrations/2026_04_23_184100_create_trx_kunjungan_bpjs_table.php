<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_kunjungan_bpjs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan')->comment('Nomor kunjungan internal aplikasi');
            $table->unsignedBigInteger('pasien_id');
            $table->string('no_kunjungan_bpjs')->comment('Nomor kunjungan dari response BPJS PCare');
            $table->enum('status', ['sukses', 'edited', 'deleted'])->default('sukses');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pasien_id')->references('id')->on('mst_pasien')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('mst_user')->onDelete('set null');
            $table->index('nomor_kunjungan');
            $table->index('no_kunjungan_bpjs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_kunjungan_bpjs');
    }
};
