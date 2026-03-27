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
        Schema::create('mst_obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_obat', 20)->unique()->comment('Internal Drug/SKU Code');
            $table->string('nama_obat', 100);
            $table->string('satuan', 20)->nullable()->comment('Contoh: Tablet, Kapsul, Botol');
            $table->integer('stok')->default(0);
            $table->integer('stok_minimal')->default(10)->comment('Threshold for low stock alerts');
            $table->decimal('harga_beli', 12, 2)->default(0);
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->date('tanggal_beli')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['Aktif', 'Tidak Aktif', 'Stok Habis'])->default('Aktif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_obat');
    }
};
