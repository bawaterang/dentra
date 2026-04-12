<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Restructure trx_satusehat_status:
     *   id, nomor_kunjungan, patient_id, resource_type, resource_uuid, resource_status, created_by, timestamps
     * 
     * Add nomor_kunjungan & resource_type to trx_satusehat_data.
     */
    public function up(): void
    {
        // ── trx_satusehat_status ──
        // Drop the old table and recreate with correct schema
        Schema::dropIfExists('trx_satusehat_status');

        Schema::create('trx_satusehat_status', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 30)->index();
            $table->string('patient_id')->nullable()->comment('SatuSehat Patient UUID');
            $table->string('resource_type', 50)->nullable()->comment('Encounter, Condition, Observation');
            $table->string('resource_uuid')->nullable()->comment('UUID dari response SatuSehat');
            $table->string('resource_status', 50)->default('Pending')->comment('Pending, Success, Failed');
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        // ── trx_satusehat_data ──
        if (!Schema::hasColumn('trx_satusehat_data', 'nomor_kunjungan')) {
            Schema::table('trx_satusehat_data', function (Blueprint $table) {
                $table->string('nomor_kunjungan', 30)->nullable()->index()->after('id');
                $table->string('resource_type', 50)->nullable()->comment('Encounter, Condition, Observation')->after('organization_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_satusehat_status');

        // Recreate original schema
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

        if (Schema::hasColumn('trx_satusehat_data', 'nomor_kunjungan')) {
            Schema::table('trx_satusehat_data', function (Blueprint $table) {
                $table->dropColumn(['nomor_kunjungan', 'resource_type']);
            });
        }
    }
};
