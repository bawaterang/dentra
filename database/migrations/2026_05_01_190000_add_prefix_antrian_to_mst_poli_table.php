<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_poli', function (Blueprint $table) {
            $table->string('prefix_antrian', 5)->nullable()->after('poli_bpjs_id');
        });
    }

    public function down(): void
    {
        Schema::table('mst_poli', function (Blueprint $table) {
            $table->dropColumn('prefix_antrian');
        });
    }
};
