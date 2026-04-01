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
        // 1. trx_bmhp
        Schema::create('trx_bmhp', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan');
            $table->string('kode_bmhp');
            $table->double('jumlah')->default(0);
            $table->string('satuan')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. trx_obat
        Schema::create('trx_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan');
            $table->string('kode_obat');
            $table->string('dosis')->nullable();
            $table->string('aturan')->nullable();
            $table->date('tanggal_obat')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. trx_diagnosis
        Schema::create('trx_diagnosis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan');
            $table->string('kode_diagnosa');
            $table->string('jenis_icd')->nullable();
            $table->string('kasus_icd')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. trx_tindakan
        Schema::create('trx_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan');
            $table->string('kode_tindakan');
            $table->string('kode_asuransi')->nullable();
            $table->double('biaya')->default(0);
            $table->double('jasa_medis')->default(0);
            $table->string('satuan')->nullable();
            $table->double('bhp')->default(0);
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. trx_pemeriksaan
        Schema::create('trx_pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan');
            $table->string('kode_dokter');
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('planning')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 6. trx_ohis
        Schema::create('trx_ohis', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm');
            $table->string('lab_1')->nullable();
            $table->string('lab_2')->nullable();
            $table->string('lab_3')->nullable();
            $table->string('lab_4')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. trx_penunjang_dokumen
        Schema::create('trx_penunjang_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan');
            $table->string('no_rm');
            $table->string('document_name');
            $table->string('jenis')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_penunjang_dokumen');
        Schema::dropIfExists('trx_ohis');
        Schema::dropIfExists('trx_pemeriksaan');
        Schema::dropIfExists('trx_tindakan');
        Schema::dropIfExists('trx_diagnosis');
        Schema::dropIfExists('trx_obat');
        Schema::dropIfExists('trx_bmhp');
    }
};
