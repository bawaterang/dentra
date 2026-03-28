<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_screening', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->constrained('trx_pendaftaran')->cascadeOnDelete();
            $table->foreignId('pasien_id')->constrained('mst_pasien');
            $table->foreignId('survei_id')->constrained('mst_survei');
            $table->enum('jawaban', ['ya', 'tidak'])->default('tidak');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_screening');
    }
};
