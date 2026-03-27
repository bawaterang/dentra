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
        Schema::create('mst_kategori_gigi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kategori', 20)->unique()->comment('Contoh: CARIES, MISSING, FILLING');
            $table->string('nama_kategori', 100)->comment('Nama kondisi/kategori gigi');
            $table->string('warna', 10)->nullable()->comment('Hex code warna untuk odontogram');
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
        Schema::dropIfExists('mst_kategori_gigi');
    }
};
