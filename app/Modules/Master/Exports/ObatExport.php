<?php
namespace App\Modules\Master\Exports;
use App\Models\MstObat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ObatExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstObat::withTrashed(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->kode_obat, $item->nama_obat, $item->satuan ?? '-', $item->stok, number_format($item->harga_jual, 0, ',', '.'), $item->status]; }
    public function headings(): array { return ['Kode', 'Nama Obat', 'Satuan', 'Stok', 'Harga Jual', 'Status']; }
}
