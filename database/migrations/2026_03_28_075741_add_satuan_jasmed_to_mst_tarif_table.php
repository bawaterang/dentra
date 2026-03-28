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
        Schema::table('mst_tarif', function (Blueprint $table) {
            $table->string('satuan_jasmed', 10)->default('Rp')->after('jasmed')->comment('Satuan jasmed: Rp or %');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_tarif', function (Blueprint $table) {
            $table->dropColumn('satuan_jasmed');
        });
    }
};
