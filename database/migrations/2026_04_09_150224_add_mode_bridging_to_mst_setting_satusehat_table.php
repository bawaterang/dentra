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
        Schema::table('mst_setting_satusehat', function (Blueprint $table) {
            $table->string('mode_bridging', 20)->default('klinik')->after('token_url');
            $table->json('doctor_credentials')->nullable()->after('mode_bridging');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_setting_satusehat', function (Blueprint $table) {
            $table->dropColumn(['mode_bridging', 'doctor_credentials']);
        });
    }
};
