<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_dokter', function (Blueprint $table) {
            $table->renameColumn('bpjs_id', 'dokter_bpjs_id');
        });
    }

    public function down(): void
    {
        Schema::table('mst_dokter', function (Blueprint $table) {
            $table->renameColumn('dokter_bpjs_id', 'bpjs_id');
        });
    }
};
