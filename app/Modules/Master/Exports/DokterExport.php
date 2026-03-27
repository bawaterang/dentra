<?php

namespace App\Modules\Master\Exports;

use App\Models\MstDokter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DokterExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;

    public function __construct($status = 'all')
    {
        $this->status = $status;
    }

    public function collection()
    {
        $query = MstDokter::withTrashed();
        
        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }
        
        return $query->get();
    }

    /**
     * @var MstDokter $dokter
     */
    public function map($dokter): array
    {
        return [
            $dokter->kode_dokter,
            $dokter->nama_dokter,
            $dokter->spesialisasi ?? '-',
            $dokter->jenis_kelamin,
            $dokter->no_sip ?? '-',
            $dokter->no_str ?? '-',
            $dokter->no_telepon ?? '-',
            $dokter->status,
        ];
    }
    
    public function headings(): array
    {
        return [
            'Kode Dokter',
            'Nama Dokter',
            'Spesialisasi',
            'Jenis Kelamin',
            'No. SIP',
            'No. STR',
            'No. Telepon',
            'Status',
        ];
    }
}
