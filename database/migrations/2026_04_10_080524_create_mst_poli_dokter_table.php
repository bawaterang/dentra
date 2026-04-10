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
        Schema::create('mst_poli_dokter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poli_id')->constrained('mst_poli')->onDelete('cascade');
            $table->foreignId('dokter_id')->constrained('mst_dokter')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['poli_id', 'dokter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_poli_dokter');
    }
};
