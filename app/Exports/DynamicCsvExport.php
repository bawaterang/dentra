<?php

namespace App\Exports;

use App\Services\CsvEntityMappingService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DynamicCsvExport implements FromCollection, WithHeadings, WithCustomCsvSettings, WithTitle
{
    protected $entityKey;
    protected $isTemplate;
    protected $columns;
    protected $mandatoryFields;
    protected $modelClass;
    protected $tableName;
    protected $label;

    public function __construct($entityKey, $isTemplate = false)
    {
        $this->entityKey = $entityKey;
        $this->isTemplate = $isTemplate;

        $entities = CsvEntityMappingService::getEntities();
        
        if (!isset($entities[$entityKey])) {
            throw new \Exception("Entity tidak ditemukan.");
        }

        $this->modelClass = $entities[$entityKey]['model'];
        $this->tableName = $entities[$entityKey]['table'];
        $this->mandatoryFields = $entities[$entityKey]['mandatory'];
        $this->label = $entities[$entityKey]['label'];
        $this->columns = CsvEntityMappingService::getColumnsForEntity($entityKey);
    }

    public function collection()
    {
        if ($this->isTemplate) {
            // Return empty collection for template
            return collect([]);
        }

        // Export actual data, only the selected valid columns
        if ($this->modelClass) {
            return $this->modelClass::select($this->columns)->get();
        } else {
            return collect(\Illuminate\Support\Facades\DB::table($this->tableName)->select($this->columns)->get());
        }
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->columns as $column) {
            $isMandatory = in_array($column, $this->mandatoryFields);
            $headings[] = $column . ($isMandatory ? '*' : '');
        }
        return $headings;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'use_bom' => true, // Ensure UTF-8 characters are handled correctly in Excel
        ];
    }

    public function title(): string
    {
        return $this->label;
    }
}
