<?php

namespace App\Modules\Master\Exports;

use App\Models\MstSurvei;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Traits\HasExportHeader;

class SurveiExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    use HasExportHeader;
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstSurvei::query(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->pertanyaan, ucfirst($item->jenis_survei), $item->status]; }
    public function headings(): array { return ['Pertanyaan', 'Jenis Survei', 'Status']; }
}
