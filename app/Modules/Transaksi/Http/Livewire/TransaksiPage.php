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
    public $riwayat_penyakit = '';
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
        $this->tekanan_darah = $this->selectedPendaftaran?->tekanan_darah ?? '';
        $this->nadi = $this->selectedPendaftaran?->nadi ?? '';
        $this->suhu = $this->selectedPendaftaran?->suhu ?? '';
        $this->berat_badan = $this->selectedPendaftaran?->berat_badan ?? '';
        $this->tinggi_badan = $this->selectedPendaftaran?->tinggi_badan ?? '';
        $this->riwayat_penyakit = $this->selectedPendaftaran?->riwayat_penyakit ?? '';
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

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Clinical Notes berhasil disimpan.']);
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

            // Only save entries that have a color assigned (not white/empty)
            if ($color && $color !== 'white') {
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
                'kesadaran' => $pendaftaran?->kesadaran,
                'td' => $pendaftaran?->tekanan_darah,
                'nadi' => $pendaftaran?->nadi,
                'suhu' => $pendaftaran?->suhu,
                'bb' => $pendaftaran?->berat_badan,
                'tb' => $pendaftaran?->tinggi_badan,
            ],
            'soap' => $soap ? [
                'subjective' => $soap->subjective ?? '-',
                'objective' => $soap->objective ?? '-',
                'assessment' => $soap->assessment ?? '-',
                'planning' => $soap->planning ?? '-',
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
        
        $this->kesadaranList = [
            ['value' => 'Compos Mentis', 'label' => 'Compos Mentis', 'icon' => 'ri-checkbox-circle-line text-green-500'],
            ['value' => 'Somnolence', 'label' => 'Somnolence', 'icon' => 'ri-eye-close-line text-yellow-500'],
            ['value' => 'Sopor', 'label' => 'Sopor', 'icon' => 'ri-eye-close-line text-orange-500'],
            ['value' => 'Coma', 'label' => 'Coma', 'icon' => 'ri-close-circle-line text-red-500'],
        ];

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

        return <<<'HTML'
        <div x-data="{ 
            activeTab: 'soap',
            mainTab: 'pemeriksaan',
            medicalTab: 'diagnosis',
            searchDiag: '',
            searchTind: '',
            searchObat: '',
            searchBmhp: ''
        }">
            
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-exchange-funds-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Transaksi Layanan Dokter</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Halaman pemeriksaan klinis dan tindakan medis oleh dokter.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Transaksi</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Layanan Dokter</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar Left: Filtering & Patient List -->
                <div class="space-y-6">
                    <!-- Card: Filter Data -->
                    <div class="card shadow-sm border-t-2 border-[#405189]" style="overflow: visible !important;">
                        <div class="p-4 border-b border-[#eff2f7]">
                            <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0"><i class="ri-filter-3-line mr-1"></i>Filter Data</h6>
                        </div>
                        <div class="p-4">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tanggal Kunjungan</label>
                            <div class="flex items-center gap-2">
                                <button wire:click="prevDate" class="flex h-[42px] w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-[#405189] hover:text-[#405189] hover:bg-indigo-50 transition-all group" title="Hari Sebelumnya">
                                    <i class="ri-arrow-left-s-line text-xl group-hover:scale-110 transition-transform"></i>
                                </button>
                                
                                <div class="relative flex-grow">
                                    <input type="date" wire:model.live="selectedDate" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all text-center font-bold text-[#405189] appearance-none cursor-pointer">
                                </div>

                                <button wire:click="nextDate" class="flex h-[42px] w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-[#405189] hover:text-[#405189] hover:bg-indigo-50 transition-all group" title="Hari Berikutnya">
                                    <i class="ri-arrow-right-s-line text-xl group-hover:scale-110 transition-transform"></i>
                                </button>
                            </div>
                            <div class="mt-3 text-center p-2 bg-indigo-50/50 rounded-xl border border-indigo-100/50">
                                <p class="text-[10px] text-[#878a99] font-bold uppercase tracking-widest mb-0.5">Tanggal Terpilih</p>
                                <p class="font-black text-[#405189] text-xs">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
                            </div>
                            
                            <div class="border-t border-[#eff2f7] pt-4 mt-2">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Poli Tujuan</label>
                                <x-custom-dropdown model="selectedPoli" :options="$this->poliListOptions" placeholder="Pilih Poli" searchable="true" live="true" />
                            </div>
                        </div>
                    </div>

                    <!-- Patient List Card -->
                    <div class="card shadow-sm overflow-hidden flex-1 border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                            <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0 flex items-center gap-2">
                                <i class="ri-group-line"></i> Daftar Pasien
                            </h6>
                            <span class="badge bg-[#405189] text-white rounded-full px-2 text-[10px]">{{ $this->pasienList->total() }}</span>
                        </div>
                        <div class="p-4 bg-white">
                            <div class="relative mb-3">
                                <input type="text" wire:model.live.debounce.300ms="searchPasien" class="form-control text-xs h-9 pl-8 border-gray-200 rounded-lg w-full focus:border-[#405189] transition-all" placeholder="Cari nama/RM...">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <div class="max-h-[600px] overflow-y-auto space-y-2 p-1">
                                @forelse($this->pasienList as $item)
                                    <button wire:click="selectPendaftaran({{ $item->id }})" 
                                        class="w-full text-left p-3 rounded-xl border transition-all duration-200 group {{ $selectedPendaftaranId == $item->id ? 'bg-[#405189] border-[#405189] shadow-md ring-2 ring-[#405189]/20' : 'bg-white border-gray-100 hover:border-[#405189] hover:bg-gray-50 shadow-sm' }}">
                                        <div class="flex flex-col gap-1">
                                            <h6 class="text-sm font-black m-0 {{ $selectedPendaftaranId == $item->id ? 'text-white' : 'text-[#495057]' }}">
                                                {{ $item->pasien?->nama_pasien ?? '-' }}
                                            </h6>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-lg {{ $selectedPendaftaranId == $item->id ? 'bg-white/20 text-white' : 'bg-[#405189]/10 text-[#405189]' }}">
                                                    Visit #{{ $item->nomor_kunjungan }}
                                                </span>
                                            </div>
                                        </div>
                                    </button>
                                @empty
                                    <div class="text-center py-10 opacity-40">
                                        <i class="ri-user-search-line text-4xl block mb-2"></i>
                                        <p class="text-xs font-bold">Pasien tidak ditemukan</p>
                                    </div>
                                @endforelse
                            </div>

                            @if($this->pasienList->hasPages())
                            <div class="mt-4 pt-4 border-t border-gray-100 pagination-sidebar">
                                {{ $this->pasienList->links() }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Transaction Forms -->
                <div class="lg:col-span-3 space-y-6">
                    @if($selectedPendaftaranId)
                        <!-- Patient Bio Card (Modern & Elegant) -->
                        <div class="card shadow-md border-0 rounded-3xl overflow-hidden bg-white group hover:shadow-xl transition-all duration-500">
                            <div class="flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-gray-100">
                                <!-- Main Info Section -->
                                <div class="p-6 md:p-8 flex-1 bg-gradient-to-br from-white to-gray-50/50">
                                    <div class="flex items-start gap-5">
                                        <div class="h-20 w-20 rounded-3xl bg-gradient-to-tr from-[#405189] to-[#299cdb] flex items-center justify-center text-3xl font-black text-white shadow-lg shadow-blue-200 transform group-hover:scale-105 transition-transform duration-500">
                                            {{ strtoupper(substr($selectedPendaftaran->pasien->nama_pasien ?? 'P', 0, 1)) }}
                                        </div>
                                        <div class="flex-1 space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h2 class="text-2xl font-black text-[#405189] tracking-tight leading-tight m-0">{{ $selectedPendaftaran->pasien->nama_pasien }}</h2>
                                                <span class="px-3 py-1 rounded-full bg-[#0ab39c]/10 text-[#0ab39c] text-[9px] font-black uppercase tracking-widest border border-[#0ab39c]/20">
                                                    {{ $selectedPendaftaran->nomor_kunjungan }}
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3 text-gray-500">
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-barcode-line text-[#405189]"></i> RM: {{ $selectedPendaftaran->pasien->no_rm }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-fingerprint-line text-[#405189]"></i> NIK: {{ $selectedPendaftaran->pasien->nik ?? '-' }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-shield-user-line text-[#405189]"></i> {{ $selectedPendaftaran->asuransi->nama_asuransi ?? 'UMUM / PRIBADI' }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-hospital-line text-[#405189]"></i> {{ $selectedPendaftaran->poli->nama_poli }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-heart-pulse-line text-[#405189]"></i> Agama: {{ $selectedPendaftaran->pasien->agama ?? '-' }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-phone-line text-[#405189]"></i> Telp: {{ $selectedPendaftaran->pasien->no_telepon ?? '-' }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-map-pin-line text-[#405189]"></i> Alamat : {{ $selectedPendaftaran->pasien->alamat ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Personal Specs Section -->
                                <div class="p-6 bg-gray-50/30 md:w-80 flex flex-col justify-center gap-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-0.5">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Umur / Tgl Lahir</p>
                                            <p class="text-xs font-black text-[#405189] m-0">{{ \Carbon\Carbon::parse($selectedPendaftaran->pasien->tanggal_lahir)->age }} Thn ({{ \Carbon\Carbon::parse($selectedPendaftaran->pasien->tanggal_lahir)->format('d/m/Y') }})</p>
                                        </div>
                                        <div class="space-y-0.5">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Jenis Kelamin</p>
                                            <p class="text-xs font-black text-[#405189] m-0 font-mono">{{ $selectedPendaftaran->pasien->jenis_kelamin == 'L' ? 'LAKI-LAKI' : 'PEREMPUAN' }}</p>
                                        </div>
                                        <div class="col-span-2 space-y-0.5">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Nama Dokter</p>
                                            <p class="text-xs font-black text-[#405189] m-0 flex items-center gap-1.5"><i class="ri-user-star-line text-amber-500"></i> {{ $selectedPendaftaran->dokter->nama_dokter ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ MAIN TAB NAVIGATION ═══ -->
                        <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-2xl w-fit mt-2">
                            <button @click="mainTab = 'pemeriksaan'" 
                                    :class="mainTab === 'pemeriksaan' ? 'bg-white text-[#405189] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-5 py-2.5 rounded-xl text-sm font-black transition-all duration-200 flex items-center gap-2">
                                <i class="ri-stethoscope-line"></i> <span class="hidden sm:inline">Pemeriksaan</span>
                            </button>
                            <button @click="mainTab = 'screening'" 
                                    :class="mainTab === 'screening' ? 'bg-white text-[#405189] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-5 py-2.5 rounded-xl text-sm font-black transition-all duration-200 flex items-center gap-2">
                                <i class="ri-survey-line"></i> <span class="hidden sm:inline">Screening</span>
                            </button>
                            <button @click="mainTab = 'penunjang'" 
                                    :class="mainTab === 'penunjang' ? 'bg-white text-[#405189] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-5 py-2.5 rounded-xl text-sm font-black transition-all duration-200 flex items-center gap-2">
                                <i class="ri-file-search-line"></i> <span class="hidden sm:inline">Penunjang</span>
                            </button>
                            <button @click="mainTab = 'riwayat'" 
                                    :class="mainTab === 'riwayat' ? 'bg-white text-[#405189] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-5 py-2.5 rounded-xl text-sm font-black transition-all duration-200 flex items-center gap-2">
                                <i class="ri-history-line"></i> <span class="hidden sm:inline">Riwayat</span>
                            </button>
                        </div>

                        <!-- ═══ TAB CONTENT: PEMERIKSAAN ═══ -->
                        <div x-show="mainTab === 'pemeriksaan'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Card: Anamnesis & SOAP -->
                            <div class="card shadow-sm border-t-4 border-[#405189] md:col-span-2 relative z-50" style="overflow: visible !important;">
                                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                    <h6 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-0">
                                        <i class="ri-mental-health-line mr-1"></i> Data Anamnesis & Pemeriksaan
                                    </h6>
                                </div>
                                <div class="p-8 space-y-12">
                                    <!-- Subsection: Pemeriksaan Awal (Vitals) -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="h-8 w-8 rounded-lg bg-blue-50 text-[#405189] flex items-center justify-center shadow-sm">
                                                <i class="ri-pulse-line text-lg"></i>
                                            </div>
                                            <h6 class="text-xs font-black text-gray-700 uppercase tracking-widest m-0">Pemeriksaan Awal (Vitals)</h6>
                                            <div class="flex-1 h-px bg-gray-100"></div>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                                            <div class="form-group col-span-2 lg:col-span-1">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Tingkat Kesadaran</label>
                                                <x-custom-dropdown model="kesadaran" :options="$this->kesadaranList" placeholder="Pilih..." />
                                            </div>
                                            <div class="form-group">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Tekanan Darah</label>
                                                <div class="relative">
                                                    <input type="text" wire:model.defer="tekanan_darah" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all pr-12 font-bold" placeholder="120/80">
                                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-gray-400">mmHg</span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Nadi</label>
                                                <div class="relative">
                                                    <input type="text" wire:model.defer="nadi" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all pr-10 font-bold" placeholder="80">
                                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-gray-400">bpm</span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Suhu</label>
                                                <div class="relative">
                                                    <input type="text" wire:model.defer="suhu" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all pr-10 font-bold" placeholder="36.5">
                                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-gray-400">°C</span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Berat Badan</label>
                                                <div class="relative">
                                                    <input type="text" wire:model.defer="berat_badan" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all pr-10 font-bold" placeholder="60">
                                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-gray-400">kg</span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Tinggi Badan</label>
                                                <div class="relative">
                                                    <input type="text" wire:model.defer="tinggi_badan" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all pr-10 font-bold" placeholder="170">
                                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-gray-400">cm</span>
                                                </div>
                                            </div>
                                            <div class="form-group md:col-span-1 lg:col-span-2 lg:col-start-1">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Alergi</label>
                                                <textarea wire:model.defer="alergi" rows="2" class="w-full rounded-lg border border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all font-bold" placeholder="Alergi"></textarea>
                                            </div>
                                            <div class="form-group md:col-span-1 lg:col-span-2">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Riwayat Penyakit</label>
                                                <textarea wire:model.defer="riwayat_penyakit" rows="2" class="w-full rounded-lg border border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all font-bold" placeholder="Riwayat Penyakit"></textarea>
                                            </div>
                                            <div class="form-group col-span-2 md:col-span-3 lg:col-span-4">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5 uppercase">Keterangan Lain</label>
                                                <textarea wire:model.defer="keterangan_lain" rows="2" class="w-full rounded-lg border border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all font-bold" placeholder="Informasi medis tambahan..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Subsection: SOAP (Clinical Notes) -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center shadow-sm">
                                                <i class="ri-edit-2-line text-lg"></i>
                                            </div>
                                            <h6 class="text-xs font-black text-gray-700 uppercase tracking-widest m-0">Clinical Notes (SOAP)</h6>
                                            <div class="flex-1 h-px bg-gray-100"></div>
                                        </div>
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                            <div class="form-group">
                                                <div class="flex justify-between items-center mb-2">
                                                    <label class="block text-[11px] font-black text-[#405189] uppercase tracking-widest m-0">Subyektif (S)</label>
                                                    <span class="text-[9px] font-bold text-gray-400">Keluhan Pasien</span>
                                                </div>
                                                <textarea wire:model.defer="subyektif" rows="5" class="w-full rounded-2xl border border-gray-200 text-sm focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/10 transition-all p-5 bg-gray-50/30 font-medium" placeholder="Tuliskan keluhan utama..."></textarea>
                                            </div>
                                            <div class="form-group">
                                                <div class="flex justify-between items-center mb-2">
                                                    <label class="block text-[11px] font-black text-[#405189] uppercase tracking-widest m-0">Obyektif (O)</label>
                                                    <span class="text-[9px] font-bold text-gray-400">Hasil Pemeriksaan</span>
                                                </div>
                                                <textarea wire:model.defer="obyektif" rows="5" class="w-full rounded-2xl border border-gray-200 text-sm focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/10 transition-all p-5 bg-gray-50/30 font-medium" placeholder="Tuliskan hasil pemeriksaan..."></textarea>
                                            </div>
                                            <div class="form-group">
                                                <div class="flex justify-between items-center mb-2">
                                                    <label class="block text-[11px] font-black text-[#405189] uppercase tracking-widest m-0">Assessment (A)</label>
                                                    <span class="text-[9px] font-bold text-gray-400">Diagnosa / Analisa</span>
                                                </div>
                                                <textarea wire:model.defer="assessment" rows="5" class="w-full rounded-2xl border border-gray-200 text-sm focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/10 transition-all p-5 bg-gray-50/30 font-medium" placeholder="Analisa temuan..."></textarea>
                                            </div>
                                            <div class="form-group">
                                                <div class="flex justify-between items-center mb-2">
                                                    <label class="block text-[11px] font-black text-[#405189] uppercase tracking-widest m-0">Planning (P)</label>
                                                    <span class="text-[9px] font-bold text-gray-400">Rencana Terapi</span>
                                                </div>
                                                <textarea wire:model.defer="planning" rows="5" class="w-full rounded-2xl border border-gray-200 text-sm focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/10 transition-all p-5 bg-gray-50/30 font-medium" placeholder="Tuliskan rencana tindakan..."></textarea>
                                            </div>
                                            <div class="form-group lg:col-span-2">
                                                <div class="flex justify-between items-center mb-2">
                                                    <label class="block text-[11px] font-black text-emerald-600 uppercase tracking-widest m-0">Rekomendasi Diet</label>
                                                    <span class="text-[9px] font-bold text-gray-400">Nutrisi & Edukasi</span>
                                                </div>
                                                <textarea wire:model.defer="rekomendasi_diet" rows="3" class="w-full rounded-2xl border border-emerald-100 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all p-5 bg-emerald-50/30 font-medium text-emerald-900 placeholder:text-emerald-300" placeholder="Tuliskan rekomendasi diet atau anjuran nutrisi untuk pasien..."></textarea>
                                            </div>
                                        </div>
                                        <div class="flex justify-end pt-4 mt-6 border-t border-gray-100">
                                            <button type="button" wire:click="savePemeriksaan" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                                                <svg wire:loading wire:target="savePemeriksaan" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <i wire:loading.remove wire:target="savePemeriksaan" class="ri-save-line"></i>
                                                <span wire:loading.remove wire:target="savePemeriksaan">Simpan Pemeriksaan</span>
                                                <span wire:loading wire:target="savePemeriksaan">Memproses...</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- UNIFIED MEDICAL TABS CARD -->
                            <div class="card shadow-sm overflow-hidden border-t-2 border-[#405189] md:col-span-2">
                                <div class="p-4 border-b border-[#eff2f7]">
                                    <div class="flex overflow-x-auto scrollbar-hide">
                                        <ul class="nav-pills-custom">
                                            <li class="nav-item">
                                                <a class="nav-link" :class="medicalTab === 'diagnosis' ? 'active active-pill-warning' : ''" @click="medicalTab = 'diagnosis'" role="button">
                                                    <i class="ri-microscope-line"></i>
                                                    <span>Diagnosis</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" :class="medicalTab === 'tindakan' ? 'active active-pill-primary' : ''" @click="medicalTab = 'tindakan'" role="button">
                                                    <i class="ri-hand-heart-line"></i>
                                                    <span>Tindakan Medis</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" :class="medicalTab === 'resep' ? 'active active-pill-success' : ''" @click="medicalTab = 'resep'" role="button">
                                                    <i class="ri-capsule-line"></i>
                                                    <span>Peresepan Obat</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" :class="medicalTab === 'bmhp' ? 'active active-pill-primary' : ''" @click="medicalTab = 'bmhp'" role="button">
                                                    <i class="ri-flask-line"></i>
                                                    <span>BMHP</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <!-- TAB: Diagnosis -->
                                    <div x-show="medicalTab === 'diagnosis'" x-cloak class="p-4" x-transition>
                                        <div class="mb-5">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-orange-50 rounded-lg">
                                                        <i class="ri-microscope-line text-orange-500 text-xl"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="text-base font-bold text-[#405189] mb-0">Diagnosis Pasien</h6>
                                                        <p class="text-[11px] text-gray-500 mb-0">Kelola daftar ICD-10 untuk kunjungan ini</p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" x-model="searchDiag" class="h-10 w-full sm:w-64 rounded-xl border border-gray-200 pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all placeholder:text-gray-400 bg-gray-50/50" placeholder="Cari diagnosis...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                                                    </div>
                                                    <button @click="$wire.set('showDiagnosisModal', true)" class="btn btn-primary text-white h-10 px-5 rounded-xl shadow-md flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto border-0">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="border-b border-dashed border-gray-200 mb-5"></div>

                                        <!-- Premium Card Table Replacement -->
                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($diagnoses as $idx => $diag)
                                                <div wire:key="diag-card-{{ $diag['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($diag['nama']) }} {{ strtolower($diag['kode']) }}"
                                                     x-show="searchDiag === '' || $el.dataset.search.includes(searchDiag.toLowerCase())" 
                                                     class="group relative flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <!-- Index / Number -->
                                                    <div class="flex-none w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-[10px] sm:text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <!-- Diagnosis Data -->
                                                    <div class="flex-grow min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                                            <span class="text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors line-clamp-1">{{ $diag['nama'] }}</span>
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-orange-100 text-orange-700 text-[10px] font-bold tracking-wider uppercase">{{ $diag['kode'] }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex items-center gap-1.5">
                                                                <div class="w-1.5 h-1.5 rounded-full {{ ($diag['jenis_icd'] ?? '') === 'Utama' ? 'bg-indigo-500' : 'bg-gray-300' }}"></div>
                                                                <span class="text-[11px] font-medium text-gray-500 uppercase tracking-widest">{{ $diag['jenis_icd'] ?? 'Sekunder' }}</span>
                                                            </div>
                                                            <div class="w-1 h-1 rounded-full bg-gray-200"></div>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="text-[11px] font-medium text-gray-500">Kasus:</span>
                                                                <span class="px-2 py-0.5 rounded-full {{ ($diag['kasus_icd'] ?? '') === 'Baru' ? 'bg-emerald-50 text-emerald-600' : 'bg-purple-50 text-purple-600' }} text-[9px] font-bold uppercase tracking-wider">{{ $diag['kasus_icd'] ?? '-' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Actions -->
                                                    <div class="flex-none flex items-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-all transform sm:translate-x-2 group-hover:translate-x-0">
                                                        <button @click="
                                                            Swal.fire({
                                                                title: 'Konfirmasi Hapus',
                                                                text: 'Apakah Anda yakin ingin menghapus diagnosis ini?',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#f06548',
                                                                cancelButtonColor: '#6c757d',
                                                                confirmButtonText: 'Ya, Hapus!',
                                                                cancelButtonText: 'Batal',
                                                                reverseButtons: true
                                                                }).then((result) => {
                                                                    if (result.isConfirmed) {
                                                                        $wire.removeItem('diagnoses', {{ $idx }})
                                                                    }
                                                                })
                                                            " class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-base sm:text-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div wire:key="empty-diag-{{ $selectedPendaftaran->id ?? 'none' }}" class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                                                    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                        <i class="ri-microscope-line text-3xl text-gray-300"></i>
                                                    </div>
                                                    <h3 class="text-sm font-bold text-gray-500 mb-1">Belum Ada Diagnosis</h3>
                                                    <p class="text-xs text-gray-400 text-center max-w-[200px]">Silakan klik tombol "Tambah Diagnosis" untuk mulai menginput data.</p>
                                                </div>
                                            @endforelse

                                            <!-- Empty Search Results: Diagnosis -->
                                            <div x-show="searchDiag !== '' && !Array.from($el.parentElement.querySelectorAll('.group')).some(c => c.dataset.search.includes(searchDiag.toLowerCase()))" 
                                                 class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200"
                                                 x-cloak>
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                    <i class="ri-search-2-line text-3xl text-gray-300 transition-all"></i>
                                                </div>
                                                <h3 class="text-sm font-bold text-gray-500 mb-1">Data Tidak Ditemukan</h3>
                                                <p class="text-xs text-gray-400 text-center max-w-[200px]">Tidak ada diagnosis yang cocok dengan kata kunci "<span x-text="searchDiag" class="font-bold text-[#405189]"></span>"</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: Tindakan Medis -->
                                    <div x-show="medicalTab === 'tindakan'" x-cloak class="p-4" x-transition>
                                        <div class="mb-5">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-blue-50 rounded-lg">
                                                        <i class="ri-hand-heart-line text-blue-500 text-xl"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="text-base font-bold text-[#405189] mb-0">Tindakan Medis</h6>
                                                        <p class="text-[11px] text-gray-500 mb-0">Layanan dan tindakan yang diberikan kepada pasien</p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" x-model="searchTind" class="h-10 w-full sm:w-64 rounded-xl border border-gray-200 pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all placeholder:text-gray-400 bg-gray-50/50" placeholder="Cari tindakan / layanan...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                                                    </div>
                                                    <button wire:click="addTindakan" class="btn btn-primary text-white h-10 px-5 rounded-xl shadow-md flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto border-0">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="border-b border-dashed border-gray-200 mb-5"></div>

                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($tindakans as $idx => $tdk)
                                                <div wire:key="tindakan-card-{{ $tdk['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($tdk['nama'] ?? '') }}"
                                                     x-show="searchTind === '' || $el.dataset.search.includes(searchTind.toLowerCase())" 
                                                     class="group relative flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <div class="flex-none w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-[10px] sm:text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <div class="flex-grow min-w-0">
                                                        <span class="text-xs sm:text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors block mb-1 truncate">{{ $tdk['nama'] }}</span>
                                                        <div class="flex flex-wrap items-center gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Biaya:</span>
                                                                <span class="text-xs font-black text-[#405189]">Rp {{ number_format($tdk['biaya'], 0, ',', '.') }}</span>
                                                            </div>
                                                            <div class="w-1 h-1 rounded-full bg-gray-200"></div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">BHP:</span>
                                                                <span class="text-xs font-bold text-orange-600">Rp {{ number_format($tdk['bhp'] ?? 0, 0, ',', '.') }}</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex-none flex items-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-all transform sm:translate-x-2 group-hover:translate-x-0">
                                                        <button @click="
                                                            Swal.fire({
                                                                title: 'Konfirmasi Hapus',
                                                                text: 'Apakah Anda yakin ingin menghapus tindakan ini?',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#f06548',
                                                                cancelButtonColor: '#6c757d',
                                                                confirmButtonText: 'Ya, Hapus!',
                                                                cancelButtonText: 'Batal',
                                                                reverseButtons: true
                                                                }).then((result) => {
                                                                    if (result.isConfirmed) {
                                                                        $wire.removeItem('tindakans', {{ $idx }})
                                                                    }
                                                                })
                                                            " class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-base sm:text-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div wire:key="empty-tindakan-{{ $selectedPendaftaran->id ?? 'none' }}" class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                                                    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                        <i class="ri-hand-heart-line text-3xl text-gray-300"></i>
                                                    </div>
                                                    <h3 class="text-sm font-bold text-gray-500 mb-1">Belum Ada Tindakan</h3>
                                                    <p class="text-xs text-gray-400 text-center max-w-[200px]">Silakan masukkan nama tindakan pada kolom pencarian di atas lalu tekan Enter atau klik tombol Tambah.</p>
                                                </div>
                                            @endforelse

                                            <!-- Empty Search Results: Tindakan -->
                                            <div x-show="searchTind !== '' && !Array.from($el.parentElement.querySelectorAll('.group')).some(c => c.dataset.search.includes(searchTind.toLowerCase()))" 
                                                 class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200"
                                                 x-cloak>
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                    <i class="ri-search-2-line text-3xl text-gray-300 transition-all"></i>
                                                </div>
                                                <h3 class="text-sm font-bold text-gray-500 mb-1">Data Tidak Ditemukan</h3>
                                                <p class="text-xs text-gray-400 text-center max-w-[200px]">Tidak ada tindakan yang cocok dengan kata kunci "<span x-text="searchTind" class="font-bold text-[#405189]"></span>"</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: Peresepan Obat -->
                                    <div x-show="medicalTab === 'resep'" x-cloak class="p-4" x-transition>
                                        <div class="mb-5">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-emerald-50 rounded-lg">
                                                        <i class="ri-capsule-line text-emerald-500 text-xl"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="text-base font-bold text-[#405189] mb-0">Peresepan Obat</h6>
                                                        <p class="text-[11px] text-gray-500 mb-0">Input daftar resep obat untuk pasien</p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" x-model="searchObat" class="h-10 w-full sm:w-64 rounded-xl border border-gray-200 pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all placeholder:text-gray-400 bg-gray-50/50" placeholder="Cari resep obat...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                                                    </div>
                                                    <button wire:click="addResep" class="btn btn-primary text-white h-10 px-5 rounded-xl shadow-md flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto border-0">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="border-b border-dashed border-gray-200 mb-5"></div>

                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($reseps as $idx => $rsp)
                                                <div wire:key="resep-card-{{ $rsp['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($rsp['nama'] ?? '') }}"
                                                     x-show="searchObat === '' || $el.dataset.search.includes(searchObat.toLowerCase())" 
                                                     class="group relative flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <div class="flex-none w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-[10px] sm:text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <div class="flex-grow min-w-0">
                                                        <div class="flex items-center gap-3 mb-1">
                                                            <span class="text-xs sm:text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors truncate">{{ $rsp['nama'] }}</span>
                                                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600 text-[10px] font-black border border-emerald-100 italic"> Dosis : {{ $rsp['qty'] }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="ri-information-line text-gray-400 text-xs"></i>
                                                            <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Aturan pakai : {{ $rsp['signa'] }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex-none flex items-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-all transform sm:translate-x-2 group-hover:translate-x-0">
                                                        <button @click="
                                                            Swal.fire({
                                                                title: 'Konfirmasi Hapus',
                                                                text: 'Apakah Anda yakin ingin menghapus resep ini?',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#f06548',
                                                                cancelButtonColor: '#6c757d',
                                                                confirmButtonText: 'Ya, Hapus!',
                                                                cancelButtonText: 'Batal',
                                                                reverseButtons: true
                                                                }).then((result) => {
                                                                    if (result.isConfirmed) {
                                                                        $wire.removeItem('reseps', {{ $idx }})
                                                                    }
                                                                })
                                                            " class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-base sm:text-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div wire:key="empty-resep-{{ $selectedPendaftaran->id ?? 'none' }}" class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                                                    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                        <i class="ri-capsule-line text-3xl text-gray-300"></i>
                                                    </div>
                                                    <h3 class="text-sm font-bold text-gray-500 mb-1">Belum Ada Resep</h3>
                                                    <p class="text-xs text-gray-400 text-center max-w-[200px]">Silakan masukkan data resep obat pada isian di atas.</p>
                                                </div>
                                            @endforelse

                                            <!-- Empty Search Results: Resep -->
                                            <div x-show="searchObat !== '' && !Array.from($el.parentElement.querySelectorAll('.group')).some(c => c.dataset.search.includes(searchObat.toLowerCase()))" 
                                                 class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200"
                                                 x-cloak>
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                    <i class="ri-search-2-line text-3xl text-gray-300 transition-all"></i>
                                                </div>
                                                <h3 class="text-sm font-bold text-gray-500 mb-1">Data Tidak Ditemukan</h3>
                                                <p class="text-xs text-gray-400 text-center max-w-[200px]">Tidak ada resep yang cocok dengan kata kunci "<span x-text="searchObat" class="font-bold text-[#405189]"></span>"</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: BMHP -->
                                    <div x-show="medicalTab === 'bmhp'" x-cloak class="p-4" x-transition>
                                        <div class="mb-5">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-purple-50 rounded-lg">
                                                        <i class="ri-flask-line text-purple-500 text-xl"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="text-base font-bold text-[#405189] mb-0">Bahan Medis (BMHP)</h6>
                                                        <p class="text-[11px] text-gray-500 mb-0">Daftar penggunaan BMHP / Alkes</p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" x-model="searchBmhp" class="h-10 w-full sm:w-64 rounded-xl border border-gray-200 pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all placeholder:text-gray-400 bg-gray-50/50" placeholder="Cari BMHP / alkes...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                                                    </div>
                                                    <button wire:click="addBmhp" class="btn btn-primary text-white h-10 px-5 rounded-xl shadow-md flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto border-0">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="border-b border-dashed border-gray-200 mb-5"></div>

                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($bmhps as $idx => $bm)
                                                <div wire:key="bmhp-card-{{ $bm['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($bm['nama'] ?? '') }}"
                                                     x-show="searchBmhp === '' || $el.dataset.search.includes(searchBmhp.toLowerCase())" 
                                                     class="group relative flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <div class="flex-none w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-[10px] sm:text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <div class="flex-grow min-w-0">
                                                        <div class="text-xs sm:text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors mb-0.5 truncate">
                                                            {{ $bm['nama'] }}
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="ri-stack-line text-gray-400 text-[10px]"></i>
                                                            <span class="text-[11px] font-bold text-purple-600 uppercase tracking-wider italic">Jumlah: {{ $bm['jumlah'] }} {{ $bm['satuan'] }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex-none flex items-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-all transform sm:translate-x-2 group-hover:translate-x-0">
                                                        <button @click="
                                                            Swal.fire({
                                                                title: 'Konfirmasi Hapus',
                                                                text: 'Apakah Anda yakin ingin menghapus BMHP ini?',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#f06548',
                                                                cancelButtonColor: '#6c757d',
                                                                confirmButtonText: 'Ya, Hapus!',
                                                                cancelButtonText: 'Batal',
                                                                reverseButtons: true
                                                                }).then((result) => {
                                                                    if (result.isConfirmed) {
                                                                        $wire.removeItem('bmhps', {{ $idx }})
                                                                    }
                                                                })
                                                            " class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-base sm:text-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div wire:key="empty-bmhp-{{ $selectedPendaftaran->id ?? 'none' }}" class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                                                    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                        <i class="ri-flask-line text-3xl text-gray-300"></i>
                                                    </div>
                                                    <h3 class="text-sm font-bold text-gray-500 mb-1">Belum Ada BMHP</h3>
                                                    <p class="text-xs text-gray-400 text-center max-w-[200px]">Silakan masukkan data BMHP pada isian di atas.</p>
                                                </div>
                                            @endforelse

                                            <!-- Empty Search Results: BMHP -->
                                            <div x-show="searchBmhp !== '' && !Array.from($el.parentElement.querySelectorAll('.group')).some(c => c.dataset.search.includes(searchBmhp.toLowerCase()))" 
                                                 class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200"
                                                 x-cloak>
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                    <i class="ri-search-2-line text-3xl text-gray-300 transition-all"></i>
                                                </div>
                                                <h3 class="text-sm font-bold text-gray-500 mb-1">Data Tidak Ditemukan</h3>
                                                <p class="text-xs text-gray-400 text-center max-w-[200px]">Tidak ada BMHP yang cocok dengan kata kunci "<span x-text="searchBmhp" class="font-bold text-[#405189]"></span>"</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($selectedPendaftaran && $selectedPendaftaran->poli && $selectedPendaftaran->poli->kode_poli === '002')
                        <!-- Odontogram Card -->
                        <div class="card shadow-sm border-t-4 border-indigo-500 relative z-10 mt-4" style="border-color: #0a31b3ff;">
                            <div class="rounded-t-xl" style="overflow-x: auto; overflow-y: visible;">
                                <div class="p-4 border-b border-gray-100 flex flex-wrap gap-2 justify-between items-center bg-gray-50/50" style="background: linear-gradient(to right, #f0f4fdff, #ffffff); min-width: 820px;">
                                <h6 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-0">
                                    <i class="ri-mastodon-line mr-1"></i> Odontogram Gigi
                                </h6>
                                <div class="text-xs font-bold text-gray-400 flex items-center gap-1.5"><i class="ri-mouse-line"></i> Klik / Klik kanan pada gigi</div>
                            </div>
                            <!-- (commented out block already exists below) -->
                            
                            <div class="p-2 sm:p-4 lg:p-6 bg-white" 
                                 x-data="odontogramStore(@entangle('odontogramState'))">
                                <div style="min-width: 800px;" class="flex flex-col gap-8 select-none pb-12 px-20">
                                    
                                    <!-- Adult Top Row -->
                                    <div class="flex justify-center gap-8">
                                        <!-- 18-11 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [18,17,16,15,14,13,12,11]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1.5 hover:scale-110 transition-transform">
                                                    <span class="text-[11px] font-black text-gray-400" x-text="tooth"></span>
                                                    <div class="w-10 h-10 lg:w-11 lg:h-11 drop-shadow-sm">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                        <!-- 21-28 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [21,22,23,24,25,26,27,28]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1.5 hover:scale-110 transition-transform">
                                                    <span class="text-[11px] font-black text-gray-400" x-text="tooth"></span>
                                                    <div class="w-10 h-10 lg:w-11 lg:h-11 drop-shadow-sm">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                    </div>

                                    <!-- Child Top Row -->
                                    <div class="flex justify-center gap-6 px-4 lg:px-16 mx-auto">
                                        <!-- 55-51 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [55,54,53,52,51]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1 hover:scale-110 transition-transform">
                                                    <span class="text-[9px] font-black text-gray-300" x-text="tooth"></span>
                                                    <div class="w-8 h-8 lg:w-9 lg:h-9 opacity-80">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                        <!-- 61-65 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [61,62,63,64,65]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1 hover:scale-110 transition-transform">
                                                    <span class="text-[9px] font-black text-gray-300" x-text="tooth"></span>
                                                    <div class="w-8 h-8 lg:w-9 lg:h-9 opacity-80">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                    </div>

                                     <!-- Child Bot Row -->
                                    <div class="flex justify-center gap-6 px-4 lg:px-16 mx-auto mt-2">
                                        <!-- 85-81 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [85,84,83,82,81]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1 hover:scale-110 transition-transform flex-col-reverse">
                                                    <span class="text-[9px] font-black text-gray-300" x-text="tooth"></span>
                                                    <div class="w-8 h-8 lg:w-9 lg:h-9 opacity-80">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                        <!-- 71-75 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [71,72,73,74,75]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1 hover:scale-110 transition-transform flex-col-reverse">
                                                    <span class="text-[9px] font-black text-gray-300" x-text="tooth"></span>
                                                    <div class="w-8 h-8 lg:w-9 lg:h-9 opacity-80">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                    </div>

                                    <!-- Adult Bot Row -->
                                    <div class="flex justify-center gap-8 mt-2">
                                        <!-- 48-41 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [48,47,46,45,44,43,42,41]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1.5 hover:scale-110 transition-transform flex-col-reverse">
                                                    <span class="text-[11px] font-black text-gray-400" x-text="tooth"></span>
                                                    <div class="w-10 h-10 lg:w-11 lg:h-11 drop-shadow-sm">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                        <!-- 31-38 -->
                                        <div class="flex gap-2 lg:gap-2.5">
                                           <template x-for="tooth in [31,32,33,34,35,36,37,38]" :key="tooth">
                                                <div class="flex flex-col items-center gap-1.5 hover:scale-110 transition-transform flex-col-reverse">
                                                    <span class="text-[11px] font-black text-gray-400" x-text="tooth"></span>
                                                    <div class="w-10 h-10 lg:w-11 lg:h-11 drop-shadow-sm">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path @click="openMenu($event, tooth, 'T')" @contextmenu.prevent="openMenu($event, tooth, 'T')" :fill="state[tooth + '-T']?.color || 'white'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'R')" @contextmenu.prevent="openMenu($event, tooth, 'R')" :fill="state[tooth + '-R']?.color || 'white'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'B')" @contextmenu.prevent="openMenu($event, tooth, 'B')" :fill="state[tooth + '-B']?.color || 'white'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'L')" @contextmenu.prevent="openMenu($event, tooth, 'L')" :fill="state[tooth + '-L']?.color || 'white'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                            <path @click="openMenu($event, tooth, 'C')" @contextmenu.prevent="openMenu($event, tooth, 'C')" :fill="state[tooth + '-C']?.color || 'white'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#cbd5e1" stroke-width="1.2" class="cursor-pointer transition-all hover:brightness-90"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                           </template>
                                        </div>
                                    </div>

                                </div>

                                <!-- Custom Context Menu for Odontogram -->
                                <div x-show="showMenu" @click.away="showMenu = false" x-transition.opacity
                                     class="fixed z-[9999] bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.3)] border border-gray-100 min-w-[240px] overflow-hidden flex flex-col"
                                     :style="{ top: menuY + 'px', left: menuX + 'px' }" style="display: none;" x-cloak>
                                     
                                     <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between" style="background: linear-gradient(to right, #eef2ff, #ffffff);">
                                        <div>
                                           <h6 class="text-xs font-black text-indigo-700 mb-0 flex items-center gap-1.5">
                                              <i class="ri-tooth-fill text-lg"></i> Gigi <span x-text="selectedTooth" class="text-black bg-white px-2 py-0.5 rounded shadow-sm border border-indigo-100"></span>
                                           </h6>
                                           <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-0 mt-1 flex items-center gap-1">
                                              <i class="ri-focus-3-line"></i> <span x-text="partLabelMap[selectedPart]" class="text-gray-600"></span>
                                           </p>
                                        </div>
                                     </div>
                                     
                                     <div class="p-2 max-h-[320px] overflow-y-auto space-y-1">
                                         <button @click.prevent="applyCategory(null, 'white')" class="w-full text-left px-3 py-2 rounded-xl text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors flex items-center gap-3 group">
                                             <div class="w-5 h-5 rounded border-2 border-dashed border-gray-300 group-hover:border-red-300 flex items-center justify-center bg-white shadow-sm">
                                                 <i class="ri-close-line text-xs text-gray-400 group-hover:text-red-500"></i>
                                             </div>
                                             <span class="font-bold text-xs uppercase tracking-wider">Reset Normal</span>
                                         </button>
                                         
                                         <div class="h-px w-full bg-gray-100 my-2"></div>
                                         
                                         @foreach($kategoriGigiOptions as $kat)
                                             <button @click.prevent="applyCategory('{{ $kat->kode_kategori }}', '{{ $kat->warna ?? '#333' }}')" 
                                                     class="w-full text-left px-3 py-2 rounded-xl text-sm hover:bg-gray-50/80 transition-colors flex items-center gap-3 group border border-transparent hover:border-gray-200">
                                                 <div class="w-5 h-5 rounded-md shadow-sm flex-none border border-black/10 transition-transform group-hover:scale-110 flex items-center justify-center relative overflow-hidden" style="background-color: {{ $kat->warna ?? '#ccc' }}">
                                                     <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity"></div>
                                                 </div>
                                                 <div class="flex-grow min-w-0 flex flex-col justify-center">
                                                     <span class="block font-bold text-xs text-gray-700 truncate group-hover:text-indigo-600 transition-colors leading-tight">{{ $kat->nama_kategori }}</span>
                                                     <span class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mt-0.5">{{ $kat->kode_kategori }}</span>
                                                 </div>
                                             </button>
                                         @endforeach
                                     </div>
                                </div>
                            </div>
                            </div> <!-- End Odontogram scroll wrapper -->

                            <!-- Odontogram Legend -->
                            <div class="mt-4 px-4 sm:px-5 lg:px-6 pb-6 border-t border-gray-100 pt-6">
                                <h6 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <i class="ri-information-fill text-indigo-500 text-sm"></i> Legenda Kondisi Gigi
                                </h6>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                                    <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-gray-50/50 border border-gray-100/50 shadow-sm hover:shadow-md hover:bg-white hover:border-indigo-100 transition-all duration-300 group">
                                        <div class="w-5 h-5 rounded-lg shadow-sm border border-black/5 flex-none transition-transform group-hover:scale-110 bg-white"></div>
                                        <div class="min-w-0">
                                            <span class="block text-[11px] font-bold text-gray-700 truncate leading-tight group-hover:text-indigo-600 transition-colors">Normal / Sehat</span>
                                        </div>
                                    </div>
                                    @foreach($kategoriGigiOptions as $kat)
                                        <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-gray-50/50 border border-gray-100/50 shadow-sm hover:shadow-md hover:bg-white hover:border-indigo-100 transition-all duration-300 group">
                                            <div class="w-5 h-5 rounded-lg shadow-sm border border-black/5 flex-none transition-transform group-hover:scale-110" style="background-color: {{ $kat->warna ?? '#ccc' }}"></div>
                                            <div class="min-w-0">
                                                <span class="block text-[11px] font-bold text-gray-700 truncate leading-tight group-hover:text-indigo-600 transition-colors">{{ $kat->nama_kategori }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Save Odontogram Button -->
                            <div class="flex justify-end pt-4 mt-4 border-t border-gray-100 px-4 sm:px-5 lg:px-6 pb-4 mb-4">
                                <button type="button" wire:click="saveOdontogram" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                                    <svg wire:loading wire:target="saveOdontogram" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <i wire:loading.remove wire:target="saveOdontogram" class="ri-save-line"></i>
                                    <span wire:loading.remove wire:target="saveOdontogram">Simpan Odontogram</span>
                                    <span wire:loading wire:target="saveOdontogram">Memproses...</span>
                                </button>
                            </div>
                        </div>
                        <!-- OHIS Card -->
                        <div class="card shadow-sm border-t-4 relative z-10 mt-4" style="border-color: #0ab39c;">
                            <div class="rounded-t-xl" style="overflow-x: auto; overflow-y: visible;">
                                <div class="p-4 border-b border-gray-100 flex flex-wrap gap-2 justify-between items-center" style="background: linear-gradient(to right, #f0fdf9, #ffffff); min-width: 600px;">
                                <h6 class="text-sm font-black uppercase tracking-widest mb-0 flex items-center gap-2" style="color: #0ab39c;">
                                    <i class="ri-stethoscope-line text-lg"></i> OHI-S (Oral Hygiene Index)
                                </h6>
                                <div class="text-xs font-bold text-gray-400 flex items-center gap-1.5"><i class="ri-information-line"></i> Skor 0-3 per gigi</div>
                            </div>
                            
                            <div class="p-4 sm:p-5 lg:p-6 bg-white" x-data="{
                                diScores: {
                                    16: $wire.entangle('ohis_di_16'),
                                    11: $wire.entangle('ohis_di_11'),
                                    26: $wire.entangle('ohis_di_26'),
                                    36: $wire.entangle('ohis_di_36'),
                                    31: $wire.entangle('ohis_di_31'),
                                    46: $wire.entangle('ohis_di_46'),
                                },
                                ciScores: {
                                    16: $wire.entangle('ohis_ci_16'),
                                    11: $wire.entangle('ohis_ci_11'),
                                    26: $wire.entangle('ohis_ci_26'),
                                    36: $wire.entangle('ohis_ci_36'),
                                    31: $wire.entangle('ohis_ci_31'),
                                    46: $wire.entangle('ohis_ci_46'),
                                },
                                teeth: [16, 11, 26, 36, 31, 46],
                                toothLabels: { 16: 'Buccal', 11: 'Labial', 26: 'Buccal', 36: 'Lingual', 31: 'Labial', 46: 'Lingual' },
                                get diTotal() {
                                    let vals = this.teeth.map(t => parseFloat(this.diScores[t]) || 0);
                                    let valid = vals.filter((v, i) => this.diScores[this.teeth[i]] !== '' && this.diScores[this.teeth[i]] !== null);
                                    return valid.length > 0 ? (valid.reduce((a,b)=>a+b, 0) / valid.length).toFixed(2) : '-';
                                },
                                get ciTotal() {
                                    let vals = this.teeth.map(t => parseFloat(this.ciScores[t]) || 0);
                                    let valid = vals.filter((v, i) => this.ciScores[this.teeth[i]] !== '' && this.ciScores[this.teeth[i]] !== null);
                                    return valid.length > 0 ? (valid.reduce((a,b)=>a+b, 0) / valid.length).toFixed(2) : '-';
                                },
                                get ohisTotal() {
                                    if (this.diTotal === '-' && this.ciTotal === '-') return '-';
                                    let di = this.diTotal === '-' ? 0 : parseFloat(this.diTotal);
                                    let ci = this.ciTotal === '-' ? 0 : parseFloat(this.ciTotal);
                                    return (di + ci).toFixed(2);
                                },
                                get ohisCategory() {
                                    if (this.ohisTotal === '-') return { text: 'Belum diisi', color: '#878a99', bg: '#f3f6f9' };
                                    let v = parseFloat(this.ohisTotal);
                                    if (v <= 1.2) return { text: 'Baik', color: '#0ab39c', bg: '#d1fae5' };
                                    if (v <= 3.0) return { text: 'Sedang', color: '#f7b84b', bg: '#fef3c7' };
                                    return { text: 'Buruk', color: '#f06548', bg: '#fee2e2' };
                                }
                            }">
                                <div style="min-width: 520px;">
                                    <!-- Compact Horizontal Table Layout -->
                                    <div class="overflow-x-auto mb-5">
                                        <table class="w-full border-collapse" style="table-layout: fixed;">
                                            <!-- Tooth Number Header -->
                                            <thead>
                                                <tr>
                                                    <th class="text-left py-2 px-2" style="width: 100px;"></th>
                                                    <template x-for="tooth in teeth" :key="'th-'+tooth">
                                                        <th class="text-center py-2 px-1">
                                                            <span class="text-xs font-black text-gray-700 block" x-text="tooth"></span>
                                                            <span class="text-[9px] font-semibold text-gray-400 block" x-text="toothLabels[tooth]"></span>
                                                        </th>
                                                    </template>
                                                    <th class="text-center py-2 px-2" style="width: 70px;">
                                                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-wider">Rata²</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- DI Row -->
                                                <tr style="border-top: 1px solid #f1f5f9;">
                                                    <td class="py-3 px-2">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[10px] font-black flex-none" style="background: #405189;">DI</div>
                                                            <div class="min-w-0">
                                                                <span class="text-xs font-bold text-gray-700 block leading-tight">Debris</span>
                                                                <span class="text-[9px] text-gray-400 font-semibold leading-tight">Index</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <template x-for="tooth in teeth" :key="'di-sel-'+tooth">
                                                        <td class="py-3 px-1">
                                                            <select x-model="diScores[tooth]" 
                                                                    class="w-full text-center text-sm font-bold border border-gray-200 rounded-xl py-2 px-1 bg-gray-50/50 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-all hover:border-gray-300 appearance-none cursor-pointer"
                                                                    style="text-align-last: center;">
                                                                <option value="">-</option>
                                                                <option value="0">0</option>
                                                                <option value="1">1</option>
                                                                <option value="2">2</option>
                                                                <option value="3">3</option>
                                                            </select>
                                                        </td>
                                                    </template>
                                                    <td class="py-3 px-2 text-center">
                                                        <div class="rounded-lg py-1.5 px-2 font-black text-sm" style="background: #f0f4ff; color: #405189;" x-text="diTotal"></div>
                                                    </td>
                                                </tr>
                                                <!-- CI Row -->
                                                <tr style="border-top: 1px solid #f1f5f9;">
                                                    <td class="py-3 px-2">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[10px] font-black flex-none" style="background: #0ab39c;">CI</div>
                                                            <div class="min-w-0">
                                                                <span class="text-xs font-bold text-gray-700 block leading-tight">Calculus</span>
                                                                <span class="text-[9px] text-gray-400 font-semibold leading-tight">Index</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <template x-for="tooth in teeth" :key="'ci-sel-'+tooth">
                                                        <td class="py-3 px-1">
                                                            <select x-model="ciScores[tooth]" 
                                                                    class="w-full text-center text-sm font-bold border border-gray-200 rounded-xl py-2 px-1 bg-gray-50/50 focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none transition-all hover:border-gray-300 appearance-none cursor-pointer"
                                                                    style="text-align-last: center;">
                                                                <option value="">-</option>
                                                                <option value="0">0</option>
                                                                <option value="1">1</option>
                                                                <option value="2">2</option>
                                                                <option value="3">3</option>
                                                            </select>
                                                        </td>
                                                    </template>
                                                    <td class="py-3 px-2 text-center">
                                                        <div class="rounded-lg py-1.5 px-2 font-black text-sm" style="background: #e6f9f5; color: #0ab39c;" x-text="ciTotal"></div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Result Summary (Compact) -->
                                    <div class="border-t-2 border-dashed border-gray-100 pt-4">
                                        <div class="flex flex-wrap gap-3 items-stretch">
                                            <!-- OHI-S Total -->
                                            <div class="flex-1 min-w-[120px] rounded-xl p-3 text-center border-2" style="background: #fffbeb; border-color: #fde68a;">
                                                <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color: #92400e;">OHI-S Total</p>
                                                <p class="text-2xl font-black mb-0" style="color: #92400e;" x-text="ohisTotal"></p>
                                            </div>
                                            <!-- Category -->
                                            <div class="flex-1 min-w-[120px] rounded-xl p-3 text-center flex flex-col items-center justify-center" :style="{ background: ohisCategory.bg }">
                                                <p class="text-[10px] font-black uppercase tracking-widest mb-1" :style="{ color: ohisCategory.color }">Kategori</p>
                                                <p class="text-xl font-black mb-0" :style="{ color: ohisCategory.color }" x-text="ohisCategory.text"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- End OHIS scroll wrapper -->

                                
                            </div>
                            <!-- Save Button -->
                            <div class="flex justify-end pt-4 mt-4 border-t border-gray-100 px-4 sm:px-5 lg:px-6 pb-4 mb-4">
                                <button type="button" wire:click="saveOhis" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                                    <svg wire:loading wire:target="saveOhis" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <i wire:loading.remove wire:target="saveOhis" class="ri-save-line"></i>
                                    <span wire:loading.remove wire:target="saveOhis">Simpan OHIS</span>
                                    <span wire:loading wire:target="saveOhis">Memproses...</span>
                                </button>
                            </div>
                        </div>
                        @endif
                        </div> <!-- end mainTab pemeriksaan -->

                        <!-- ═══ TAB CONTENT: SCREENING ═══ -->
                        <div x-show="mainTab === 'screening'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="card shadow-sm overflow-hidden border-t-2 border-[#0ab39c]">
                                <div class="p-4 border-b border-[#eff2f7] bg-gray-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-emerald-50 rounded-lg">
                                            <i class="ri-survey-line text-[#0ab39c] text-xl"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-base font-bold text-[#405189] mb-0">Hasil Screening Pasien</h6>
                                            <p class="text-[11px] text-gray-500 mb-0">Data screening yang telah diisi saat proses admisi</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="space-y-3 min-h-[200px]">
                                        @forelse($screeningData as $idx => $scr)
                                            <div class="group relative flex items-start gap-4 p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#0ab39c]/20 transition-all duration-300">
                                                <div class="flex-none w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-xs group-hover:bg-[#0ab39c] group-hover:text-white transition-all">
                                                    {{ $idx + 1 }}
                                                </div>
                                                <div class="flex-grow min-w-0">
                                                    <p class="text-sm font-bold text-[#2d3748] mb-2 leading-relaxed group-hover:text-[#405189] transition-colors">{{ $scr->pertanyaan }}</p>
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider {{ $scr->jawaban === 'ya' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100' }}">
                                                            <i class="{{ $scr->jawaban === 'ya' ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill' }}"></i>
                                                            {{ $scr->jawaban === 'ya' ? 'Ya' : 'Tidak' }}
                                                        </span>
                                                        @if(!empty($scr->keterangan))
                                                            <span class="text-[11px] text-gray-500 italic flex items-center gap-1">
                                                                <i class="ri-chat-quote-line text-gray-400"></i> {{ $scr->keterangan }}
                                                            </span>
                                                        @endif
                                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest px-2 py-0.5 bg-gray-50 rounded-full">{{ $scr->jenis_survei ?? 'Umum' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                    <i class="ri-survey-line text-3xl text-gray-300"></i>
                                                </div>
                                                <h3 class="text-sm font-bold text-gray-500 mb-1">Belum Ada Data Screening</h3>
                                                <p class="text-xs text-gray-400 text-center max-w-[220px]">Data screening akan tersedia setelah pasien menjalani proses admisi / screening.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ TAB CONTENT: PENUNJANG ═══ -->
                        <div x-show="mainTab === 'penunjang'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="card shadow-sm overflow-hidden border-t-2 border-[#f7b84b]">
                                <div class="p-4 border-b border-[#eff2f7] bg-gray-50/50">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-amber-50 rounded-lg">
                                                <i class="ri-file-search-line text-amber-500 text-xl"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-base font-bold text-[#405189] mb-0">Pemeriksaan Penunjang</h6>
                                                <p class="text-[11px] text-gray-500 mb-0">Hasil radiologi, laboratorium PA/PK</p>
                                            </div>
                                        </div>
                                        <button wire:click="addPenunjang" class="btn btn-primary text-white h-10 px-5 rounded-xl shadow-md flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto border-0">
                                            <i class="ri-add-line text-lg"></i>
                                            <span class="font-semibold text-xs uppercase tracking-wider">Upload Dokumen</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="border-b border-dashed border-gray-200 mb-5"></div>
                                    <div class="space-y-3 min-h-[200px]">
                                        @forelse($penunjangs as $idx => $pnj)
                                            <div class="group relative flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#f7b84b]/30 hover:bg-amber-50/20 transition-all duration-300">
                                                <div class="flex-none w-10 h-10 flex items-center justify-center rounded-xl {{ ($pnj['jenis'] ?? '') === 'Radiologi' ? 'bg-blue-50 text-blue-500' : (str_contains($pnj['jenis'] ?? '', 'Lab') ? 'bg-purple-50 text-purple-500' : 'bg-amber-50 text-amber-500') }} font-bold text-lg">
                                                    <i class="{{ ($pnj['jenis'] ?? '') === 'Radiologi' ? 'ri-scan-line' : (str_contains($pnj['jenis'] ?? '', 'Lab') ? 'ri-test-tube-line' : 'ri-file-text-line') }}"></i>
                                                </div>
                                                <div class="flex-grow min-w-0">
                                                    <span class="text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors block mb-1 truncate">{{ $pnj['document_name'] }}</span>
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ ($pnj['jenis'] ?? '') === 'Radiologi' ? 'bg-blue-50 text-blue-600 border border-blue-100' : (str_contains($pnj['jenis'] ?? '', 'Lab') ? 'bg-purple-50 text-purple-600 border border-purple-100' : 'bg-amber-50 text-amber-600 border border-amber-100') }}">
                                                            {{ $pnj['jenis'] ?? '-' }}
                                                        </span>
                                                        @if(!empty($pnj['file_path']))
                                                            @php $ext = pathinfo($pnj['file_path'], PATHINFO_EXTENSION); @endphp
                                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1">
                                                                <i class="{{ in_array($ext, ['pdf']) ? 'ri-file-pdf-2-line text-red-400' : 'ri-image-line text-blue-400' }}"></i>
                                                                {{ strtoupper($ext) }}
                                                            </span>
                                                        @endif
                                                        <span class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($pnj['created_at'])->format('d/m/Y H:i') }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex-none flex items-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-all transform sm:translate-x-2 group-hover:translate-x-0">
                                                    @if(!empty($pnj['file_path']))
                                                        <a href="{{ asset('storage/' . $pnj['file_path']) }}" target="_blank" class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white transition-all shadow-sm" title="Lihat File">
                                                            <i class="ri-eye-line text-lg"></i>
                                                        </a>
                                                    @endif
                                                    <button @click="
                                                        Swal.fire({
                                                            title: 'Konfirmasi Hapus',
                                                            text: 'Apakah Anda yakin ingin menghapus dokumen ini?',
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#f06548',
                                                            cancelButtonColor: '#6c757d',
                                                            confirmButtonText: 'Ya, Hapus!',
                                                            cancelButtonText: 'Batal',
                                                            reverseButtons: true
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                $wire.removePenunjang({{ $pnj['id'] }})
                                                            }
                                                        })
                                                    " class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                        <i class="ri-delete-bin-line text-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                    <i class="ri-file-search-line text-3xl text-gray-300"></i>
                                                </div>
                                                <h3 class="text-sm font-bold text-gray-500 mb-1">Belum Ada Dokumen Penunjang</h3>
                                                <p class="text-xs text-gray-400 text-center max-w-[220px]">Upload hasil radiologi atau laboratorium dengan klik tombol "Upload Dokumen".</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ TAB CONTENT: RIWAYAT ═══ -->
                        <div x-show="mainTab === 'riwayat'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="card shadow-sm overflow-hidden border-t-2 border-[#405189]">
                                <div class="p-4 border-b border-[#eff2f7] bg-gray-50/50">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-indigo-50 rounded-lg">
                                                <i class="ri-history-line text-[#405189] text-xl"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-base font-bold text-[#405189] mb-0">Riwayat Rekam Medis</h6>
                                                <p class="text-[11px] text-gray-500 mb-0">Daftar kunjungan dan pemeriksaan pasien sebelumnya</p>
                                            </div>
                                        </div>
                                        @if($selectedPendaftaran?->pasien_id)
                                            <a href="{{ route('laporan.kunjungan.print-riwayat', $selectedPendaftaran->pasien_id) }}" target="_blank" class="btn bg-indigo-50 text-indigo-600 h-10 px-5 rounded-xl font-bold text-sm shadow-sm hover:bg-indigo-600 hover:text-white transition-all flex items-center gap-2 w-full sm:w-auto justify-center">
                                                <i class="ri-printer-line text-lg"></i>
                                                <span class="font-semibold text-xs uppercase tracking-wider">Cetak Riwayat</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="space-y-3 min-h-[200px]">
                                        @forelse($riwayatData as $idx => $rw)
                                            <div class="group rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                                                <!-- Riwayat Header -->
                                                <button wire:click="toggleRiwayatDetail({{ $idx }})" class="w-full text-left p-4 flex items-center gap-4 hover:bg-gray-50/50 transition-colors">
                                                    <div class="flex-none w-10 h-10 flex items-center justify-center rounded-xl {{ isset($expandedRiwayat[$idx]) ? 'bg-[#405189] text-white' : 'bg-gray-50 text-gray-400' }} font-bold text-xs transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>
                                                    <div class="flex-grow min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                                            <span class="text-sm font-bold text-[#2d3748] tracking-tight">{{ $rw['tanggal'] }}</span>
                                                            <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase tracking-wider border border-indigo-100">{{ $rw['nomor_kunjungan'] }}</span>
                                                            <span class="px-2 py-0.5 rounded bg-gray-50 text-gray-500 text-[9px] font-bold uppercase">{{ $rw['status'] }}</span>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-500">
                                                            <span class="flex items-center gap-1"><i class="ri-user-star-line text-amber-500"></i> {{ $rw['dokter'] }}</span>
                                                            <span class="flex items-center gap-1"><i class="ri-hospital-line text-blue-500"></i> {{ $rw['poli'] }}</span>
                                                            <span class="flex items-center gap-1"><i class="ri-shield-user-line text-green-500"></i> {{ $rw['asuransi'] }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-none">
                                                        <i class="ri-arrow-{{ isset($expandedRiwayat[$idx]) ? 'up' : 'down' }}-s-line text-xl text-gray-400 transition-transform"></i>
                                                    </div>
                                                </button>

                                                <!-- Riwayat Detail (Expandable) -->
                                                @if(isset($expandedRiwayat[$idx]) && isset($rw['clinical']))
                                                    <div class="px-4 pb-4 border-t border-gray-100 bg-gray-50/30">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                                            <!-- Vitals -->
                                                            @if($rw['clinical']['vitals'])
                                                                <div class="bg-white rounded-xl border border-gray-100 p-4">
                                                                    <h6 class="text-[10px] font-black text-[#405189] uppercase tracking-widest mb-3 flex items-center gap-1.5"><i class="ri-heart-pulse-line"></i> Pemeriksaan Awal</h6>
                                                                    <div class="grid grid-cols-3 gap-2">
                                                                        @foreach(['kesadaran' => 'Kesadaran', 'td' => 'TD (mmHg)', 'nadi' => 'Nadi', 'suhu' => 'Suhu (°C)', 'bb' => 'BB (kg)', 'tb' => 'TB (cm)'] as $k => $lbl)
                                                                            <div class="p-2 bg-gray-50 rounded-lg">
                                                                                <span class="text-[9px] font-bold text-gray-400 uppercase block">{{ $lbl }}</span>
                                                                                <span class="text-xs font-black text-gray-700">{{ $rw['clinical']['vitals'][$k] ?: '-' }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- SOAP -->
                                                            @if($rw['clinical']['soap'])
                                                                <div class="bg-white rounded-xl border border-gray-100 p-4">
                                                                    <h6 class="text-[10px] font-black text-[#405189] uppercase tracking-widest mb-3 flex items-center gap-1.5"><i class="ri-file-list-3-line"></i> Clinical Notes</h6>
                                                                    <div class="space-y-2">
                                                                        @foreach(['subjective' => 'S', 'objective' => 'O', 'assessment' => 'A', 'planning' => 'P'] as $k => $lbl)
                                                                            <div class="flex gap-2">
                                                                                <div class="w-5 h-5 rounded bg-gray-100 flex items-center justify-center text-[9px] font-black text-gray-500 shrink-0">{{ $lbl }}</div>
                                                                                <p class="text-[11px] text-gray-600 leading-relaxed m-0">{{ $rw['clinical']['soap'][$k] ?? '-' }}</p>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- Diagnoses -->
                                                            @if(!empty($rw['clinical']['diagnoses']))
                                                                <div class="bg-white rounded-xl border border-gray-100 p-4">
                                                                    <h6 class="text-[10px] font-black text-[#405189] uppercase tracking-widest mb-3 flex items-center gap-1.5"><i class="ri-microscope-line"></i> Diagnosis</h6>
                                                                    <div class="space-y-2">
                                                                        @foreach($rw['clinical']['diagnoses'] as $dg)
                                                                            <div class="flex items-start gap-2 p-2 bg-orange-50/50 rounded-lg border border-orange-100">
                                                                                <span class="text-[10px] font-black text-indigo-500 font-mono shrink-0 mt-0.5">{{ $dg['kode_diagnosa'] }}</span>
                                                                                <span class="text-[11px] font-bold text-gray-700">{{ $dg['nama_diagnosa'] }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- Obat -->
                                                            @if(!empty($rw['clinical']['obat']))
                                                                <div class="bg-white rounded-xl border border-gray-100 p-4">
                                                                    <h6 class="text-[10px] font-black text-[#405189] uppercase tracking-widest mb-3 flex items-center gap-1.5"><i class="ri-capsule-line"></i> Resep Obat</h6>
                                                                    <div class="space-y-2">
                                                                        @foreach($rw['clinical']['obat'] as $ob)
                                                                            <div class="flex items-center gap-2 p-2 bg-emerald-50/50 rounded-lg border border-emerald-100">
                                                                                <i class="ri-medicine-bottle-line text-emerald-500 text-sm"></i>
                                                                                <div>
                                                                                    <p class="text-[11px] font-bold text-gray-700 m-0">{{ $ob['nama_obat'] }}</p>
                                                                                    <span class="text-[10px] text-gray-500">{{ $ob['dosis'] }} | {{ $ob['aturan'] }}</span>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="flex flex-col items-center justify-center py-16 px-4 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                                    <i class="ri-history-line text-3xl text-gray-300"></i>
                                                </div>
                                                <h3 class="text-sm font-bold text-gray-500 mb-1">Belum Ada Riwayat</h3>
                                                <p class="text-xs text-gray-400 text-center max-w-[220px]">Belum ada data riwayat kunjungan untuk pasien ini.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- Selection Call to Action -->
                        <div class="card h-full flex flex-col items-center justify-center p-12 text-center opacity-40 min-h-[600px] border-2 border-dashed border-gray-200 rounded-[2rem] bg-gray-50/30">
                            <i class="ri-user-follow-line text-[100px] text-gray-300 mb-6"></i>
                            <h2 class="text-2xl font-black text-[#405189]">Silakan Pilih Pasien</h2>
                            <p class="text-gray-500 max-w-sm mt-2 font-medium">Pilih salah satu pasien dari daftar antrean di sebelah kiri untuk mulai menginput data tindakan dan medis lainnya.</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Integration Modal: Tambah Diagnosis -->
            <div x-show="$wire.showDiagnosisModal" 
                 class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 x-transition.opacity
                 style="display: none;">
                <div x-show="$wire.showDiagnosisModal"
                     @click.away="$wire.set('showDiagnosisModal', false)"
                     x-transition.scale.95
                     class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-visible">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 rounded-t-3xl flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                            <i class="ri-microscope-line text-orange-500"></i> Tambah Diagnosis Pasien
                        </h5>
                        <button @click="$wire.set('showDiagnosisModal', false)" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-8 py-6 max-h-[75vh] overflow-visible">
                        <div class="grid grid-cols-1 gap-6">
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Diagnosis / ICD-10 <span class="text-red-500">*</span></label>
                                <x-custom-dropdown-async 
                                    model="kode_diagnosa" 
                                    search-method="searchDiagnosis"
                                    label-method="getDiagnosisLabel"
                                    placeholder="Ketik kode/nama diagnosis..."
                                    live="true"
                                />
                                @error('kode_diagnosa') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Jenis ICD <span class="text-red-500">*</span></label>
                                    <x-custom-dropdown 
                                        model="jenis_icd" 
                                        :options="[
                                            ['value' => 'Utama', 'label' => 'Utama / Primary', 'icon' => 'ri-star-fill text-yellow-500'],
                                            ['value' => 'Sekunder', 'label' => 'Sekunder / Secondary', 'icon' => 'ri-star-line text-gray-400']
                                        ]"
                                        placeholder="Pilih Jenis"
                                    />
                                    @error('jenis_icd') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Kasus <span class="text-red-500">*</span></label>
                                    <x-custom-dropdown 
                                        model="kasus_icd" 
                                        :options="[
                                            ['value' => 'Baru', 'label' => 'Kasus Baru', 'icon' => 'ri-file-add-line text-blue-500'],
                                            ['value' => 'Lama', 'label' => 'Kasus Lama', 'icon' => 'ri-history-line text-purple-500']
                                        ]"
                                        placeholder="Kasus Baru/Lama"
                                    />
                                    @error('kasus_icd') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-8 py-5 rounded-b-3xl bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="$wire.set('showDiagnosisModal', false)" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600">
                            <i class="ri-arrow-go-back-line"></i>
                            Batal
                        </button>
                        <button type="button" wire:click="saveDiagnosis" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="saveDiagnosis" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <i wire:loading.remove wire:target="saveDiagnosis" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="saveDiagnosis">Simpan Diagnosis</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Tambah Tindakan -->
            <div x-show="$wire.showTindakanModal" 
                 class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 x-transition.opacity
                 style="display: none;">
                <div x-show="$wire.showTindakanModal"
                     @click.away="$wire.set('showTindakanModal', false)"
                     x-transition.scale.95
                     class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-visible">
                    
                    <div class="px-6 py-4 rounded-t-3xl flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                            <i class="ri-hand-heart-line text-primary"></i> Tambah Tindakan Medis
                        </h5>
                        <button @click="$wire.set('showTindakanModal', false)" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-visible">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Pilih Tindakan <span class="text-red-500">*</span></label>
                                <x-custom-dropdown-async 
                                    model="kode_tindakan" 
                                    search-method="searchTindakan"
                                    label-method="getTindakanLabel"
                                    placeholder="Ketik kode/nama tindakan..."
                                    live="true"
                                />
                                @error('kode_tindakan') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Tarif / Biaya <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">Rp</span>
                                        <input type="number" wire:model="biaya_tindakan" class="h-11 w-full rounded-xl border border-gray-200 pl-10 pr-4 text-sm font-bold text-[#405189] outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all bg-gray-50/50" readonly>
                                    </div>
                                    @error('biaya_tindakan') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                <div style="display: none;">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Jasa Medis (Jasmed)</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">Rp</span>
                                        <input type="number" wire:model="jasmed_tindakan" class="h-11 w-full rounded-xl border border-gray-200 pl-10 pr-4 text-sm font-bold text-emerald-600 outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/5 transition-all bg-gray-50/50" readonly>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">BHP (Habis Pakai)</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">Rp</span>
                                        <input type="number" wire:model="bhp_tindakan" class="h-11 w-full rounded-xl border border-gray-200 pl-10 pr-4 text-sm font-bold text-orange-600 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/5 transition-all bg-gray-50/50" readonly>
                                    </div>
                                </div>
                                <div style="display: none;">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Satuan Jasmed</label>
                                    <input type="text" wire:model="satuan_tindakan" class="h-11 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all bg-gray-50/50 text-gray-600 font-medium" placeholder="Sesi / Tindakan" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-5 rounded-b-3xl bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="$wire.set('showTindakanModal', false)" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600">
                            <i class="ri-arrow-go-back-line"></i> Batal
                        </button>
                        <button type="button" wire:click="saveTindakan" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="saveTindakan" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <i wire:loading.remove wire:target="saveTindakan" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="saveTindakan">Simpan Tindakan</span>
                            <span wire:loading wire:target="saveTindakan">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Tambah Resep/Obat -->
            <div x-show="$wire.showResepModal" 
                 class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 x-transition.opacity
                 style="display: none;">
                <div x-show="$wire.showResepModal"
                     @click.away="$wire.set('showResepModal', false)"
                     x-transition.scale.95
                     class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-visible">
                    
                    <div class="px-6 py-4 rounded-t-3xl flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                            <i class="ri-capsule-line text-emerald-500"></i> Tambah Resep Obat
                        </h5>
                        <button @click="$wire.set('showResepModal', false)" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-visible">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Pilih Obat <span class="text-red-500">*</span></label>
                                <x-custom-dropdown-async 
                                    model="kode_obat" 
                                    search-method="searchObat"
                                    label-method="getObatLabel"
                                    placeholder="Ketik kode/nama obat..."
                                    live="true"
                                />
                                @error('kode_obat') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Jumlah (Qty) <span class="text-red-500">*</span></label>
                                    <input type="number" wire:model="jumlah_obat" class="h-11 w-full rounded-xl border border-gray-200 px-4 text-sm font-bold outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all bg-gray-50/50">
                                    @error('jumlah_obat') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Aturan Pakai (Signa) <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="aturan_obat" class="h-11 w-full rounded-xl border border-gray-200 px-4 text-sm font-bold text-emerald-600 outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/5 transition-all bg-gray-50/50" placeholder="Contoh: 3 x 1 Sesudah Makan">
                                    @error('aturan_obat') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-5 rounded-b-3xl bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="$wire.set('showResepModal', false)" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600">
                            <i class="ri-arrow-go-back-line"></i> Batal
                        </button>
                        <button type="button" wire:click="saveResep" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="saveResep" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <i wire:loading.remove wire:target="saveResep" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="saveResep">Simpan Resep</span>
                            <span wire:loading wire:target="saveResep">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Tambah BMHP -->
            <div x-show="$wire.showBmhpModal" 
                 class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 x-transition.opacity
                 style="display: none;">
                <div x-show="$wire.showBmhpModal"
                     @click.away="$wire.set('showBmhpModal', false)"
                     x-transition.scale.95
                     class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-visible">
                    
                    <div class="px-6 py-4 rounded-t-3xl flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                            <i class="ri-flask-line text-purple-500"></i> Tambah BMHP / Alkes
                        </h5>
                        <button @click="$wire.set('showBmhpModal', false)" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-visible">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Pilih BMHP <span class="text-red-500">*</span></label>
                                <x-custom-dropdown-async 
                                    model="kode_bmhp" 
                                    search-method="searchBmhp"
                                    label-method="getBmhpLabel"
                                    placeholder="Ketik kode/nama BMHP..."
                                    live="true"
                                />
                                @error('kode_bmhp') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Jumlah <span class="text-red-500">*</span></label>
                                    <input type="number" wire:model="jumlah_bmhp" class="h-11 w-full rounded-xl border border-gray-200 px-4 text-sm font-bold outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all bg-gray-50/50">
                                    @error('jumlah_bmhp') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Satuan</label>
                                    <input type="text" wire:model="satuan_bmhp" class="h-11 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all bg-gray-50/50 text-gray-600" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-5 rounded-b-3xl bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="$wire.set('showBmhpModal', false)" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600">
                            <i class="ri-arrow-go-back-line"></i> Batal
                        </button>
                        <button type="button" wire:click="saveBmhp" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="saveBmhp" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <i wire:loading.remove wire:target="saveBmhp" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="saveBmhp">Simpan BMHP</span>
                            <span wire:loading wire:target="saveBmhp">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Upload Penunjang -->
            <div x-show="$wire.showPenunjangModal" 
                 class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 x-transition.opacity
                 style="display: none;">
                <div x-show="$wire.showPenunjangModal"
                     @click.away="$wire.set('showPenunjangModal', false)"
                     x-transition.scale.95
                     class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-visible">
                    
                    <div class="px-6 py-4 rounded-t-3xl flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                            <i class="ri-file-upload-line text-amber-500"></i> Upload Dokumen Penunjang
                        </h5>
                        <button @click="$wire.set('showPenunjangModal', false)" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-visible">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Dokumen <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="penunjang_nama" class="h-11 w-full rounded-xl border border-gray-200 px-4 text-sm font-bold outline-none focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 transition-all bg-gray-50/50" placeholder="Contoh: Hasil Rontgen Thorax">
                                @error('penunjang_nama') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Penunjang <span class="text-red-500">*</span></label>
                                <x-custom-dropdown 
                                    model="penunjang_jenis" 
                                    :options="[
                                        ['value' => 'Radiologi', 'label' => 'Radiologi', 'icon' => 'ri-scan-line text-blue-500'],
                                        ['value' => 'Lab PK', 'label' => 'Laboratorium PK (Patologi Klinik)', 'icon' => 'ri-test-tube-line text-purple-500'],
                                        ['value' => 'Lab PA', 'label' => 'Laboratorium PA (Patologi Anatomi)', 'icon' => 'ri-test-tube-line text-indigo-500'],
                                    ]"
                                    placeholder="Pilih Jenis"
                                />
                                @error('penunjang_jenis') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Upload File <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="file" wire:model="penunjang_file" accept=".pdf,.jpg,.jpeg,.png" 
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-[#405189]/10 file:text-[#405189] hover:file:bg-[#405189]/20 file:transition-all file:uppercase file:tracking-wider cursor-pointer border border-gray-200 rounded-xl bg-gray-50/50 focus:ring-4 focus:ring-[#405189]/5 transition-all">
                                    <div wire:loading wire:target="penunjang_file" class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <svg class="animate-spin h-5 w-5 text-[#405189]" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1.5 flex items-center gap-1"><i class="ri-information-line"></i> Format: PDF, JPG, PNG — Maks. 10MB</p>
                                @error('penunjang_file') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-5 rounded-b-3xl bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="$wire.set('showPenunjangModal', false)" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600">
                            <i class="ri-arrow-go-back-line"></i> Batal
                        </button>
                        <button type="button" wire:click="savePenunjang" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="savePenunjang" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <i wire:loading.remove wire:target="savePenunjang" class="ri-upload-2-line"></i>
                            <span wire:loading.remove wire:target="savePenunjang">Upload & Simpan</span>
                            <span wire:loading wire:target="savePenunjang">Mengupload...</span>
                        </button>
                    </div>
                </div>
            </div>

            <style>
                .scrollbar-hide::-webkit-scrollbar { display: none; }
                .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
                [x-cloak] { display: none !important; }

                /* Premium Card Table Overrides */
                .line-clamp-1 {
                    display: -webkit-box;
                    -webkit-line-clamp: 1;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }
                .premium-shadow {
                    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05), 0 2px 10px -5px rgba(0,0,0,0.02);
                }
                .glass-card {
                    background: rgba(255, 255, 255, 0.85);
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }

                /* Compact Pagination for Sidebar */
                .pagination-sidebar nav > div:first-child { display: none !important; }
                .pagination-sidebar nav > div:last-child { justify-content: center !important; }
                .pagination-sidebar nav span.relative.z-0 { gap: 4px !important; }
                .pagination-sidebar nav a, 
                .pagination-sidebar nav span[aria-disabled="true"] span,
                .pagination-sidebar nav span[aria-current="page"] span {
                    min-width: 32px !important;
                    height: 32px !important;
                    padding: 0 8px !important;
                    font-size: 11px !important;
                    border-radius: 6px !important;
                }
            </style>
        </div>
        <script>
            if (!window.odontogramStoreRegistered) {
                document.addEventListener('alpine:init', () => {
                    registerOdontogramStore();
                });
                window.odontogramStoreRegistered = true;
            }

            // Also register immediately if Alpine is already defined (for Livewire re-renders)
            if (window.Alpine) {
                registerOdontogramStore();
            }

            function registerOdontogramStore() {
                if (window.odontogramStoreDefined) return;
                
                Alpine.data('odontogramStore', (entangledState) => ({
                    showMenu: false,
                    menuX: 0,
                    menuY: 0,
                    selectedTooth: null,
                    selectedPart: null,
                    partLabelMap: {
                        'T': 'Top / Buccal / Labial',
                        'B': 'Bottom / Lingual / Palatal',
                        'L': 'Left / Mesial / Distal',
                        'R': 'Right / Distal / Mesial',
                        'C': 'Center / Occlusal / Incisal'
                    },
                    state: entangledState, 

                    init() {
                        // State is already bound via entangledState
                    },

                    openMenu(e, tooth, part) {
                        this.selectedTooth = tooth;
                        this.selectedPart = part;
                        this.showMenu = true;
                        
                        this.$nextTick(() => {
                            let w = window.innerWidth;
                            let h = window.innerHeight;
                            let x = e.clientX;
                            let y = e.clientY;
                            
                            if (x + 240 > w) x = w - 250;
                            if (y + 320 > h) y = h - 330;
                            
                            this.menuX = x;
                            this.menuY = y;
                        });
                    },

                    applyCategory(kode, warna) {
                        let key = this.selectedTooth + '-' + this.selectedPart;
                        if (!kode) {
                            delete this.state[key];
                        } else {
                            this.state[key] = { kode: kode, color: warna };
                        }
                        
                        this.state = {...this.state};
                        this.showMenu = false;
                    }
                }));
                window.odontogramStoreDefined = true;
            }
        </script>
        HTML;
    }
}
