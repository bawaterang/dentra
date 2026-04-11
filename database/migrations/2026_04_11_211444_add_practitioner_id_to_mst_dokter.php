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
        Schema::table('mst_dokter', function (Blueprint $table) {
            $table->string('practitioner_id')->nullable()->after('nik');
            $table->string('bpjs_id')->nullable()->after('practitioner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_dokter', function (Blueprint $table) {
            $table->dropColumn(['practitioner_id', 'bpjs_id']);
        });
    }
};
