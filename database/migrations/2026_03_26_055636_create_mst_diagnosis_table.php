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
        Schema::create('mst_diagnosis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_diagnosa', 20)->unique()->comment('Code Internal or ICD-10');
            $table->string('nama_diagnosa', 200);
            $table->string('kategori', 50)->nullable()->comment('Contoh: Gigi, Umum, Bedah');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_diagnosis');
    }
};
