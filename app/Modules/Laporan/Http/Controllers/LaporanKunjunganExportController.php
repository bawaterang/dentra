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
        $query = TrxPendaftaran::with([
            'pasien', 
            'dokter' => fn($q) => $q->withTrashed(), 
            'asuransi', 
            'billing'
        ])
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
        
        $alergiName = null;
        if ($pendaftaran && $pendaftaran->kode_alergi) {
            $mstAlergi = DB::table('mst_alergi')->where('kdAlergi', $pendaftaran->kode_alergi)->first();
            if ($mstAlergi) {
                $alergiName = $mstAlergi->nmAlergi;
            }
        }

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
                'kesadaran' => $pendaftaran && $pendaftaran->kesadaran ? (\App\Models\MstKesadaran::where('kdSadar', $pendaftaran->kesadaran)->value('nmSadar') ?? $pendaftaran->kesadaran) : '-',
                'td' => $pendaftaran->tekanan_darah ?? null,
                'nadi' => $pendaftaran->nadi ?? null,
                'suhu' => $pendaftaran->suhu ?? null,
                'bb' => $pendaftaran->berat_badan ?? null,
                'tb' => $pendaftaran->tinggi_badan ?? null,
                'lp' => $pendaftaran->lingkar_perut ?? null,
                'riwayat' => $pendaftaran->riwayat_penyakit ?? null,
                'kode_alergi' => $pendaftaran->kode_alergi ?? null,
                'alergi_master' => $alergiName,
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

        // Get OHI-S from the most recent visit specifically
        $latestOhis = $historyData[0]['clinical']['ohis'] ?? null;

        // Get odontogram categories for legend
        $odontogramCategories = DB::table('mst_kategori_gigi')
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->orderBy('nama_kategori', 'asc')
            ->get();

        // Generate tooth images as base64 PNGs for PDF rendering
        $allTeeth = [
            18,17,16,15,14,13,12,11, 21,22,23,24,25,26,27,28,
            55,54,53,52,51, 61,62,63,64,65,
            85,84,83,82,81, 71,72,73,74,75,
            48,47,46,45,44,43,42,41, 31,32,33,34,35,36,37,38
        ];

        $toothImages = [];
        foreach ($allTeeth as $t) {
            $colors = [
                'T' => $odontogramState[$t.'-T']['color'] ?? '#ffffff',
                'R' => $odontogramState[$t.'-R']['color'] ?? '#ffffff',
                'B' => $odontogramState[$t.'-B']['color'] ?? '#ffffff',
                'L' => $odontogramState[$t.'-L']['color'] ?? '#ffffff',
                'C' => $odontogramState[$t.'-C']['color'] ?? '#ffffff',
            ];
            $toothImages[$t] = $this->generateToothImage($colors);
        }

        $pdf = Pdf::loadView('modules.Laporan.riwayat-pasien-pdf', [
            'pasien' => $pasien,
            'historyData' => $historyData,
            'odontogramState' => $odontogramState,
            'latestOhis' => $latestOhis,
            'odontogramCategories' => $odontogramCategories,
            'toothImages' => $toothImages
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Riwayat_Kunjungan_' . str_replace(' ', '_', $pasien->nama_pasien) . '.pdf');
    }

    /**
     * Generate a single tooth PNG image using GD library.
     * Draws the exact same cross/trapezoid shape as the modal SVG odontogram.
     * Returns a base64-encoded PNG string.
     */
    private function generateToothImage(array $colors): string
    {
        $size = 80; // Render at 80px for crisp quality, display at ~22px in PDF
        $img = imagecreatetruecolor($size, $size);

        // White background
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);

        // Scale factor: SVG viewBox is 40x40, we render at 80x80
        // SVG coordinates × 2
        // Inner offset: SVG 10 → pixel 20, SVG 30 → pixel 60
        $s = $size - 1; // 79
        $i = 20;        // inner border start
        $o = 60;        // inner border end

        // Define segments matching SVG paths exactly:
        // T: M0,0 L40,0 L30,10 L10,10 Z  → top trapezoid
        // R: M40,0 L40,40 L30,30 L30,10 Z → right trapezoid
        // B: M40,40 L0,40 L10,30 L30,30 Z → bottom trapezoid
        // L: M0,0 L10,10 L10,30 L0,40 Z   → left trapezoid
        // C: M10,10 L30,10 L30,30 L10,30 Z → center square
        $segmentDefs = [
            'T' => [0,0, $s,0, $o,$i, $i,$i],
            'R' => [$s,0, $s,$s, $o,$o, $o,$i],
            'B' => [$s,$s, 0,$s, $i,$o, $o,$o],
            'L' => [0,0, $i,$i, $i,$o, 0,$s],
            'C' => [$i,$i, $o,$i, $o,$o, $i,$o],
        ];

        foreach ($segmentDefs as $seg => $points) {
            $hex = ltrim($colors[$seg] ?? '#ffffff', '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $color = imagecolorallocate($img, $r, $g, $b);
            imagefilledpolygon($img, $points, $color);
        }

        // Draw borders with dark gray lines
        $black = imagecolorallocate($img, 51, 51, 51); // #333333
        imagesetthickness($img, 2);

        // Outer square border
        imageline($img, 0, 0, $s, 0, $black);  // top edge
        imageline($img, $s, 0, $s, $s, $black); // right edge
        imageline($img, $s, $s, 0, $s, $black); // bottom edge
        imageline($img, 0, $s, 0, 0, $black);   // left edge

        // Diagonal lines from corners to inner square
        imageline($img, 0, 0, $i, $i, $black);   // top-left diagonal
        imageline($img, $s, 0, $o, $i, $black);   // top-right diagonal
        imageline($img, $s, $s, $o, $o, $black);  // bottom-right diagonal
        imageline($img, 0, $s, $i, $o, $black);   // bottom-left diagonal

        // Inner square border
        imageline($img, $i, $i, $o, $i, $black);  // inner top
        imageline($img, $o, $i, $o, $o, $black);  // inner right
        imageline($img, $o, $o, $i, $o, $black);  // inner bottom
        imageline($img, $i, $o, $i, $i, $black);  // inner left

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return base64_encode($data);
    }
}
