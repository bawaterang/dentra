<?php

namespace App\Modules\Laporan\Exports;

use App\Models\TrxBilling;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanPendapatanExport implements FromView, WithTitle
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

    public function getPengeluaranBhp($nomorKunjungan)
    {
        return DB::table('trx_tindakan')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->whereNull('deleted_at')
            ->sum('bhp');
    }

    public function view(): View
    {
        $query = TrxBilling::with(['pasien', 'details'])
            ->whereNotNull('created_at');

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
                $q->where('no_faktur', 'like', '%' . $this->search . '%')
                  ->orWhere('nomor_kunjungan', 'like', '%' . $this->search . '%')
                  ->orWhereHas('pasien', function ($pq) {
                      $pq->where('nama_pasien', 'like', '%' . $this->search . '%')
                         ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $dataList = $query->orderBy('created_at', 'desc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        
        $pendapatan = $dataList->sum('total_bayar');
        $piutang = $dataList->sum('hutang');
        $nomorKunjungans = $dataList->pluck('nomor_kunjungan')->toArray();
        if (count($nomorKunjungans) > 0) {
            $pengeluaranBhp = DB::table('trx_tindakan')
                ->whereIn('nomor_kunjungan', $nomorKunjungans)
                ->whereNull('deleted_at')
                ->sum('bhp');
        } else {
            $pengeluaranBhp = 0;
        }

        $summary = [
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaranBhp,
            'piutang' => $piutang,
            'laba_bersih' => $pendapatan - $pengeluaranBhp
        ];

        return view('modules.Laporan.pendapatan-excel', [
            'dataList' => $dataList,
            'summary' => $summary,
            'periodType' => $this->periodType,
            'selectedDate' => $this->selectedDate,
            'bulan' => $namaBulan[(int) $this->selectedBulan],
            'tahun' => $this->selectedTahun,
            'getPengeluaranBhp' => [$this, 'getPengeluaranBhp'],
        ]);
    }

    public function title(): string
    {
        return 'Laporan Pendapatan';
    }
}
