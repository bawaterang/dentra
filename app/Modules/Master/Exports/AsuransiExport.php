<?php
namespace App\Modules\Master\Exports;
use App\Models\MstAsuransi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Traits\HasExportHeader;

class AsuransiExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    use HasExportHeader;
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstAsuransi::withTrashed(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->kode_asuransi, $item->nama_asuransi, $item->tipe_asuransi, number_format($item->diskon, 2).'%', $item->no_telepon ?? '-', $item->email ?? '-', $item->status]; }
    public function headings(): array { return ['Kode', 'Nama Asuransi', 'Tipe', 'Diskon', 'No. Telepon', 'Email', 'Status']; }
}
