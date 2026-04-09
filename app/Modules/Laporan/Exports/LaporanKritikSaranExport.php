<?php

namespace App\Modules\Laporan\Exports;

use App\Models\TrxMessage;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanKritikSaranExport implements FromView, ShouldAutoSize, WithStyles
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
        $query = TrxMessage::query()->whereNotNull('created_at');

        if ($this->periodType === 'DAILY') {
            $query->whereDate('created_at', $this->selectedDate);
        } elseif ($this->periodType === 'MONTHLY') {
            $query->whereMonth('created_at', $this->selectedBulan)
                ->whereYear('created_at', $this->selectedTahun);
        } elseif ($this->periodType === 'YEARLY') {
            $query->whereYear('created_at', $this->selectedTahun);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('nomor_hp', 'like', '%' . $this->search . '%')
                  ->orWhere('pesan', 'like', '%' . $this->search . '%');
            });
        }

        $dataList = $query->orderBy('created_at', 'desc')->get();

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

        return view('modules.Laporan.kritik-saran-excel', [
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
