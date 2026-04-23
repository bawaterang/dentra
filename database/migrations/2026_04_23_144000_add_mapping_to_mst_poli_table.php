<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_poli', function (Blueprint $table) {
            $table->string('poli_bpjs_id')->nullable()->after('nama_poli')->comment('Kode Poli from BPJS PCare');
        });
    }

    public function down(): void
    {
        Schema::table('mst_poli', function (Blueprint $table) {
            $table->dropColumn(['poli_bpjs_id']);
        });
    }
};
