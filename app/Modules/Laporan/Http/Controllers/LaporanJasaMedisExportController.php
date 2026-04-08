<?php

namespace App\Modules\Laporan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxPendaftaran;
use App\Models\TrxTindakan;
use App\Modules\Laporan\Exports\LaporanJasaMedisExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanJasaMedisExportController extends Controller
{
    public function getData($bulan, $tahun, $dokter = 'all')
    {
        $query = TrxPendaftaran::with([
            'pasien', 
            'dokter' => fn($q) => $q->withTrashed(), 
            'asuransi'
        ])
            ->whereNotNull('created_at')
            ->whereMonth('created_at', $bulan)
            ->whereYear('created_at', $tahun);

        if ($dokter !== 'all') {
            $query->where('dokter_id', $dokter);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getTindakanByKunjungan($nomorKunjungan)
    {
        return TrxTindakan::withoutTrashed()->where('nomor_kunjungan', $nomorKunjungan)->get();
    }

    public function print(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));
        $dokter = $request->query('dokter', 'all');

        $dataList = $this->getData($bulan, $tahun, $dokter);

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $pdf = Pdf::loadView('modules.Laporan.jasamedis-pdf', [
            'dataList' => $dataList,
            'bulan' => $namaBulan[(int) $bulan],
            'tahun' => $tahun,
            'dokter' => $dokter,
            'getTindakanByKunjungan' => [$this, 'getTindakanByKunjungan'],
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Jasa_Medis_'.$namaBulan[(int) $bulan].'_'.$tahun.'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));
        $dokter = $request->query('dokter', 'all');

        return Excel::download(new LaporanJasaMedisExport($bulan, $tahun, $dokter), 'Laporan_Jasa_Medis_'.$bulan.'_'.$tahun.'.xlsx');
    }
}
