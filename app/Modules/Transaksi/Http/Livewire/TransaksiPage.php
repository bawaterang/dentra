<?php

namespace App\Modules\Transaksi\Http\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\TrxPendaftaran;
use App\Models\MstPoli;
use App\Models\MstDiagnosis;
use App\Models\TrxPenunjangDokumen;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransaksiPage extends Component
{
    use WithFileUploads, WithPagination;
    public $selectedDate;
    public $selectedPoli = 'all';
    public $searchPasien = '';
    public $selectedPendaftaranId;
    public $selectedPendaftaran;
    public $poliList = [];
    public $poliListOptions = [];
    public $kesadaranList = [];
    public $alergiList = [];

    // SOAP / Anamnesis State
    public $subyektif = '';
    public $obyektif = '';
    public $assessment = '';
    public $planning = '';
    public $rekomendasi_diet = '';

    // Transaction Lists (Simulated for UI)
    public $diagnoses = [];
    public $tindakans = [];
    public $reseps = [];
    public $bmhps = [];

    // Vitals / Pemeriksaan Awal
    public $kesadaran = '';
    public $tekanan_darah = '';
    public $nadi = '';
    public $suhu = '';
    public $berat_badan = '';
    public $tinggi_badan = '';
    public $lingkar_perut = '';
    public $riwayat_penyakit = '';
    public $kode_alergi = '';
    public $alergi = '';
    public $keterangan_lain = '';

    // Form inputs (Temporary)
    public $tempDiagnosis = '';
    public $tempTindakan = '';
    public $tempObat = '';
    public $tempQty = 1;
    public $tempBmhp = '';

    // Diagnosis Modal State
    public $showDiagnosisModal = false;
    public $kode_diagnosa = '';
    public $jenis_icd = 'Utama';
    public $kasus_icd = 'Baru';

    // Tindakan Modal State
    public $showTindakanModal = false;
    public $kode_tindakan = '';
    public $biaya_tindakan = 0;
    public $jasmed_tindakan = 0;
    public $bhp_tindakan = 0;
    public $satuan_tindakan = '';

    // Resep (Obat) Modal State
    public $showResepModal = false;
    public $kode_obat = '';
    public $jumlah_obat = 1;
    public $dosis_obat = '';
    public $aturan_obat = '3x1';

    // BMHP Modal State
    public $showBmhpModal = false;
    public $kode_bmhp = '';
    public $jumlah_bmhp = 1;
    public $satuan_bmhp = '';

    // Odontogram State
    public $kategoriGigiOptions = [];
    public $odontogramState = [];

    // OHIS State (6 teeth: 16,11,26,36,31,46)
    public $ohis_di_16 = '';
    public $ohis_di_11 = '';
    public $ohis_di_26 = '';
    public $ohis_di_36 = '';
    public $ohis_di_31 = '';
    public $ohis_di_46 = '';
    public $ohis_ci_16 = '';
    public $ohis_ci_11 = '';
    public $ohis_ci_26 = '';
    public $ohis_ci_36 = '';
    public $ohis_ci_31 = '';
    public $ohis_ci_46 = '';

    // Screening Tab State
    public $screeningData = [];

    // Penunjang Tab State
    public $penunjangs = [];
    public $showPenunjangModal = false;
    public $penunjang_jenis = '';
    public $penunjang_nama = '';
    public $penunjang_file;

    // Riwayat Tab State
    public $riwayatData = [];
    public $expandedRiwayat = [];

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->kesadaranList = \App\Models\MstKesadaran::all()->map(fn($k) => ['value' => $k->kdSadar, 'label' => $k->nmSadar, 'icon' => 'ri-checkbox-circle-line text-green-500'])->toArray();
        $this->alergiList = \App\Models\MstAlergi::all()->map(fn($a) => ['value' => $a->kdAlergi, 'label' => $a->nmAlergi, 'icon' => 'ri-bug-line text-red-500'])->toArray();
    }

    public function prevDate()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        // No resetPage needed as TransaksiPage doesn't seem to use Livewire Pagination for the main list (it's a simple get())
    }

    public function nextDate()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
    }

    public function selectPendaftaran($id)
    {
        $this->selectedPendaftaranId = $id;
        $this->selectedPendaftaran = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'asuransi'])->find($id);
        
        // Load Pemeriksaan (SOAP) from DB
        $pemeriksaan = DB::table('trx_pemeriksaan')->where('nomor_kunjungan', $this->selectedPendaftaran?->nomor_kunjungan)->first();
        if ($pemeriksaan) {
            $this->subyektif = $pemeriksaan->subjective ?? '';
            $this->obyektif = $pemeriksaan->objective ?? '';
            $this->assessment = $pemeriksaan->assessment ?? '';
            $this->planning = $pemeriksaan->planning ?? '';
            $this->rekomendasi_diet = $pemeriksaan->rekomendasi_diet ?? '';
        } else {
            $this->subyektif = '';
            $this->obyektif = '';
            $this->assessment = '';
            $this->planning = '';
            $this->rekomendasi_diet = '';
        }
        
        // Vitals / Pemeriksaan Awal (From Pendaftaran)
        $this->kesadaran = $this->selectedPendaftaran?->kesadaran ?? '';
        
        // Mapping Kesadaran lama (string) ke kode jika perlu
        if ($this->kesadaran && !is_numeric($this->kesadaran) && strlen($this->kesadaran) > 2) {
            $matched = \App\Models\MstKesadaran::where('nmSadar', 'like', '%' . $this->kesadaran . '%')->first();
            if ($matched) {
                $this->kesadaran = $matched->kdSadar;
            }
        }
        
        $this->tekanan_darah = $this->selectedPendaftaran?->tekanan_darah ?? '';
        $this->nadi = $this->selectedPendaftaran?->nadi ?? '';
        $this->suhu = $this->selectedPendaftaran?->suhu ?? '';
        $this->berat_badan = $this->selectedPendaftaran?->berat_badan ?? '';
        $this->tinggi_badan = $this->selectedPendaftaran?->tinggi_badan ?? '';
        $this->lingkar_perut = $this->selectedPendaftaran?->lingkar_perut ?? '';
        $this->riwayat_penyakit = $this->selectedPendaftaran?->riwayat_penyakit ?? '';
        $this->kode_alergi = $this->selectedPendaftaran?->kode_alergi ?? '';
        $this->alergi = $this->selectedPendaftaran?->alergi ?? '';
        $this->keterangan_lain = $this->selectedPendaftaran?->keterangan_lain ?? '';
        
        // Load existing data if any
        $this->loadDiagnoses();
        $this->loadTindakans();
        $this->loadReseps();
        $this->loadBmhps();
        $this->loadOdontogram();
        $this->loadOhis();
        $this->loadScreening();
        $this->loadPenunjangs();
        $this->loadRiwayat();
        
        $this->dispatch('patient-selected');
    }

    public function loadDiagnoses()
    {
        if (!$this->selectedPendaftaran) return;
        
        $diags = DB::table('trx_diagnosis')
            ->join('mst_diagnosis', 'trx_diagnosis.kode_diagnosa', '=', 'mst_diagnosis.kode_diagnosa')
            ->where('trx_diagnosis.nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->whereNull('trx_diagnosis.deleted_at')
            ->select('trx_diagnosis.id', 'mst_diagnosis.nama_diagnosa as nama', 'trx_diagnosis.kode_diagnosa as kode', 'trx_diagnosis.jenis_icd', 'trx_diagnosis.kasus_icd')
            ->orderBy('trx_diagnosis.created_at', 'asc')
            ->get();
            
        $this->diagnoses = json_decode(json_encode($diags), true);
    }

    public function loadTindakans()
    {
        if (!$this->selectedPendaftaran?->nomor_kunjungan) return;
        
        $tdks = DB::table('trx_tindakan')
            ->join('mst_tindakan', 'trx_tindakan.kode_tindakan', '=', 'mst_tindakan.kode_tindakan')
            ->where('trx_tindakan.nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->whereNull('trx_tindakan.deleted_at')
            ->select('trx_tindakan.id', 'mst_tindakan.nama_tindakan as nama', 'trx_tindakan.kode_tindakan as kode', 'trx_tindakan.biaya', 'trx_tindakan.satuan', 'trx_tindakan.bhp')
            ->orderBy('trx_tindakan.created_at', 'asc')
            ->get();
            
        $this->tindakans = json_decode(json_encode($tdks), true);
    }

    public function loadReseps()
    {
        if (!$this->selectedPendaftaran?->nomor_kunjungan) return;
        
        $rsps = DB::table('trx_obat')
            ->join('mst_obat', 'trx_obat.kode_obat', '=', 'mst_obat.kode_obat')
            ->where('trx_obat.nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->whereNull('trx_obat.deleted_at')
            ->select('trx_obat.id', 'mst_obat.nama_obat as nama', 'trx_obat.kode_obat as kode', 'trx_obat.dosis as qty', 'trx_obat.aturan as signa')
            ->orderBy('trx_obat.created_at', 'asc')
            ->get();
            
        $this->reseps = json_decode(json_encode($rsps), true);
    }

    public function loadBmhps()
    {
        if (!$this->selectedPendaftaran?->nomor_kunjungan) return;
        
        $bmhps = DB::table('trx_bmhp')
            ->join('mst_bmhp', 'trx_bmhp.kode_bmhp', '=', 'mst_bmhp.kode_bmhp')
            ->where('trx_bmhp.nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->whereNull('trx_bmhp.deleted_at')
            ->select('trx_bmhp.id', 'mst_bmhp.nama_bmhp as nama', 'trx_bmhp.kode_bmhp as kode', 'trx_bmhp.jumlah', 'trx_bmhp.satuan')
            ->orderBy('trx_bmhp.created_at', 'asc')
            ->get();
            
        $this->bmhps = json_decode(json_encode($bmhps), true);
    }

    public function savePemeriksaan()
    {
        if (!$this->selectedPendaftaran) return;

        DB::table('trx_pemeriksaan')->updateOrInsert(
            ['nomor_kunjungan' => $this->selectedPendaftaran->nomor_kunjungan],
            [
                'kode_dokter' => $this->selectedPendaftaran->dokter_id ?? '',
                'subjective' => $this->subyektif,
                'objective' => $this->obyektif,
                'assessment' => $this->assessment,
                'planning' => $this->planning,
                'rekomendasi_diet' => $this->rekomendasi_diet,
                'created_by' => auth()->user()->username ?? 'System',
                'updated_at' => now(),
            ]
        );

        // Update Vitals di Pendaftaran
        TrxPendaftaran::where('nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)->update([
            'kesadaran' => $this->kesadaran,
            'tekanan_darah' => $this->tekanan_darah,
            'nadi' => $this->nadi,
            'suhu' => $this->suhu,
            'berat_badan' => $this->berat_badan,
            'tinggi_badan' => $this->tinggi_badan,
            'lingkar_perut' => $this->lingkar_perut,
            'riwayat_penyakit' => $this->riwayat_penyakit,
            'kode_alergi' => $this->kode_alergi,
            'alergi' => $this->alergi,
            'keterangan_lain' => $this->keterangan_lain,
        ]);

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Clinical Notes & Vitals berhasil disimpan.']);
    }

    public function saveOhis()
    {
        if (!$this->selectedPendaftaran) return;

        // Calculate totals
        $diValues = array_filter([$this->ohis_di_16, $this->ohis_di_11, $this->ohis_di_26, $this->ohis_di_36, $this->ohis_di_31, $this->ohis_di_46], fn($v) => $v !== '' && $v !== null);
        $ciValues = array_filter([$this->ohis_ci_16, $this->ohis_ci_11, $this->ohis_ci_26, $this->ohis_ci_36, $this->ohis_ci_31, $this->ohis_ci_46], fn($v) => $v !== '' && $v !== null);
        
        $diTotal = count($diValues) > 0 ? round(array_sum($diValues) / count($diValues), 2) : null;
        $ciTotal = count($ciValues) > 0 ? round(array_sum($ciValues) / count($ciValues), 2) : null;
        $ohisTotal = ($diTotal !== null || $ciTotal !== null) ? round(($diTotal ?? 0) + ($ciTotal ?? 0), 2) : null;
        
        $kategori = null;
        if ($ohisTotal !== null) {
            if ($ohisTotal <= 1.2) $kategori = 'Baik';
            elseif ($ohisTotal <= 3.0) $kategori = 'Sedang';
            else $kategori = 'Buruk';
        }

        DB::table('trx_ohis')->updateOrInsert(
            [
                'nomor_kunjungan' => $this->selectedPendaftaran->nomor_kunjungan,
                'pasien_id' => $this->selectedPendaftaran->pasien_id,
            ],
            [
                'di_16' => $this->ohis_di_16 !== '' ? $this->ohis_di_16 : null,
                'di_11' => $this->ohis_di_11 !== '' ? $this->ohis_di_11 : null,
                'di_26' => $this->ohis_di_26 !== '' ? $this->ohis_di_26 : null,
                'di_36' => $this->ohis_di_36 !== '' ? $this->ohis_di_36 : null,
                'di_31' => $this->ohis_di_31 !== '' ? $this->ohis_di_31 : null,
                'di_46' => $this->ohis_di_46 !== '' ? $this->ohis_di_46 : null,
                'ci_16' => $this->ohis_ci_16 !== '' ? $this->ohis_ci_16 : null,
                'ci_11' => $this->ohis_ci_11 !== '' ? $this->ohis_ci_11 : null,
                'ci_26' => $this->ohis_ci_26 !== '' ? $this->ohis_ci_26 : null,
                'ci_36' => $this->ohis_ci_36 !== '' ? $this->ohis_ci_36 : null,
                'ci_31' => $this->ohis_ci_31 !== '' ? $this->ohis_ci_31 : null,
                'ci_46' => $this->ohis_ci_46 !== '' ? $this->ohis_ci_46 : null,
                'di_total' => $diTotal,
                'ci_total' => $ciTotal,
                'ohis_total' => $ohisTotal,
                'kategori' => $kategori,
                'created_by' => auth()->user()->username ?? 'System',
                'updated_at' => now(),
            ]
        );

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data OHIS berhasil disimpan.']);
    }

    public function loadOhis()
    {
        // Reset all
        $this->ohis_di_16 = ''; $this->ohis_di_11 = ''; $this->ohis_di_26 = '';
        $this->ohis_di_36 = ''; $this->ohis_di_31 = ''; $this->ohis_di_46 = '';
        $this->ohis_ci_16 = ''; $this->ohis_ci_11 = ''; $this->ohis_ci_26 = '';
        $this->ohis_ci_36 = ''; $this->ohis_ci_31 = ''; $this->ohis_ci_46 = '';

        if (!$this->selectedPendaftaran) return;

        $ohis = DB::table('trx_ohis')
            ->where('nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->whereNull('deleted_at')
            ->first();

        if ($ohis) {
            $this->ohis_di_16 = $ohis->di_16 ?? '';
            $this->ohis_di_11 = $ohis->di_11 ?? '';
            $this->ohis_di_26 = $ohis->di_26 ?? '';
            $this->ohis_di_36 = $ohis->di_36 ?? '';
            $this->ohis_di_31 = $ohis->di_31 ?? '';
            $this->ohis_di_46 = $ohis->di_46 ?? '';
            $this->ohis_ci_16 = $ohis->ci_16 ?? '';
            $this->ohis_ci_11 = $ohis->ci_11 ?? '';
            $this->ohis_ci_26 = $ohis->ci_26 ?? '';
            $this->ohis_ci_36 = $ohis->ci_36 ?? '';
            $this->ohis_ci_31 = $ohis->ci_31 ?? '';
            $this->ohis_ci_46 = $ohis->ci_46 ?? '';
        }
    }


    public function saveOdontogram()
    {
        if (!$this->selectedPendaftaran) return;

        $nomorKunjungan = $this->selectedPendaftaran->nomor_kunjungan;
        $createdBy = auth()->user()->username ?? 'System';

        // Delete existing records for this visit+patient
        DB::table('trx_odontogram')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->where('pasien_id', $this->selectedPendaftaran->pasien_id)
            ->delete();

        // Insert all tooth-surface entries from state
        $records = [];
        foreach ($this->odontogramState as $key => $data) {
            // Key format: "18-T" => ["color" => "#xxx", "kode" => "xxx"]
            $parts = explode('-', $key);
            if (count($parts) !== 2) continue;
            
            $nomorGigi = $parts[0];
            $bagian = $parts[1];
            $color = is_array($data) ? ($data['color'] ?? null) : null;
            $kode = is_array($data) ? ($data['kode'] ?? null) : null;

            // Only save entries that have a color assigned
            if ($color) {
                $records[] = [
                    'nomor_kunjungan' => $nomorKunjungan,
                    'pasien_id' => $this->selectedPendaftaran->pasien_id,
                    'nomor_gigi' => $nomorGigi,
                    'bagian' => $bagian,
                    'kode_kategori' => $kode,
                    'warna' => $color,
                    'created_by' => $createdBy,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (count($records) > 0) {
            DB::table('trx_odontogram')->insert($records);
        }

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data Odontogram berhasil disimpan.']);
    }

    public function loadOdontogram()
    {
        $this->odontogramState = [];
        if (!$this->selectedPendaftaran) return;

        $rows = DB::table('trx_odontogram')
            ->where('pasien_id', $this->selectedPendaftaran->pasien_id)
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Group by tooth-surface, taking the latest entry
        $state = [];
        foreach ($rows as $row) {
            $key = $row->nomor_gigi . '-' . $row->bagian;
            if (!isset($state[$key])) {
                $state[$key] = [
                    'color' => $row->warna,
                    'kode' => $row->kode_kategori,
                ];
            }
        }

        $this->odontogramState = $state;
    }

    public function saveDiagnosis()
    {
        $this->validate([
            'kode_diagnosa' => 'required',
            'jenis_icd' => 'required',
            'kasus_icd' => 'required',
        ], [
            'kode_diagnosa.required' => 'Pilih diagnosis terlebih dahulu.',
        ]);

        if (!$this->selectedPendaftaran) return;

        // Check duplicates
        $exists = DB::table('trx_diagnosis')
            ->where('nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->where('kode_diagnosa', $this->kode_diagnosa)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            $this->addError('kode_diagnosa', 'Diagnosis ini sudah ditambahkan sebelumnya.');
            return;
        }

        DB::table('trx_diagnosis')->insert([
            'nomor_kunjungan' => $this->selectedPendaftaran->nomor_kunjungan,
            'kode_diagnosa' => $this->kode_diagnosa,
            'jenis_icd' => $this->jenis_icd,
            'kasus_icd' => $this->kasus_icd,
            'created_by' => auth()->user()->username ?? 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->showDiagnosisModal = false;
        $this->kode_diagnosa = '';
        $this->jenis_icd = 'Utama';
        $this->kasus_icd = 'Baru';
        
        $this->loadDiagnoses();
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Diagnosis berhasil ditambahkan.']);
    }

    public function saveTindakan()
    {
        $this->validate([
            'kode_tindakan' => 'required',
            'biaya_tindakan' => 'required|numeric',
        ]);

        if (!$this->selectedPendaftaran) return;

        DB::table('trx_tindakan')->insert([
            'nomor_kunjungan' => $this->selectedPendaftaran->nomor_kunjungan,
            'kode_tindakan' => $this->kode_tindakan,
            'kode_asuransi' => $this->selectedPendaftaran->asuransi_id ?? 'UMUM',
            'biaya' => $this->biaya_tindakan,
            'jasa_medis' => $this->jasmed_tindakan,
            'bhp' => $this->bhp_tindakan,
            'satuan' => $this->satuan_tindakan,
            'created_by' => auth()->user()->username ?? 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->showTindakanModal = false;
        $this->kode_tindakan = '';
        $this->biaya_tindakan = 0;
        
        $this->loadTindakans();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Tindakan berhasil ditambahkan.']);
    }

    public function saveResep()
    {
        $this->validate([
            'kode_obat' => 'required',
            'jumlah_obat' => 'required|numeric|min:1',
        ]);

        if (!$this->selectedPendaftaran) return;

        // Check duplicates
        $exists = DB::table('trx_obat')
            ->where('nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->where('kode_obat', $this->kode_obat)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            $this->addError('kode_obat', 'Obat ini sudah ditambahkan sebelumnya.');
            return;
        }

        DB::table('trx_obat')->insert([
            'nomor_kunjungan' => $this->selectedPendaftaran->nomor_kunjungan,
            'kode_obat' => $this->kode_obat,
            'dosis' => $this->jumlah_obat,
            'aturan' => $this->aturan_obat,
            'tanggal_obat' => now()->format('Y-m-d'),
            'created_by' => auth()->user()->username ?? 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->showResepModal = false;
        $this->kode_obat = '';
        $this->jumlah_obat = 1;
        
        $this->loadReseps();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Resep berhasil ditambahkan.']);
    }

    public function saveBmhp()
    {
        $this->validate([
            'kode_bmhp' => 'required',
            'jumlah_bmhp' => 'required|numeric|min:1',
        ]);

        if (!$this->selectedPendaftaran) return;

        DB::table('trx_bmhp')->insert([
            'nomor_kunjungan' => $this->selectedPendaftaran->nomor_kunjungan,
            'kode_bmhp' => $this->kode_bmhp,
            'jumlah' => $this->jumlah_bmhp,
            'satuan' => $this->satuan_bmhp,
            'created_by' => auth()->user()->username ?? 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->showBmhpModal = false;
        $this->kode_bmhp = '';
        $this->jumlah_bmhp = 1;
        
        $this->loadBmhps();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'BMHP berhasil ditambahkan.']);
    }

    public function updatedKodeTindakan($value)
    {
        if (!$value) {
            $this->biaya_tindakan = 0;
            $this->jasmed_tindakan = 0;
            $this->bhp_tindakan = 0;
            $this->satuan_tindakan = '';
            return;
        }

        if ($this->selectedPendaftaran) {
            $asuransi_kode = $this->selectedPendaftaran->asuransi->kode_asuransi ?? 'UMUM';
            $tarif = DB::table('mst_tarif')
                ->where('kode_tindakan', $value)
                ->where('kode_asuransi', $asuransi_kode)
                ->first();
            
            if ($tarif) {
                $this->biaya_tindakan = $tarif->tarif;
                $this->jasmed_tindakan = $tarif->jasmed;
                $this->bhp_tindakan = $tarif->bhp;
                $this->satuan_tindakan = $tarif->satuan_jasmed; // Updated from 'satuan'
            } else {
                $mst = DB::table('mst_tindakan')->where('kode_tindakan', $value)->first();
                $this->biaya_tindakan = $mst?->harga_default ?? 0;
                $this->jasmed_tindakan = 0;
                $this->bhp_tindakan = 0;
                $this->satuan_tindakan = $mst?->satuan ?? '';
            }
        }
    }

    public function updatedKodeObat($value)
    {
        if ($value) {
            // Optional: load medicine unit or stock
        }
    }

    public function updatedKodeBmhp($value)
    {
        if (!$value) {
            $this->satuan_bmhp = '';
            return;
        }

        $bmhp = DB::table('mst_bmhp')->where('kode_bmhp', $value)->first();
        $this->satuan_bmhp = $bmhp?->satuan ?? '';
    }

    public function addTindakan()
    {
        $this->showTindakanModal = true;
    }

    public function addResep()
    {
        $this->showResepModal = true;
    }

    public function addBmhp()
    {
        $this->showBmhpModal = true;
    }

    public function removeItem($list, $index)
    {
        if (!isset($this->{$list}[$index]['id'])) return;
        $id = $this->{$list}[$index]['id'];

        $tableMap = [
            'diagnoses' => 'trx_diagnosis',
            'tindakans' => 'trx_tindakan',
            'reseps' => 'trx_obat',
            'bmhps' => 'trx_bmhp',
        ];

        if (isset($tableMap[$list])) {
            DB::table($tableMap[$list])->where('id', $id)->update(['deleted_at' => now()]);
            
            // Reload the specific list
            $method = 'load' . ucfirst($list);
            if (method_exists($this, $method)) {
                $this->$method();
            }
            
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Data berhasil dihapus.']);
        }
    }

    // ─── Async Search Methods (Server-Side, max 20 results) ───

    public function searchDiagnosis($term)
    {
        if (strlen($term) < 2) return [];
        return DB::table('mst_diagnosis')
            ->where(function($q) use ($term) {
                $q->where('kode_diagnosa', 'like', '%'.$term.'%')
                  ->orWhere('nama_diagnosa', 'like', '%'.$term.'%');
            })
            ->limit(20)
            ->get()
            ->map(fn($d) => ['value' => $d->kode_diagnosa, 'label' => $d->kode_diagnosa.' - '.$d->nama_diagnosa, 'icon' => 'ri-microscope-line text-warning'])
            ->toArray();
    }

    public function getDiagnosisLabel($kode)
    {
        $d = DB::table('mst_diagnosis')->where('kode_diagnosa', $kode)->first();
        return $d ? ['label' => $d->kode_diagnosa.' - '.$d->nama_diagnosa, 'icon' => 'ri-microscope-line text-warning'] : null;
    }

    public function searchTindakan($term)
    {
        if (strlen($term) < 2) return [];
        return DB::table('mst_tindakan')
            ->where('status', 'Aktif')
            ->where(function($q) use ($term) {
                $q->where('kode_tindakan', 'like', '%'.$term.'%')
                  ->orWhere('nama_tindakan', 'like', '%'.$term.'%');
            })
            ->limit(20)
            ->get()
            ->map(fn($t) => ['value' => $t->kode_tindakan, 'label' => $t->kode_tindakan.' - '.$t->nama_tindakan, 'icon' => 'ri-hand-heart-line text-primary'])
            ->toArray();
    }

    public function getTindakanLabel($kode)
    {
        $t = DB::table('mst_tindakan')->where('kode_tindakan', $kode)->first();
        return $t ? ['label' => $t->kode_tindakan.' - '.$t->nama_tindakan, 'icon' => 'ri-hand-heart-line text-primary'] : null;
    }

    public function searchObat($term)
    {
        if (strlen($term) < 2) return [];
        return DB::table('mst_obat')
            ->where('status', 'Aktif')
            ->where(function($q) use ($term) {
                $q->where('kode_obat', 'like', '%'.$term.'%')
                  ->orWhere('nama_obat', 'like', '%'.$term.'%');
            })
            ->limit(20)
            ->get()
            ->map(fn($o) => ['value' => $o->kode_obat, 'label' => $o->kode_obat.' - '.$o->nama_obat, 'icon' => 'ri-capsule-line text-emerald-500'])
            ->toArray();
    }

    public function getObatLabel($kode)
    {
        $o = DB::table('mst_obat')->where('kode_obat', $kode)->first();
        return $o ? ['label' => $o->kode_obat.' - '.$o->nama_obat, 'icon' => 'ri-capsule-line text-emerald-500'] : null;
    }

    public function searchBmhp($term)
    {
        if (strlen($term) < 2) return [];
        return DB::table('mst_bmhp')
            ->where('status', 'Aktif')
            ->where(function($q) use ($term) {
                $q->where('kode_bmhp', 'like', '%'.$term.'%')
                  ->orWhere('nama_bmhp', 'like', '%'.$term.'%');
            })
            ->limit(20)
            ->get()
            ->map(fn($b) => ['value' => $b->kode_bmhp, 'label' => $b->kode_bmhp.' - '.$b->nama_bmhp, 'icon' => 'ri-flask-line text-purple-500'])
            ->toArray();
    }

    public function getBmhpLabel($kode)
    {
        $b = DB::table('mst_bmhp')->where('kode_bmhp', $kode)->first();
        return $b ? ['label' => $b->kode_bmhp.' - '.$b->nama_bmhp, 'icon' => 'ri-flask-line text-purple-500'] : null;
    }

    // ─── Screening Tab Methods ───

    public function loadScreening()
    {
        $this->screeningData = [];
        if (!$this->selectedPendaftaran) return;

        $this->screeningData = DB::table('trx_screening')
            ->join('mst_survei', 'trx_screening.survei_id', '=', 'mst_survei.id')
            ->where('trx_screening.pendaftaran_id', $this->selectedPendaftaran->id)
            ->select('trx_screening.*', 'mst_survei.pertanyaan', 'mst_survei.jenis_survei')
            ->orderBy('mst_survei.id', 'asc')
            ->get()
            ->toArray();
    }

    // ─── Penunjang Tab Methods ───

    public function loadPenunjangs()
    {
        $this->penunjangs = [];
        if (!$this->selectedPendaftaran?->nomor_kunjungan) return;

        $this->penunjangs = DB::table('trx_penunjang_dokumen')
            ->where('nomor_kunjungan', $this->selectedPendaftaran->nomor_kunjungan)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => (array) $p)
            ->toArray();
    }

    public function addPenunjang()
    {
        $this->reset(['penunjang_jenis', 'penunjang_nama', 'penunjang_file']);
        $this->showPenunjangModal = true;
    }

    public function savePenunjang()
    {
        $this->validate([
            'penunjang_nama' => 'required|string|max:255',
            'penunjang_jenis' => 'required|string',
            'penunjang_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'penunjang_nama.required' => 'Nama dokumen wajib diisi.',
            'penunjang_jenis.required' => 'Jenis penunjang wajib dipilih.',
            'penunjang_file.required' => 'File wajib diunggah.',
            'penunjang_file.mimes' => 'Format: PDF, JPG, PNG.',
            'penunjang_file.max' => 'Maksimal 10MB.',
        ]);

        if (!$this->selectedPendaftaran) return;

        $filePath = $this->penunjang_file->store('penunjang', 'public');

        DB::table('trx_penunjang_dokumen')->insert([
            'nomor_kunjungan' => $this->selectedPendaftaran->nomor_kunjungan,
            'no_rm' => $this->selectedPendaftaran->pasien->no_rm ?? '',
            'document_name' => $this->penunjang_nama,
            'jenis' => $this->penunjang_jenis,
            'file_path' => $filePath,
            'created_by' => auth()->user()->username ?? 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->showPenunjangModal = false;
        $this->reset(['penunjang_jenis', 'penunjang_nama', 'penunjang_file']);
        $this->loadPenunjangs();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Dokumen penunjang berhasil diunggah.']);
    }

    public function removePenunjang($id)
    {
        $doc = DB::table('trx_penunjang_dokumen')->where('id', $id)->first();
        if ($doc && $doc->file_path) {
            Storage::disk('public')->delete($doc->file_path);
        }
        DB::table('trx_penunjang_dokumen')->where('id', $id)->update(['deleted_at' => now()]);
        $this->loadPenunjangs();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Dokumen berhasil dihapus.']);
    }

    // ─── Riwayat Tab Methods ───

    public function loadRiwayat()
    {
        $this->riwayatData = [];
        $this->expandedRiwayat = [];
        if (!$this->selectedPendaftaran?->pasien_id) return;

        $history = TrxPendaftaran::with(['dokter', 'asuransi', 'poli'])
            ->where('pasien_id', $this->selectedPendaftaran->pasien_id)
            ->whereNotNull('created_at')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        foreach ($history as $item) {
            $this->riwayatData[] = [
                'id' => $item->id,
                'nomor_kunjungan' => $item->nomor_kunjungan,
                'tanggal' => $item->created_at?->format('d/m/Y H:i'),
                'dokter' => $item->dokter?->nama_dokter ?? '-',
                'poli' => $item->poli?->nama_poli ?? '-',
                'asuransi' => $item->asuransi?->nama_asuransi ?? 'UMUM',
                'status' => $item->status,
                'clinical' => null, // lazy-load on expand
            ];
        }
    }

    public function toggleRiwayatDetail($index)
    {
        if (isset($this->expandedRiwayat[$index])) {
            unset($this->expandedRiwayat[$index]);
        } else {
            // Lazy load clinical details
            if (!isset($this->riwayatData[$index]['clinical']) || $this->riwayatData[$index]['clinical'] === null) {
                $nk = $this->riwayatData[$index]['nomor_kunjungan'];
                $this->riwayatData[$index]['clinical'] = $this->getClinicalDetailsForRiwayat($nk);
            }
            $this->expandedRiwayat[$index] = true;
        }
    }

    public function getClinicalDetailsForRiwayat($nomorKunjungan)
    {
        $pendaftaran = TrxPendaftaran::where('nomor_kunjungan', $nomorKunjungan)->first();
        $soap = DB::table('trx_pemeriksaan')->where('nomor_kunjungan', $nomorKunjungan)->first();

        $diagnoses = DB::table('trx_diagnosis')
            ->join('mst_diagnosis', 'trx_diagnosis.kode_diagnosa', '=', 'mst_diagnosis.kode_diagnosa')
            ->where('trx_diagnosis.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_diagnosis.deleted_at')
            ->select('mst_diagnosis.nama_diagnosa', 'trx_diagnosis.kode_diagnosa', 'trx_diagnosis.jenis_icd')
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();

        $obat = DB::table('trx_obat')
            ->join('mst_obat', 'trx_obat.kode_obat', '=', 'mst_obat.kode_obat')
            ->where('trx_obat.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_obat.deleted_at')
            ->select('mst_obat.nama_obat', 'trx_obat.dosis', 'trx_obat.aturan')
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();

        return [
            'vitals' => [
                'kesadaran' => $pendaftaran?->kesadaran ? (\App\Models\MstKesadaran::where('kdSadar', $pendaftaran->kesadaran)->value('nmSadar') ?? $pendaftaran->kesadaran) : '-',
                'td' => $pendaftaran?->tekanan_darah,
                'nadi' => $pendaftaran?->nadi,
                'suhu' => $pendaftaran?->suhu,
                'bb' => $pendaftaran?->berat_badan,
                'tb' => $pendaftaran?->tinggi_badan,
                'lp' => $pendaftaran?->lingkar_perut,
                'alergi' => trim(($pendaftaran?->kode_alergi ? (\App\Models\MstAlergi::where('kdAlergi', $pendaftaran->kode_alergi)->value('nmAlergi') . ' ') : '') . ($pendaftaran?->alergi ?? '')),
            ],
            'soap' => $soap ? [
                'subjective' => $soap->subjective ?? '-',
                'objective' => $soap->objective ?? '-',
                'assessment' => $soap->assessment ?? '-',
                'planning' => $soap->planning ?? '-',
                'rekomendasi_diet' => $soap->rekomendasi_diet ?? '-',
            ] : null,
            'diagnoses' => $diagnoses,
            'obat' => $obat,
        ];
    }

    public function updatedSearchPasien()
    {
        $this->resetPage();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
    }

    public function updatedSelectedPoli()
    {
        $this->resetPage();
    }

    #[Computed]
    public function pasienList()
    {
        $isAdmin = auth()->user()->roles()->wherePivot('role_id', 1)->exists();
        $query = TrxPendaftaran::with(['pasien', 'poli', 'dokter'])
            ->whereDate('created_at', $this->selectedDate)
            ->whereIn('status', ['terdaftar', 'menunggu_screening', 'selesai']);

        if (!$isAdmin) {
            $user = auth()->user();
            if ($user->dokter) {
                $query->where('dokter_id', $user->dokter->id);
            }
        }

        if ($this->selectedPoli !== 'all') {
            $query->where('poli_id', $this->selectedPoli);
        }

        if ($this->searchPasien) {
            $query->whereHas('pasien', function($q) {
                $q->where('nama_pasien', 'like', '%' . $this->searchPasien . '%')
                  ->orWhere('no_rm', 'like', '%' . $this->searchPasien . '%');
            });
        }

        return $query->orderBy('created_at', 'asc')->paginate(10);
    }

    public function render()
    {
        // Filter Poli based on User Mapping (trx_user_poli -> mst_poli)
        // Only Administrator (role_id=1 in trx_role_user) sees all polis
        $isAdmin = auth()->user()->roles()->wherePivot('role_id', 1)->exists();
        if ($isAdmin) {
            $this->poliList = MstPoli::where('status', 'Aktif')->get();
        } else {
            $this->poliList = auth()->user()->polis()->where('status', 'Aktif')->get();
        }

        $this->poliListOptions = $this->poliList->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli, 'icon' => 'ri-hospital-line text-blue-500'])->toArray();
        if ($this->poliList->count() > 1) {
            array_unshift($this->poliListOptions, ['value' => 'all', 'label' => 'Semua Poli', 'icon' => 'ri-group-line text-gray-500']);
        } elseif ($this->poliList->count() === 1 && $this->selectedPoli === 'all') {
            $this->selectedPoli = $this->poliList->first()->id;
        }
        
        if (empty($this->kesadaranList)) {
            $this->kesadaranList = \App\Models\MstKesadaran::all()->map(fn($k) => ['value' => $k->kdSadar, 'label' => $k->nmSadar, 'icon' => 'ri-checkbox-circle-line text-green-500'])->toArray();
        }

        if (empty($this->alergiList)) {
            $this->alergiList = \App\Models\MstAlergi::all()->map(fn($a) => ['value' => $a->kdAlergi, 'label' => $a->nmAlergi, 'icon' => 'ri-bug-line text-red-500'])->toArray();
        }

        // Load only small datasets eagerly (kategori gigi is tiny)
        if (empty($this->kategoriGigiOptions)) {
            $this->kategoriGigiOptions = DB::table('mst_kategori_gigi')
                ->where('status', 'Aktif')
                ->select('id', 'kode_kategori', 'nama_kategori', 'warna')
                ->get()
                ->toArray();
        }
        // NOTE: diagnosisListOptions, tindakanListOptions, obatListOptions, bmhpListOptions
        // are now loaded on-demand via searchDiagnosis(), searchTindakan(), searchObat(), searchBmhp()

        return view('livewire.modules.transaksi.transaksi-page');
    }
}
