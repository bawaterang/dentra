<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_odontogram', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 50)->index();
            $table->string('nomor_gigi', 5)->comment('Tooth number: 11-48 (adult), 51-85 (child)');
            $table->string('bagian', 1)->comment('Surface: T=Top, B=Bottom, L=Left, R=Right, C=Center');
            $table->string('kode_kategori', 20)->nullable()->comment('Category code from mst_kategori_gigi');
            $table->string('warna', 20)->nullable()->comment('Color hex value');
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nomor_kunjungan', 'nomor_gigi', 'bagian'], 'trx_odontogram_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_odontogram');
    }
};
