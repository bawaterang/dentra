<?php
namespace App\Modules\Master\Exports;
use App\Models\MstBmhp;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BmhpExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstBmhp::withTrashed(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->kode_bmhp, $item->nama_bmhp, $item->satuan ?? '-', $item->stok, number_format($item->harga_satuan, 0, ',', '.'), $item->status]; }
    public function headings(): array { return ['Kode', 'Nama BMHP', 'Satuan', 'Stok', 'Harga Satuan', 'Status']; }
}
