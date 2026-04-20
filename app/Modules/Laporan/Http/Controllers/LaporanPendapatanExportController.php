<?php

namespace App\Modules\Laporan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxBilling;
use App\Modules\Laporan\Exports\LaporanPendapatanExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanPendapatanExportController extends Controller
{
    public function getData($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search = '')
    {
        $query = TrxBilling::with(['pasien', 'details'])
            ->whereNotNull('created_at');

        if ($periodType === 'DAILY') {
            $query->whereDate('created_at', $selectedDate);
        } elseif ($periodType === 'MONTHLY') {
            $query->whereMonth('created_at', $selectedBulan)
                ->whereYear('created_at', $selectedTahun);
        } elseif ($periodType === 'YEARLY') {
            $query->whereYear('created_at', $selectedTahun);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('no_faktur', 'like', '%' . $search . '%')
                  ->orWhere('nomor_kunjungan', 'like', '%' . $search . '%')
                  ->orWhereHas('pasien', function ($pq) use ($search) {
                      $pq->where('nama_pasien', 'like', '%' . $search . '%')
                         ->orWhere('no_rm', 'like', '%' . $search . '%');
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getPengeluaranBhp($nomorKunjungan)
    {
        return DB::table('trx_tindakan')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->whereNull('deleted_at')
            ->sum('bhp');
    }

    public function getDetailBhp($nomorKunjungan)
    {
        return DB::table('trx_tindakan')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->whereNull('deleted_at')
            ->where('bhp', '>', 0)
            ->get();
    }

    public function getSummaryData($dataList)
    {
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

        return [
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaranBhp,
            'piutang' => $piutang,
            'laba_bersih' => $pendapatan - $pengeluaranBhp
        ];
    }

    public function print(Request $request)
    {
        $periodType = $request->query('periodType', 'DAILY');
        $selectedDate = $request->query('selectedDate', date('Y-m-d'));
        $selectedBulan = $request->query('selectedBulan', date('n'));
        $selectedTahun = $request->query('selectedTahun', date('Y'));
        $search = $request->query('search', '');

        $dataList = $this->getData($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search);
        $summary = $this->getSummaryData($dataList);

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $periodeDisplay = '';
        if ($periodType === 'DAILY') {
            $periodeDisplay = date('d F Y', strtotime($selectedDate));
        } elseif ($periodType === 'MONTHLY') {
            $periodeDisplay = $namaBulan[(int) $selectedBulan] . ' ' . $selectedTahun;
        } else {
            $periodeDisplay = 'Tahun ' . $selectedTahun;
        }

        $pdf = Pdf::loadView('modules.Laporan.pendapatan-pdf', [
            'dataList' => $dataList,
            'summary' => $summary,
            'periode' => $periodeDisplay,
            'periodType' => $periodType,
            'getPengeluaranBhp' => [$this, 'getPengeluaranBhp'],
            'getDetailBhp' => [$this, 'getDetailBhp']
        ])->setPaper('a4', 'portrait');

        $filename = 'Laporan_Pendapatan_' . str_replace(' ', '_', $periodeDisplay) . '.pdf';
        return $pdf->stream($filename);
    }

    public function exportExcel(Request $request)
    {
        $periodType = $request->query('periodType', 'DAILY');
        $selectedDate = $request->query('selectedDate', date('Y-m-d'));
        $selectedBulan = $request->query('selectedBulan', date('n'));
        $selectedTahun = $request->query('selectedTahun', date('Y'));
        $search = $request->query('search', '');

        $filename = 'Laporan_Pendapatan_' . $periodType . '_' . ($periodType === 'DAILY' ? $selectedDate : ($periodType === 'MONTHLY' ? $selectedBulan . '_' . $selectedTahun : $selectedTahun)) . '.xlsx';

        return Excel::download(new LaporanPendapatanExport($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search), $filename);
    }
}
