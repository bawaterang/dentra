<?php

namespace App\Traits;

use App\Models\TrxPendaftaran;
use Illuminate\Support\Facades\DB;

trait HasPatientHistory
{
    public $showRiwayatModal = false;
    public $selectedPasienId;
    public $pasienHistoryData = [];
    public $currentPasien;
    public $latestOdontogramState = [];
    public $dentalCategories = [];

    public function getClinicalDetails($nomorKunjungan)
    {
        $pendaftaran = TrxPendaftaran::where('nomor_kunjungan', $nomorKunjungan)->first();
        if (!$pendaftaran) return null;

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
                'kesadaran' => $pendaftaran->kesadaran ? (\App\Models\MstKesadaran::where('kdSadar', $pendaftaran->kesadaran)->value('nmSadar') ?? $pendaftaran->kesadaran) : '-',
                'td' => $pendaftaran->tekanan_darah,
                'nadi' => $pendaftaran->nadi,
                'suhu' => $pendaftaran->suhu,
                'bb' => $pendaftaran->berat_badan,
                'tb' => $pendaftaran->tinggi_badan,
                'lp' => $pendaftaran->lingkar_perut,
                'riwayat' => $pendaftaran->riwayat_penyakit,
                'kode_alergi' => $pendaftaran->kode_alergi,
                'alergi_master' => $pendaftaran->kode_alergi ? (\App\Models\MstAlergi::where('kdAlergi', $pendaftaran->kode_alergi)->value('nmAlergi')) : '',
                'alergi' => $pendaftaran->alergi,
            ],
            'soap' => $soap,
            'diagnoses' => $diagnoses,
            'obat' => $obat,
            'ohis' => $ohis,
            'odontogram_visit' => $odontogram_visit,
        ];
    }

    public function openRiwayatModal($pasienId)
    {
        $this->selectedPasienId = $pasienId;
        $this->currentPasien = \App\Models\MstPasien::find($pasienId);

        if (!$this->currentPasien) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Data pasien tidak ditemukan.']);
            return;
        }

        $history = TrxPendaftaran::with(['dokter', 'asuransi', 'billing'])
            ->where('pasien_id', $pasienId)
            ->whereNotNull('created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->pasienHistoryData = [];
        foreach ($history as $item) {
            $this->pasienHistoryData[] = [
                'pendaftaran' => $item,
                'clinical' => $this->getClinicalDetails($item->nomor_kunjungan)
            ];
        }

        // Get latest odontogram state for the patient
        $odontogram = DB::table('trx_odontogram')
            ->where('pasien_id', $pasienId)
            ->whereNull('deleted_at')
            ->get();

        $this->latestOdontogramState = [];
        foreach ($odontogram as $o) {
            $this->latestOdontogramState[$o->nomor_gigi . '-' . $o->bagian] = [
                'color' => $o->warna,
                'kategori' => $o->kode_kategori
            ];
        }

        // Fetch dental categories for legend
        $this->dentalCategories = \App\Models\MstKategoriGigi::where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->orderBy('nama_kategori', 'asc')
            ->get();

        $this->showRiwayatModal = true;
    }

    public function closeRiwayatModal()
    {
        $this->showRiwayatModal = false;
        $this->selectedPasienId = null;
        $this->pasienHistoryData = [];
        $this->currentPasien = null;
    }
}
