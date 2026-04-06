<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MstDiagnosis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Icd10Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $url = 'https://raw.githubusercontent.com/fendis0709/icd-10/master/master_icd_x.json';
        
        $this->command->info("Fetching ICD-10 data from GitHub...");
        
        try {
            $response = Http::get($url);
            
            if (!$response->successful()) {
                $this->command->error("Failed to fetch data from $url");
                return;
            }

            $data = $response->json();
            $totalFound = count($data);
            $this->command->info("Found $totalFound total entries. Filtering and processing...");

            $processedCount = 0;
            $chunks = [];
            $batchSize = 1000;

            foreach ($data as $item) {
                $code = $item['kode_icd'] ?? '';
                $englishName = $item['nama_icd'] ?? '';
                $indoName = $item['nama_icd_indo'] ?? '';

                if (empty($code)) continue;

                // Filtering Logic:
                // 1. Dental & Oral Surgery: K00 - K14
                // 2. Common/General: A, B, E, I, J, R, Z
                $prefix = substr($code, 0, 1);
                $block = substr($code, 0, 3);
                
                $isDental = ($prefix === 'K' && intval(substr($block, 1)) >= 0 && intval(substr($block, 1)) <= 14);
                $isGeneral = in_array($prefix, ['A', 'B', 'E', 'I', 'J', 'R', 'Z']);

                if ($isDental || $isGeneral) {
                    $chunks[] = [
                        'kode_diagnosa' => $code,
                        'nama_diagnosa' => $englishName ?: $indoName,
                        'deskripsi' => $indoName ?: $englishName,
                        'kategori' => $isDental ? 'Gigi/Bedah Mulut' : 'Penyakit Umum',
                        'status' => 'aktif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $processedCount++;

                    if (count($chunks) >= $batchSize) {
                        MstDiagnosis::upsert($chunks, ['kode_diagnosa'], ['nama_diagnosa', 'deskripsi', 'kategori', 'status', 'updated_at']);
                        $chunks = [];
                        $this->command->info("Processed $processedCount records...");
                    }
                }
            }

            // Final batch
            if (count($chunks) > 0) {
                MstDiagnosis::upsert($chunks, ['kode_diagnosa'], ['nama_diagnosa', 'deskripsi', 'kategori', 'status', 'updated_at']);
                $this->command->info("Processed final batch.");
            }

            $this->command->info("Successfully imported $processedCount diagnoses!");
            
        } catch (\Exception $e) {
            $this->command->error("An error occurred during seeding: " . $e->getMessage());
            Log::error("Icd10Seeder Error: " . $e->getMessage());
        }
    }
}
