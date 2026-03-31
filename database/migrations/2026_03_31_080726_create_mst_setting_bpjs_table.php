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
        Schema::create('mst_setting_bpjs', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('consid')->nullable();
            $blueprint->string('secret_key')->nullable();
            $blueprint->string('username')->nullable();
            $blueprint->string('password')->nullable();
            $blueprint->string('kd_aplikasi')->nullable();
            $blueprint->string('user_key')->nullable();
            $blueprint->string('base_url')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_setting_bpjs');
    }
};
