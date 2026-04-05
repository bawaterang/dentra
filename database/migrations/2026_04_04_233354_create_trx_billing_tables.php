<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_billing', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 50)->index();
            $table->unsignedBigInteger('pasien_id')->index();
            $table->string('no_faktur', 50)->unique();
            $table->decimal('total_tagihan', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->decimal('kembalian', 15, 2)->default(0);
            $table->decimal('hutang', 15, 2)->default(0);
            $table->enum('status', ['Lunas', 'Belum Lunas'])->default('Belum Lunas');
            $table->dateTime('tanggal_bayar')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trx_billing_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billing_id');
            $table->string('kode_tindakan', 50);
            $table->string('nama_tindakan', 255);
            $table->decimal('biaya', 15, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('billing_id')->references('id')->on('trx_billing')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_billing_detail');
        Schema::dropIfExists('trx_billing');
    }
};
