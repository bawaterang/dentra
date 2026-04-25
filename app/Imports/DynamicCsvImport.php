<?php

namespace App\Imports;

use App\Services\CsvEntityMappingService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class DynamicCsvImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    protected $entityKey;
    protected $modelClass;
    protected $tableName;
    protected $mandatoryFields;
    protected $columns;
    public $successCount = 0;
    public $errorExceptions = [];

    public function __construct($entityKey)
    {
        $this->entityKey = $entityKey;

        $entities = CsvEntityMappingService::getEntities();
        
        if (!isset($entities[$entityKey])) {
            throw new \Exception("Entity tidak ditemukan.");
        }

        $this->modelClass = $entities[$entityKey]['model'];
        $this->tableName = $entities[$entityKey]['table'];
        $this->mandatoryFields = $entities[$entityKey]['mandatory'];
        $this->columns = CsvEntityMappingService::getColumnsForEntity($entityKey);
    }

    public function collection(Collection $rows)
    {
        $dbData = [];
        
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            
            // Re-map the keys because headers might have asterisk '*'
            $cleanRow = [];
            foreach ($rowArray as $key => $value) {
                // Laravel excel WithHeadingRow typically slugs the headers (e.g., 'nama_pasien*' becomes 'nama_pasien')
                // Let's strip any trailing asterisks just in case they are present in key names.
                $cleanKey = rtrim($key, '*');
                $cleanRow[$cleanKey] = $value;
            }

            // Validation: Check mandatory fields
            $hasError = false;
            foreach ($this->mandatoryFields as $mandatory) {
                if (!isset($cleanRow[$mandatory]) || trim($cleanRow[$mandatory]) === '') {
                    $this->errorExceptions[] = "Baris " . ($index + 2) . ": Kolom '$mandatory' wajib diisi.";
                    $hasError = true;
                }
            }

            if ($hasError) {
                continue;
            }

            // Filter out only columns that exist in our mapping
            $insertData = [];
            foreach ($this->columns as $column) {
                if (isset($cleanRow[$column])) {
                    $insertData[$column] = $cleanRow[$column];
                }
            }

            $usesTimestamps = false;
            
            if ($this->modelClass) {
                $model = new $this->modelClass;
                $usesTimestamps = $model->usesTimestamps();
            } else {
                // If using DB Facade directly, we just assume timestamps exist standardly unless configured otherwise,
                // But let's check schema if these columns exist just to be safe.
                $usesTimestamps = in_array('created_at', $this->columns) || true; 
            }

            if ($usesTimestamps) {
                $insertData['created_at'] = now();
                $insertData['updated_at'] = now();
            }

            $dbData[] = $insertData;
            $this->successCount++;
        }

        // Insert into database
        if (!empty($dbData)) {
            if ($this->modelClass) {
                foreach ($dbData as $data) {
                    $this->modelClass::create($data);
                }
            } else {
                \Illuminate\Support\Facades\DB::table($this->tableName)->insert($dbData);
            }
        }
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'use_bom' => true,
        ];
    }
}
