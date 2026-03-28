<?php

namespace App\Modules\Screening\Exports;

use App\Models\TrxPendaftaran;
use App\Models\TrxScreening;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ScreeningExport implements FromCollection, WithHeadings, WithMapping
{
    protected $date;
    public function __construct($date) { $this->date = $date; }

    public function collection()
    {
        return TrxScreening::with(['pendaftaran.pasien', 'survei'])
            ->whereHas('pendaftaran', fn($q) => $q->whereDate('created_at', $this->date))
            ->get();
    }

    public function map($item): array
    {
        return [
            $item->pendaftaran->nomor_kunjungan ?? '-',
            $item->pendaftaran->pasien->nama_pasien ?? '-',
            $item->survei->pertanyaan ?? '-',
            ucfirst($item->jawaban),
            $item->keterangan ?? '-',
        ];
    }

    public function headings(): array
    {
        return ['No Kunjungan', 'Nama Pasien', 'Pertanyaan', 'Jawaban', 'Keterangan'];
    }
}
