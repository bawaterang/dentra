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
        Schema::create('trx_satusehat_status', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 30)->index();
            $table->string('client_id')->nullable();
            $table->string('id_bundle')->nullable();
            $table->string('status_bundle', 50)->default('Pending');
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_satusehat_status');
    }
};
