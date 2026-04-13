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
        Schema::table('trx_satusehat_status', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('resource_status')->comment('Menyimpan pesan error jika gagal mengirim ke SatuSehat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_satusehat_status', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
