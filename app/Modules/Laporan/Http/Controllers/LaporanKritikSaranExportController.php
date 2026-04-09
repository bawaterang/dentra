<?php

namespace App\Modules\Laporan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxMessage;
use App\Modules\Laporan\Exports\LaporanKritikSaranExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanKritikSaranExportController extends Controller
{
    public function getData($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search = '')
    {
        $query = TrxMessage::query()->whereNotNull('created_at');

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
                $q->where('nama', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('nomor_hp', 'like', '%' . $search . '%')
                  ->orWhere('pesan', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
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

        $pdf = Pdf::loadView('modules.Laporan.kritik-saran-pdf', [
            'dataList' => $dataList,
            'periode' => $periodeDisplay,
            'periodType' => $periodType,
        ])->setPaper('a4', 'landscape');

        $filename = 'Laporan_Kritik_Saran_' . str_replace(' ', '_', $periodeDisplay) . '.pdf';
        return $pdf->stream($filename);
    }

    public function exportExcel(Request $request)
    {
        $periodType = $request->query('periodType', 'DAILY');
        $selectedDate = $request->query('selectedDate', date('Y-m-d'));
        $selectedBulan = $request->query('selectedBulan', date('n'));
        $selectedTahun = $request->query('selectedTahun', date('Y'));
        $search = $request->query('search', '');

        $filename = 'Laporan_Kritik_Saran_' . $periodType . '_' . ($periodType === 'DAILY' ? $selectedDate : ($periodType === 'MONTHLY' ? $selectedBulan . '_' . $selectedTahun : $selectedTahun)) . '.xlsx';

        return Excel::download(new LaporanKritikSaranExport($periodType, $selectedDate, $selectedBulan, $selectedTahun, $search), $filename);
    }
}
