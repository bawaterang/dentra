<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_setting_antrian_detail', function (Blueprint $table) {
            $table->id();
            $table->string('hari', 20); // Senin, Selasa, ... Minggu
            $table->time('waktu');
            $table->integer('nomor_urut');
            $table->string('created_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_setting_antrian_detail');
    }
};
