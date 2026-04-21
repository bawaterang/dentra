<?php

namespace App\Modules\Setting\Exports;

use App\Models\TrxInformasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Traits\HasExportHeader;
use Carbon\Carbon;

class InformasiExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    use HasExportHeader;
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
        $query = TrxInformasi::query();
        
        $today = Carbon::today()->format('Y-m-d');

        if ($this->status === 'Aktif') {
            $query->where('date_start', '<=', $today)
                  ->where('date_expired', '>=', $today);
        } elseif ($this->status === 'Expired') {
            $query->where(function ($q) use ($today) {
                $q->where('date_expired', '<', $today)
                  ->orWhere('date_start', '>', $today);
            });
        }
        
        return $query->orderBy('date_start', 'desc')->get();
    }

    /**
     * @var TrxInformasi $informasi
     */
    public function map($informasi): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $statusStr = ($informasi->date_start <= $today && $informasi->date_expired >= $today) ? 'Aktif' : 'Expired';

        return [
            $informasi->id,
            $informasi->description,
            Carbon::parse($informasi->date_start)->format('d F Y'),
            Carbon::parse($informasi->date_expired)->format('d F Y'),
            $statusStr,
            $informasi->created_by ?? '-',
            $informasi->created_at ? $informasi->created_at->format('d/m/Y H:i') : '-',
        ];
    }
    
    public function headings(): array
    {
        return [
            'ID',
            'Deskripsi Informasi',
            'Tanggal Mulai',
            'Tanggal Berakhir',
            'Status',
            'Dibuat Oleh',
            'Dibuat Pada',
        ];
    }
}
