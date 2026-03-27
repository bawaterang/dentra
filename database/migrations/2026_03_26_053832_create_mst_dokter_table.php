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
        Schema::create('mst_dokter', function (Blueprint $table) {
            $table->id();
            $table->string('kode_dokter', 20)->unique()->comment('ID Dokter (Internal)');
            $table->string('nama_dokter', 100);
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('agama', 20)->nullable();
            $table->string('nik', 20)->unique()->nullable()->comment('Nomor Kependudukan');
            $table->string('spesialisasi', 100)->nullable()->comment('Contoh: Dokter Gigi Umum, Bedah Mulut');
            $table->string('no_sip', 50)->nullable()->comment('Surat Izin Praktik');
            $table->string('no_str', 50)->nullable()->comment('Surat Tanda Registrasi');
            $table->enum('status', ['Aktif', 'Tidak Aktif', 'Cuti'])->default('Aktif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_dokter');
    }
};
