<?php
namespace App\Modules\Master\Exports;
use App\Models\MstTindakan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TindakanExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstTindakan::withTrashed(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->kode_tindakan, $item->nama_tindakan, $item->kategori_tindakan ?? '-', number_format($item->harga_default, 0, ',', '.'), $item->status]; }
    public function headings(): array { return ['Kode', 'Nama Tindakan', 'Kategori', 'Harga Default', 'Status']; }
}
