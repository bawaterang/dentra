<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = "https://raw.githubusercontent.com/edwardsamuel/Wilayah-Administratif-Indonesia/master/csv/";

        $targets = [
            [
                'url' => $baseUrl . 'provinces.csv',
                'table' => 'mst_wilayah_provinsi',
                'mapping' => ['kode' => 0, 'nama' => 1],
                'chunk' => 100
            ],
            [
                'url' => $baseUrl . 'regencies.csv',
                'table' => 'mst_wilayah_kabupaten',
                'mapping' => ['kode' => 0, 'provinsi_kode' => 1, 'nama' => 2],
                'chunk' => 500
            ],
            [
                'url' => $baseUrl . 'districts.csv',
                'table' => 'mst_wilayah_kecamatan',
                'mapping' => ['kode' => 0, 'kabupaten_kode' => 1, 'nama' => 2],
                'chunk' => 1000
            ],
            [
                'url' => $baseUrl . 'villages.csv',
                'table' => 'mst_wilayah_kelurahan',
                'mapping' => ['kode' => 0, 'kecamatan_kode' => 1, 'nama' => 2],
                'chunk' => 2000
            ],
        ];

        foreach ($targets as $target) {
            $this->command->info("Importing {$target['table']}...");
            $this->importCsv($target['url'], $target['table'], $target['mapping'], $target['chunk']);
        }
    }

    protected function importCsv($url, $table, $mapping, $chunkSize)
    {
        try {
            $response = Http::get($url);
            if (!$response->successful()) {
                $this->command->error("Gagal mengambil data dari {$url}");
                return;
            }

            $lines = explode("\n", trim($response->body()));
            $header = array_shift($lines); // Hapus header

            $data = [];
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                $row = str_getcsv($line);
                $insertRow = [];
                foreach ($mapping as $dbField => $csvIndex) {
                    $insertRow[$dbField] = $row[$csvIndex] ?? null;
                }
                $insertRow['created_at'] = now();
                $insertRow['updated_at'] = now();
                
                $data[] = $insertRow;

                if (count($data) >= $chunkSize) {
                    DB::table($table)->insertOrIgnore($data);
                    $data = [];
                }
            }

            if (!empty($data)) {
                DB::table($table)->insertOrIgnore($data);
            }

            $this->command->info("Selesai import {$table}.");

        } catch (\Exception $e) {
            $this->command->error("Error import {$table}: " . $e->getMessage());
            Log::error("RegionalSeeder Error ({$table}): " . $e->getMessage());
        }
    }
}
