<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_user', function (Blueprint $table) {
            $table->string('user_code', 20)->unique()->nullable()->after('id');
            $table->string('color', 7)->nullable()->after('avatar')->comment('Hex color code e.g. #FF5733, used to differentiate doctor schedules');
            $table->string('signature')->nullable()->after('color')->comment('Path to signature image file');
        });
    }

    public function down(): void
    {
        Schema::table('mst_user', function (Blueprint $table) {
            $table->dropColumn(['user_code', 'color', 'signature']);
        });
    }
};

