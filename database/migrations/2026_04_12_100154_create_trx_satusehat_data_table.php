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
        Schema::create('trx_satusehat_data', function (Blueprint $table) {
            $table->id();
            $table->string('organization_id')->nullable()->comment('Organization UUID dari SatuSehat');
            $table->string('resource_uuid')->nullable()->comment('UUID resource (Encounter, Condition, dll)');
            $table->longText('isi_json')->nullable()->comment('Full JSON payload');
            $table->string('status', 50)->default('pending')->comment('pending, success, failed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_satusehat_data');
    }
};
