<?php
namespace App\Modules\Master\Exports;
use App\Models\MstDiagnosis;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DiagnosisExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstDiagnosis::withTrashed(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->kode_diagnosa, $item->nama_diagnosa, $item->kategori ?? '-', $item->status]; }
    public function headings(): array { return ['Kode ICD-10', 'Nama Diagnosa', 'Kategori', 'Status']; }
}
