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
        Schema::table('mst_tindakan', function (Blueprint $table) {
            $table->string('icd9cm_code', 20)->nullable()->after('kategori_tindakan')->comment('ICD-9-CM procedure code');
            $table->string('icd9cm_name', 255)->nullable()->after('icd9cm_code')->comment('ICD-9-CM procedure name/display');
            $table->string('snomed_code', 30)->nullable()->after('icd9cm_name')->comment('SNOMED CT code');
            $table->string('snomed_name', 255)->nullable()->after('snomed_code')->comment('SNOMED CT display name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_tindakan', function (Blueprint $table) {
            $table->dropColumn(['icd9cm_code', 'icd9cm_name', 'snomed_code', 'snomed_name']);
        });
    }
};
