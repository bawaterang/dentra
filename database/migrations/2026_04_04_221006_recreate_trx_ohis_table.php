<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old table if exists
        Schema::dropIfExists('trx_ohis');

        // Create clean new table
        Schema::create('trx_ohis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 50)->index();
            $table->unsignedBigInteger('pasien_id')->index();
            // Debris Index scores (0-3) for 6 index teeth
            $table->tinyInteger('di_16')->nullable();
            $table->tinyInteger('di_11')->nullable();
            $table->tinyInteger('di_26')->nullable();
            $table->tinyInteger('di_36')->nullable();
            $table->tinyInteger('di_31')->nullable();
            $table->tinyInteger('di_46')->nullable();
            // Calculus Index scores (0-3) for 6 index teeth
            $table->tinyInteger('ci_16')->nullable();
            $table->tinyInteger('ci_11')->nullable();
            $table->tinyInteger('ci_26')->nullable();
            $table->tinyInteger('ci_36')->nullable();
            $table->tinyInteger('ci_31')->nullable();
            $table->tinyInteger('ci_46')->nullable();
            // Calculated results
            $table->decimal('di_total', 4, 2)->nullable();
            $table->decimal('ci_total', 4, 2)->nullable();
            $table->decimal('ohis_total', 4, 2)->nullable();
            $table->string('kategori', 20)->nullable()->comment('Baik/Sedang/Buruk');
            // Audit
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_ohis');
    }
};
