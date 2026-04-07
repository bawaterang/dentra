<?php

namespace App\Modules\Laporan\Exports;

use App\Models\TrxPendaftaran;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanKunjunganExport implements FromView, WithTitle
{
    protected $periodType;
    protected $selectedDate;
    protected $selectedBulan;
    protected $tahun;
    protected $selectedTahun;
    protected $selectedDokter;

    public function __construct($periodType, $selectedDate, $selectedBulan, $selectedTahun, $selectedDokter = 'all')
    {
        $this->periodType = $periodType;
        $this->selectedDate = $selectedDate;
        $this->selectedBulan = $selectedBulan;
        $this->tahun = $selectedTahun; // Using 'tahun' to match variable names in JasaMedisExport if needed, but consistency is key.
        $this->selectedTahun = $selectedTahun;
        $this->selectedDokter = $selectedDokter;
    }

    public function view(): View
    {
        $query = TrxPendaftaran::with(['pasien', 'dokter', 'asuransi', 'billing'])
            ->whereNotNull('created_at');

        if ($this->periodType === 'DAILY') {
            $query->whereDate('created_at', $this->selectedDate);
        } elseif ($this->periodType === 'MONTHLY') {
            $query->whereMonth('created_at', $this->selectedBulan)
                ->whereYear('created_at', $this->selectedTahun);
        } elseif ($this->periodType === 'YEARLY') {
            $query->whereYear('created_at', $this->selectedTahun);
        }

        if ($this->selectedDokter !== 'all') {
            $query->where('dokter_id', $this->selectedDokter);
        }

        $dataList = $query->orderBy('created_at', 'desc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('modules.Laporan.kunjungan-excel', [
            'dataList' => $dataList,
            'periodType' => $this->periodType,
            'selectedDate' => $this->selectedDate,
            'bulan' => $namaBulan[(int) $this->selectedBulan],
            'tahun' => $this->selectedTahun,
        ]);
    }

    public function title(): string
    {
        return 'Laporan Kunjungan';
    }
}
