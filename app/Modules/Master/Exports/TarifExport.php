<?php
namespace App\Modules\Master\Exports;
use App\Models\MstTarif;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TarifExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstTarif::withTrashed(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->kode_tindakan, $item->kode_asuransi, number_format($item->tarif, 0, ',', '.'), ($item->satuan_jasmed ?? 'Rp') === '%' ? number_format($item->jasmed, 0, ',', '.') . '%' : number_format($item->jasmed, 0, ',', '.'), $item->satuan_jasmed ?? 'Rp', number_format($item->bhp, 0, ',', '.'), $item->status]; }
    public function headings(): array { return ['Kode Tindakan', 'Kode Asuransi', 'Tarif', 'Jasmed', 'Satuan Jasmed', 'BHP', 'Status']; }
}
