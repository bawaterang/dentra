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
        Schema::table('mst_instansi', function (Blueprint $table) {
            // Organization ID from Kemenkes registration
            $table->string('organization_id')->nullable()->after('id');
            // Store the dynamically generated UUID from SatuSehat
            $table->string('satusehat_uuid')->nullable()->after('organization_id');
            
            // Address details
            $table->string('kode_pos', 10)->nullable()->after('alamat');
            $table->string('provinsi_id', 2)->nullable()->after('kode_pos');
            $table->string('kabupaten_id', 4)->nullable()->after('provinsi_id');
            $table->string('kecamatan_id', 6)->nullable()->after('kabupaten_id');
            $table->string('kelurahan_id', 10)->nullable()->after('kecamatan_id');

            // Foreign keys
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
        Schema::table('mst_instansi', function (Blueprint $table) {
            $table->dropForeign(['provinsi_id']);
            $table->dropForeign(['kabupaten_id']);
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['kelurahan_id']);

            $table->dropColumn([
                'organization_id',
                'satusehat_uuid',
                'kode_pos',
                'provinsi_id',
                'kabupaten_id',
                'kecamatan_id',
                'kelurahan_id'
            ]);
        });
    }
};
