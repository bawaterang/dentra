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
        Schema::create('mst_bmhp', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bmhp', 20)->unique()->comment('Code Internal or SKU');
            $table->string('nama_bmhp', 100);
            $table->string('satuan', 20)->nullable()->comment('Contoh: Pcs, Box, Pack');
            $table->integer('stok')->default(0);
            $table->integer('stok_minimal')->default(5)->comment('Threshold for alerts');
            $table->decimal('harga_satuan', 12, 2)->default(0)->comment('Price per unit');
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
        Schema::dropIfExists('mst_bmhp');
    }
};
