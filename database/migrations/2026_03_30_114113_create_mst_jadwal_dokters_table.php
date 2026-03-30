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
        Schema::create('mst_jadwal_dokter', function (Blueprint $table) {
            $table->id();
            $table->string('kode_dokter');
            $table->string('hari');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('status_kehadiran')->default('Hadir'); // Hadir, Libur, Cuti
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('kode_dokter')->references('kode_dokter')->on('mst_dokter')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_jadwal_dokter');
    }
};
