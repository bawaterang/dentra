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
        Schema::table('mst_instansi', function (Blueprint $table) {
            if (Schema::hasColumn('mst_instansi', 'satusehat_uuid')) {
                $table->dropColumn('satusehat_uuid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_instansi', function (Blueprint $table) {
            $table->string('satusehat_uuid')->nullable()->after('organization_id');
        });
    }
};
