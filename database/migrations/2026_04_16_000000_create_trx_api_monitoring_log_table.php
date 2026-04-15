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
        Schema::create('trx_api_monitoring_log', function (Blueprint $table) {
            $table->id();
            $table->string('api_type', 20)->comment('bpjs / satusehat');
            $table->string('endpoint_url', 500);
            $table->integer('http_status_code')->nullable();
            $table->boolean('is_up')->default(false);
            $table->integer('response_time_ms')->nullable();
            $table->string('token_status', 20)->nullable()->comment('valid / invalid / expired / error');
            $table->text('error_message')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('response_body')->nullable();
            $table->float('cpu_usage')->nullable()->comment('Persentase CPU saat request');
            $table->float('memory_usage_mb')->nullable()->comment('MB memory saat request');
            $table->string('checked_by', 100)->nullable();
            $table->timestamps();

            $table->index('api_type');
            $table->index('created_at');
            $table->index(['api_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_api_monitoring_log');
    }
};
