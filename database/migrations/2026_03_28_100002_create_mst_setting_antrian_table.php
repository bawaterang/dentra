<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_setting_antrian', function (Blueprint $table) {
            $table->id();
            $table->enum('mode_antrian', ['nomor_urut', 'time_slot'])->default('nomor_urut');
            $table->time('jam_buka')->default('08:00');
            $table->time('jam_tutup')->default('17:00');
            $table->integer('durasi_slot')->default(15)->comment('Durasi slot dalam menit');
            $table->integer('max_antrian')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_setting_antrian');
    }
};
