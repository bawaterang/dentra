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
        Schema::create('trx_user_poli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('mst_user')->onDelete('cascade');
            $table->foreignId('poli_id')->constrained('mst_poli')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_user_poli');
    }
};
