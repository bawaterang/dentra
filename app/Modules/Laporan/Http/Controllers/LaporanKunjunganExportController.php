<?php

namespace App\Modules\Laporan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxPendaftaran;
use App\Models\MstPasien;
use App\Modules\Laporan\Exports\LaporanKunjunganExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanKunjunganExportController extends Controller
{
    public function getData($periodType, $selectedDate, $selectedBulan, $selectedTahun, $selectedDokter = 'all')
    {
        $query = TrxPendaftaran::with(['pasien', 'dokter', 'asuransi', 'billing'])
            ->whereNotNull('created_at');

        if ($periodType === 'DAILY') {
            $query->whereDate('created_at', $selectedDate);
        } elseif ($periodType === 'MONTHLY') {
            $query->whereMonth('created_at', $selectedBulan)
                ->whereYear('created_at', $selectedTahun);
        } elseif ($periodType === 'YEARLY') {
            $query->whereYear('created_at', $selectedTahun);
        }

        if ($selectedDokter !== 'all') {
            $query->where('dokter_id', $selectedDokter);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getClinicalDetails($nomorKunjungan)
    {
        $pendaftaran = TrxPendaftaran::where('nomor_kunjungan', $nomorKunjungan)->first();
        $soap = DB::table('trx_pemeriksaan')->where('nomor_kunjungan', $nomorKunjungan)->first();
        
        $diagnoses = DB::table('trx_diagnosis')
            ->join('mst_diagnosis', 'trx_diagnosis.kode_diagnosa', '=', 'mst_diagnosis.kode_diagnosa')
            ->where('trx_diagnosis.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_diagnosis.deleted_at')
            ->select('mst_diagnosis.nama_diagnosa', 'trx_diagnosis.kode_diagnosa', 'trx_diagnosis.jenis_icd', 'trx_diagnosis.kasus_icd')
            ->get();

        $obat = DB::table('trx_obat')
            ->join('mst_obat', 'trx_obat.kode_obat', '=', 'mst_obat.kode_obat')
            ->where('trx_obat.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_obat.deleted_at')
            ->select('mst_obat.nama_obat', 'trx_obat.dosis', 'trx_obat.aturan')
            ->get();

        $ohis = DB::table('trx_ohis')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->whereNull('deleted_at')
            ->first();

        $odontogram_visit = DB::table('trx_odontogram')
            ->leftJoin('mst_kategori_gigi', 'trx_odontogram.kode_kategori', '=', 'mst_kategori_gigi.kode_kategori')
            ->where('trx_odontogram.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_odontogram.deleted_at')
            ->select('trx_odontogram.nomor_gigi', 'trx_odontogram.bagian', 'mst_kategori_gigi.nama_kategori', 'trx_odontogram.warna')
            ->get();

        return [
            'pemeriksaan_awal' => [
                'kesadaran' => $pendaftaran->kesadaran ?? null,
                'td' => $pendaftaran->tekanan_darah ?? null,
                'nadi' => $pendaftaran->nadi ?? null,
                'suhu' => $pendaftaran->suhu ?? null,
                'bb' => $pendaftaran->berat_badan ?? null,
                'tb' => $pendaftaran->tinggi_badan ?? null,
                'riwayat' => $pendaftaran->riwayat_penyakit ?? null,
                'alergi' => $pendaftaran->alergi ?? null,
            ],
            'soap' => $soap,
            'diagnoses' => $diagnoses,
            'obat' => $obat,
            'ohis' => $ohis,
            'odontogram_visit' => $odontogram_visit,
        ];
    }

    public function print(Request $request)
    {
        $periodType = $request->query('periodType', 'DAILY');
        $selectedDate = $request->query('selectedDate', date('Y-m-d'));
        $selectedBulan = $request->query('selectedBulan', date('n'));
        $selectedTahun = $request->query('selectedTahun', date('Y'));
        $selectedDokter = $request->query('selectedDokter', 'all');

        $dataList = $this->getData($periodType, $selectedDate, $selectedBulan, $selectedTahun, $selectedDokter);

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

        $pdf = Pdf::loadView('modules.Laporan.kunjungan-pdf', [
            'dataList' => $dataList,
            'periode' => $periodeDisplay,
            'periodType' => $periodType,
            'dokter' => $selectedDokter,
            'getClinicalDetails' => [$this, 'getClinicalDetails'],
        ])->setPaper('a4', 'portrait');

        $filename = 'Laporan_Kunjungan_' . str_replace(' ', '_', $periodeDisplay) . '.pdf';
        return $pdf->stream($filename);
    }

    public function exportExcel(Request $request)
    {
        $periodType = $request->query('periodType', 'DAILY');
        $selectedDate = $request->query('selectedDate', date('Y-m-d'));
        $selectedBulan = $request->query('selectedBulan', date('n'));
        $selectedTahun = $request->query('selectedTahun', date('Y'));
        $selectedDokter = $request->query('selectedDokter', 'all');

        $filename = 'Laporan_Kunjungan_' . $periodType . '_' . ($periodType === 'DAILY' ? $selectedDate : ($periodType === 'MONTHLY' ? $selectedBulan . '_' . $selectedTahun : $selectedTahun)) . '.xlsx';

        return Excel::download(new LaporanKunjunganExport($periodType, $selectedDate, $selectedBulan, $selectedTahun, $selectedDokter), $filename);
    }

    public function printRiwayat($pasienId)
    {
        $pasien = MstPasien::findOrFail($pasienId);
        
        $history = TrxPendaftaran::with(['dokter', 'asuransi'])
            ->where('pasien_id', $pasienId)
            ->whereNotNull('created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $historyData = [];
        foreach ($history as $item) {
            $historyData[] = [
                'pendaftaran' => $item,
                'clinical' => $this->getClinicalDetails($item->nomor_kunjungan)
            ];
        }

        // Get latest odontogram state for the patient
        $odontogram = DB::table('trx_odontogram')
            ->where('pasien_id', $pasienId)
            ->whereNull('deleted_at')
            ->get();

        $odontogramState = [];
        foreach ($odontogram as $o) {
            $odontogramState[$o->nomor_gigi . '-' . $o->bagian] = [
                'color' => $o->warna,
                'kategori' => $o->kode_kategori
            ];
        }

        $pdf = Pdf::loadView('modules.Laporan.riwayat-pasien-pdf', [
            'pasien' => $pasien,
            'historyData' => $historyData,
            'odontogramState' => $odontogramState
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Riwayat_Kunjungan_' . str_replace(' ', '_', $pasien->nama_pasien) . '.pdf');
    }
}
