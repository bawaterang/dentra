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
        Schema::create('mst_kfa_obat', function (Blueprint $table) {
            $table->string('kfa_code')->primary();
            $table->string('name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('dosage_form_code')->nullable();
            $table->string('dosage_form_name')->nullable();
            $table->string('produk_template_kfa')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mst_kfa_ingredient', function (Blueprint $table) {
            $table->id();
            $table->string('kfa_code');
            $table->string('zat_aktif')->nullable();
            $table->string('kekuatan_zat_aktif')->nullable();
            $table->timestamps();

            $table->foreign('kfa_code')->references('kfa_code')->on('mst_kfa_obat')->onDelete('cascade');
        });

        Schema::create('mst_map_obat_kfa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('obat_id');
            $table->string('kfa_code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('obat_id')->references('id')->on('mst_obat')->onDelete('cascade');
            $table->foreign('kfa_code')->references('kfa_code')->on('mst_kfa_obat')->onDelete('cascade');
        });

        Schema::create('log_satusehat', function (Blueprint $table) {
            $table->id();
            $table->longText('request_json')->nullable();
            $table->longText('response_json')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_satusehat');
        Schema::dropIfExists('mst_map_obat_kfa');
        Schema::dropIfExists('mst_kfa_ingredient');
        Schema::dropIfExists('mst_kfa_obat');
    }
};
