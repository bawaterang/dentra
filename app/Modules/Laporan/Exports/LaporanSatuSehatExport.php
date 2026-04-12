<?php

namespace App\Modules\Laporan\Exports;

use App\Models\TrxPendaftaran;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanSatuSehatExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $periodType;
    protected $selectedDate;
    protected $selectedBulan;
    protected $selectedTahun;
    protected $search;

    public function __construct($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search = '')
    {
        $this->periodType = $periodType;
        $this->selectedDate = $selectedDate;
        $this->selectedBulan = $selectedBulan;
        $this->selectedTahun = $selectedTahun;
        $this->search = $search;
    }

    public function view(): View
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'asuransi'])
            ->whereNotNull('trx_pendaftaran.created_at');

        if ($this->periodType === 'DAILY') {
            $query->whereDate('trx_pendaftaran.created_at', $this->selectedDate);
        } elseif ($this->periodType === 'MONTHLY') {
            $query->whereMonth('trx_pendaftaran.created_at', $this->selectedBulan)
                ->whereYear('trx_pendaftaran.created_at', $this->selectedTahun);
        } elseif ($this->periodType === 'YEARLY') {
            $query->whereYear('trx_pendaftaran.created_at', $this->selectedTahun);
        }

        if (!empty($this->search)) {
            $query->whereHas('pasien', function ($q) {
                $q->where('nama_pasien', 'like', '%' . $this->search . '%')
                  ->orWhere('no_rm', 'like', '%' . $this->search . '%')
                  ->orWhere('nik', 'like', '%' . $this->search . '%');
            });
        }

        $dataList = $query->orderBy('trx_pendaftaran.created_at', 'desc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $periodeDisplay = '';
        if ($this->periodType === 'DAILY') {
            $periodeDisplay = date('d F Y', strtotime($this->selectedDate));
        } elseif ($this->periodType === 'MONTHLY') {
            $periodeDisplay = $namaBulan[(int) $this->selectedBulan] . ' ' . $this->selectedTahun;
        } else {
            $periodeDisplay = 'Tahun ' . $this->selectedTahun;
        }

        return view('modules.Laporan.satu-sehat-excel', [
            'dataList' => $dataList,
            'periode' => $periodeDisplay,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            2    => ['font' => ['italic' => true]],
            4    => ['font' => ['bold' => true]],
        ];
    }
}
