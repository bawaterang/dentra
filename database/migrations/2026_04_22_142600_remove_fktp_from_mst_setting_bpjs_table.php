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
        Schema::table('mst_setting_bpjs', function (Blueprint $table) {
            if (Schema::hasColumn('mst_setting_bpjs', 'base_url_antrian_fktp')) {
                $table->dropColumn('base_url_antrian_fktp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_setting_bpjs', function (Blueprint $table) {
            $table->string('base_url_antrian_fktp')->nullable()->after('base_url_antrian');
        });
    }
};
