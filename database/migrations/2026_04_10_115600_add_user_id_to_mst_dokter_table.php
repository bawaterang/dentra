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
        if (!Schema::hasColumn('mst_dokter', 'user_id')) {
            Schema::table('mst_dokter', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->comment('Link to mst_user');
                $table->foreign('user_id')->references('id')->on('mst_user')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_dokter', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
