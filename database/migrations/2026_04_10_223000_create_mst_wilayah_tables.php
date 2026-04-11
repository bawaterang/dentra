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
        Schema::create('mst_wilayah_provinsi', function (Blueprint $table) {
            $table->string('kode', 2)->primary();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('mst_wilayah_kabupaten', function (Blueprint $table) {
            $table->string('kode', 4)->primary();
            $table->string('provinsi_kode', 2);
            $table->string('nama');
            $table->timestamps();

            $table->foreign('provinsi_kode')->references('kode')->on('mst_wilayah_provinsi')->onDelete('cascade');
        });

        Schema::create('mst_wilayah_kecamatan', function (Blueprint $table) {
            $table->string('kode', 6)->primary();
            $table->string('kabupaten_kode', 4);
            $table->string('nama');
            $table->timestamps();

            $table->foreign('kabupaten_kode')->references('kode')->on('mst_wilayah_kabupaten')->onDelete('cascade');
        });

        Schema::create('mst_wilayah_kelurahan', function (Blueprint $table) {
            $table->string('kode', 10)->primary();
            $table->string('kecamatan_kode', 6);
            $table->string('nama');
            $table->timestamps();

            $table->foreign('kecamatan_kode')->references('kode')->on('mst_wilayah_kecamatan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_wilayah_kelurahan');
        Schema::dropIfExists('mst_wilayah_kecamatan');
        Schema::dropIfExists('mst_wilayah_kabupaten');
        Schema::dropIfExists('mst_wilayah_provinsi');
    }
};
