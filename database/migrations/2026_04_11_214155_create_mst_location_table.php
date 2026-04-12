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
        Schema::create('mst_location', function (Blueprint $table) {
            $table->id();
            $table->string('organization_id')->nullable()->comment('Organization ID from mst_instansi (SS UUID)');
            $table->string('location_id')->nullable()->comment('Location UUID from SatuSehat');
            $table->string('location_code')->nullable();
            $table->string('location_name');
            $table->text('description')->nullable();
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();
            $table->string('status')->default('active')->comment('active, inactive, suspended');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_location');
    }
};
