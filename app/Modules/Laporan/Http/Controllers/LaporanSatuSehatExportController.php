<?php

namespace App\Modules\Laporan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxPendaftaran;
use App\Modules\Laporan\Exports\LaporanSatuSehatExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanSatuSehatExportController extends Controller
{
    public function getData($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search = '')
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'asuransi'])
            ->leftJoin('trx_satusehat_status', 'trx_pendaftaran.nomor_kunjungan', '=', 'trx_satusehat_status.nomor_kunjungan')
            ->select('trx_pendaftaran.*', 'trx_satusehat_status.status_bundle')
            ->whereNotNull('trx_pendaftaran.created_at');

        if ($periodType === 'DAILY') {
            $query->whereDate('trx_pendaftaran.created_at', $selectedDate);
        } elseif ($periodType === 'MONTHLY') {
            $query->whereMonth('trx_pendaftaran.created_at', $selectedBulan)
                ->whereYear('trx_pendaftaran.created_at', $selectedTahun);
        } elseif ($periodType === 'YEARLY') {
            $query->whereYear('trx_pendaftaran.created_at', $selectedTahun);
        }

        if (!empty($search)) {
            $query->whereHas('pasien', function ($q) use ($search) {
                $q->where('nama_pasien', 'like', '%' . $search . '%')
                  ->orWhere('no_rm', 'like', '%' . $search . '%')
                  ->orWhere('nik', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('trx_pendaftaran.created_at', 'desc')->get();
    }

    public function print(Request $request)
    {
        $periodType = $request->query('periodType', 'DAILY');
        $selectedDate = $request->query('selectedDate', date('Y-m-d'));
        $selectedBulan = $request->query('selectedBulan', date('n'));
        $selectedTahun = $request->query('selectedTahun', date('Y'));
        $search = $request->query('search', '');

        $dataList = $this->getData($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search);

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

        $pdf = Pdf::loadView('modules.Laporan.satu-sehat-pdf', [
            'dataList' => $dataList,
            'periode' => $periodeDisplay,
        ])->setPaper('a4', 'landscape');

        $filename = 'Laporan_Satu_Sehat_' . str_replace(' ', '_', $periodeDisplay) . '.pdf';
        return $pdf->stream($filename);
    }

    public function exportExcel(Request $request)
    {
        $periodType = $request->query('periodType', 'DAILY');
        $selectedDate = $request->query('selectedDate', date('Y-m-d'));
        $selectedBulan = $request->query('selectedBulan', date('n'));
        $selectedTahun = $request->query('selectedTahun', date('Y'));
        $search = $request->query('search', '');

        $filename = 'Laporan_Satu_Sehat_' . $periodType . '_' . ($periodType === 'DAILY' ? $selectedDate : ($periodType === 'MONTHLY' ? $selectedBulan . '_' . $selectedTahun : $selectedTahun)) . '.xlsx';

        return Excel::download(new LaporanSatuSehatExport($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search), $filename);
    }
}
