<?php

namespace App\Modules\Master\Exports;

use App\Models\MstPasien;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PasienExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;

    public function __construct($status = 'all')
    {
        $this->status = $status;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = MstPasien::withTrashed();
        
        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }
        
        return $query->get();
    }

    /**
     * @var MstPasien $pasien
     */
    public function map($pasien): array
    {
        return [
            $pasien->no_rm,
            $pasien->nama_pasien,
            $pasien->nik,
            $pasien->jenis_kelamin,
            $pasien->no_telepon ?? '-',
            $pasien->status,
        ];
    }
    
    public function headings(): array
    {
        return [
            'No. RM',
            'Nama Pasien',
            'NIK',
            'Jenis Kelamin',
            'No. Telepon',
            'Status',
        ];
    }
}
