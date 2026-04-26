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
        Schema::create('mst_alergi', function (Blueprint $table) {
            $table->id();
            $table->string('kdAlergi', 50)->unique();
            $table->string('nmAlergi', 150);
            $table->string('kdJenis', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_alergi');
    }
};
