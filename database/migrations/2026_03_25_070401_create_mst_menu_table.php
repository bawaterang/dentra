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
        Schema::create('mst_menu', function (Blueprint $table) {
            $table->id();
            $table->string('menu_name', 100);
            $table->string('menu_link', 255)->nullable();
            $table->string('menu_icon', 100)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedInteger('order_no')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('module_id')->nullable()->index();

            // Self-referential FK for parent menu
            $table->foreign('parent_id')->references('id')->on('mst_menu')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_menu');
    }
};
