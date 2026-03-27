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
        Schema::create('mst_pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm', 20)->unique()->comment('Nomor Rekam Medis');
            $table->string('nama_pasien', 100);
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('agama', 20)->nullable();
            $table->string('pekerjaan', 50)->nullable();
            $table->string('no_penjamin', 50)->nullable()->comment('No Asuransi/BPJS');
            $table->string('nik', 20)->unique()->nullable();
            $table->string('golongan_darah', 5)->nullable();
            $table->text('alergi')->nullable()->comment('Riwayat Alergi');
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
        Schema::dropIfExists('mst_pasien');
    }
};
