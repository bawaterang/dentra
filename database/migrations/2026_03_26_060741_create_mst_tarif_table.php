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
        Schema::create('mst_tarif', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tindakan', 20)->comment('FK to mst_tindakan');
            $table->string('kode_asuransi', 20)->comment('FK to mst_asuransi');
            $table->decimal('tarif', 15, 2)->default(0)->comment('Total fee charged');
            $table->decimal('jasmed', 15, 2)->default(0)->comment('Jasa Medik (Doctor/Staff fee)');
            $table->decimal('bhp', 15, 2)->default(0)->comment('Biaya Barang Habis Pakai');
            $table->decimal('adm_fee', 15, 2)->default(0)->comment('Administrative fee');
            $table->string('satuan', 50)->nullable()->comment('Contoh: Sesi, Kali, Tindakan');
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraints
            $table->foreign('kode_tindakan')->references('kode_tindakan')->on('mst_tindakan')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('kode_asuransi')->references('kode_asuransi')->on('mst_asuransi')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_tarif');
    }
};
