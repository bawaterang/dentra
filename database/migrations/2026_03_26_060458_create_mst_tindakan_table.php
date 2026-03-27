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
        Schema::create('mst_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tindakan', 20)->unique()->comment('Code Internal or Billing Code');
            $table->string('nama_tindakan', 200);
            $table->string('kategori_tindakan', 100)->nullable()->comment('Contoh: Konsultasi, Cabut Gigi, Tambal');
            $table->decimal('harga_default', 15, 2)->default(0)->comment('Default procedure price');
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
        Schema::dropIfExists('mst_tindakan');
    }
};
