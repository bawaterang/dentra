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
        Schema::create('mst_asuransi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_asuransi', 20)->unique()->comment('Internal/Claim Code');
            $table->string('nama_asuransi', 100);
            $table->enum('tipe_asuransi', ['Pemerintah', 'Swasta', 'Lainnya'])->default('Swasta');
            $table->decimal('diskon', 5, 2)->default(0)->comment('Default discount percentage');
            $table->string('no_telepon', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('alamat')->nullable();
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
        Schema::dropIfExists('mst_asuransi');
    }
};
