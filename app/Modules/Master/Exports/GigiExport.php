<?php
namespace App\Modules\Master\Exports;
use App\Models\MstKategoriGigi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GigiExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstKategoriGigi::withTrashed(); if ($this->status !== 'all') { $q->where('status', $this->status); } return $q->get(); }
    public function map($item): array { return [$item->kode_kategori, $item->nama_kategori, $item->warna ?? '-', $item->status]; }
    public function headings(): array { return ['Kode', 'Nama Kategori', 'Warna', 'Status']; }
}
