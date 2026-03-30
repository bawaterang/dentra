<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('mst_user')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('mst_role_user')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_role_user');
    }
};
