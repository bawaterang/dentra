<?php

namespace App\Modules\Master\Exports;

use App\Models\MstPoli;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Traits\HasExportHeader;

class PoliExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    use HasExportHeader;
    protected $status;

    public function __construct($status = 'all')
    {
        $this->status = $status;
    }

    public function collection()
    {
        $q = MstPoli::query();
        if ($this->status !== 'all') {
            $q->where('status', $this->status);
        }

return $q->get();
    }

    public function map($item): array
    {
        return [$item->kode_poli, $item->nama_poli, $item->status];
    }

    public function headings(): array
    {
        return ['Kode Poli', 'Nama Poli', 'Status'];
    }
}
