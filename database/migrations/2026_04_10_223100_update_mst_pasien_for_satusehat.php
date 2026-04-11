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
        Schema::table('mst_pasien', function (Blueprint $table) {
            $table->string('satusehat_uuid')->nullable()->unique()->after('id');
            $table->string('marital_status', 20)->nullable()->after('golongan_darah');
            $table->string('kode_pos', 10)->nullable()->after('alamat');
            
            $table->string('provinsi_id', 2)->nullable()->after('kode_pos');
            $table->string('kabupaten_id', 4)->nullable()->after('provinsi_id');
            $table->string('kecamatan_id', 6)->nullable()->after('kabupaten_id');
            $table->string('kelurahan_id', 10)->nullable()->after('kecamatan_id');

            $table->foreign('provinsi_id')->references('kode')->on('mst_wilayah_provinsi')->onDelete('set null');
            $table->foreign('kabupaten_id')->references('kode')->on('mst_wilayah_kabupaten')->onDelete('set null');
            $table->foreign('kecamatan_id')->references('kode')->on('mst_wilayah_kecamatan')->onDelete('set null');
            $table->foreign('kelurahan_id')->references('kode')->on('mst_wilayah_kelurahan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_pasien', function (Blueprint $table) {
            $table->dropForeign(['provinsi_id']);
            $table->dropForeign(['kabupaten_id']);
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['kelurahan_id']);
            
            $table->dropColumn([
                'satusehat_uuid',
                'marital_status',
                'kode_pos',
                'provinsi_id',
                'kabupaten_id',
                'kecamatan_id',
                'kelurahan_id'
            ]);
        });
    }
};
