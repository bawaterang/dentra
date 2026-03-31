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
        Schema::create('mst_setting_satusehat', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('client_id')->nullable();
            $blueprint->text('client_secret')->nullable();
            $blueprint->string('organization_id')->nullable();
            $blueprint->string('organization_name')->nullable();
            $blueprint->string('practitioner_id')->nullable();
            $blueprint->string('location_id')->nullable();
            $blueprint->string('url')->nullable();
            $blueprint->string('token_url')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_setting_satusehat');
    }
};
