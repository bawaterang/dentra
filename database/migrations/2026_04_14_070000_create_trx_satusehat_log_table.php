<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_satusehat_log', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 30)->nullable()->index();
            $table->string('patient_id')->nullable()->comment('SatuSehat Patient UUID');
            $table->string('organization_id')->nullable()->comment('Organization UUID dari SatuSehat');
            $table->string('resource_type', 50)->nullable()->comment('Encounter, Condition, Observation, Procedure, Composition');
            $table->string('resource_uuid')->nullable()->comment('UUID dari response SatuSehat');
            $table->longText('request_json')->nullable()->comment('Full JSON payload yang dikirim');
            $table->longText('response_json')->nullable()->comment('Full JSON response dari API');
            $table->string('status', 50)->default('Pending')->comment('Pending, Success, Failed');
            $table->text('error_message')->nullable()->comment('Pesan error jika gagal');
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        $this->migrateDataFromOldTables();

        Schema::dropIfExists('trx_satusehat_data');
        Schema::dropIfExists('trx_satusehat_status');
    }

    protected function migrateDataFromOldTables(): void
    {
        $now = now();

        if (Schema::hasTable('trx_satusehat_status')) {
            $statusRecords = DB::table('trx_satusehat_status')->get();

            foreach ($statusRecords as $record) {
                DB::table('trx_satusehat_log')->insert([
                    'nomor_kunjungan' => $record->nomor_kunjungan ?? null,
                    'patient_id' => $record->patient_id ?? null,
                    'organization_id' => null,
                    'resource_type' => $record->resource_type ?? null,
                    'resource_uuid' => $record->resource_uuid ?? null,
                    'request_json' => null,
                    'response_json' => null,
                    'status' => $record->resource_status ?? 'Pending',
                    'error_message' => $record->error_message ?? null,
                    'created_by' => $record->created_by ?? null,
                    'created_at' => $record->created_at ?? $now,
                    'updated_at' => $record->updated_at ?? $now,
                ]);
            }
        }

        if (Schema::hasTable('trx_satusehat_data')) {
            $dataRecords = DB::table('trx_satusehat_data')->get();

            foreach ($dataRecords as $record) {
                DB::table('trx_satusehat_log')->insert([
                    'nomor_kunjungan' => $record->nomor_kunjungan ?? null,
                    'patient_id' => null,
                    'organization_id' => $record->organization_id ?? null,
                    'resource_type' => $record->resource_type ?? null,
                    'resource_uuid' => $record->resource_uuid ?? null,
                    'request_json' => $record->isi_json ?? null,
                    'response_json' => null,
                    'status' => $record->status ?? 'Pending',
                    'error_message' => null,
                    'created_by' => null,
                    'created_at' => $record->created_at ?? $now,
                    'updated_at' => $record->updated_at ?? $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::create('trx_satusehat_status', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 30)->index();
            $table->string('patient_id')->nullable();
            $table->string('resource_type', 50)->nullable();
            $table->string('resource_uuid')->nullable();
            $table->string('resource_status', 50)->default('Pending');
            $table->text('error_message')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('trx_satusehat_data', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kunjungan', 30)->nullable()->index();
            $table->string('organization_id')->nullable();
            $table->string('resource_type', 50)->nullable();
            $table->string('resource_uuid')->nullable();
            $table->longText('isi_json')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });

        $logRecords = DB::table('trx_satusehat_log')->get();

        foreach ($logRecords as $record) {
            DB::table('trx_satusehat_status')->insert([
                'nomor_kunjungan' => $record->nomor_kunjungan,
                'patient_id' => $record->patient_id,
                'resource_type' => $record->resource_type,
                'resource_uuid' => $record->resource_uuid,
                'resource_status' => $record->status,
                'error_message' => $record->error_message,
                'created_by' => $record->created_by,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);

            if (! empty($record->request_json)) {
                DB::table('trx_satusehat_data')->insert([
                    'nomor_kunjungan' => $record->nomor_kunjungan,
                    'organization_id' => $record->organization_id,
                    'resource_type' => $record->resource_type,
                    'resource_uuid' => $record->resource_uuid,
                    'isi_json' => $record->request_json,
                    'status' => $record->status,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('trx_satusehat_log');
    }
};
