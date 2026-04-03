<?php

namespace App\Modules\Transaksi\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use App\Models\MstPoli;
use App\Models\MstDiagnosis;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransaksiPage extends Component
{
    public $selectedDate;
    public $selectedPoli = 'all';
    public $searchPasien = '';
    public $selectedPendaftaranId;
    public $selectedPendaftaran;
    public $poliList = [];
    public $pasienList = [];
    public $poliListOptions = [];
    public $kesadaranList = [];

    // SOAP / Anamnesis State
    public $subyektif = '';
    public $obyektif = '';
    public $assessment = '';
    public $planning = '';

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
    public $diagnosisListOptions = [];

    // Tindakan Modal State
    public $showTindakanModal = false;
    public $kode_tindakan = '';
    public $biaya_tindakan = 0;
    public $jasmed_tindakan = 0;
    public $bhp_tindakan = 0;
    public $satuan_tindakan = '';
    public $tindakanListOptions = [];

    // Resep (Obat) Modal State
    public $showResepModal = false;
    public $kode_obat = '';
    public $jumlah_obat = 1;
    public $dosis_obat = '';
    public $aturan_obat = '3x1';
    public $obatListOptions = [];

    // BMHP Modal State
    public $showBmhpModal = false;
    public $kode_bmhp = '';
    public $jumlah_bmhp = 1;
    public $satuan_bmhp = '';
    public $bmhpListOptions = [];

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
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
        } else {
            $this->subyektif = '';
            $this->obyektif = '';
            $this->assessment = '';
            $this->planning = '';
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
            ->select('trx_tindakan.id', 'mst_tindakan.nama_tindakan as nama', 'trx_tindakan.kode_tindakan as kode', 'trx_tindakan.biaya', 'trx_tindakan.satuan')
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
                'created_by' => auth()->user()->username ?? 'System',
                'updated_at' => now(),
            ]
        );

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Clinical Notes berhasil disimpan.']);
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

    public function render()
    {
        // Filter Poli based on User Mapping
        $this->poliList = auth()->user()->polis()->where('status', 'Aktif')->get();
        
        // If user is Super Admin or has no mapping, show all active polis (optional logic, but usually Admin sees all)
        if (auth()->user()->role === 'Super Admin' || $this->poliList->isEmpty()) {
            $this->poliList = MstPoli::where('status', 'Aktif')->get();
        }

        $this->poliListOptions = $this->poliList->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli, 'icon' => 'ri-hospital-line text-blue-500'])->toArray();
        array_unshift($this->poliListOptions, ['value' => 'all', 'label' => 'Semua Poli', 'icon' => 'ri-group-line text-gray-500']);
        
        $this->kesadaranList = [
            ['value' => 'Compos Mentis', 'label' => 'Compos Mentis', 'icon' => 'ri-checkbox-circle-line text-green-500'],
            ['value' => 'Somnolence', 'label' => 'Somnolence', 'icon' => 'ri-eye-close-line text-yellow-500'],
            ['value' => 'Sopor', 'label' => 'Sopor', 'icon' => 'ri-eye-close-line text-orange-500'],
            ['value' => 'Coma', 'label' => 'Coma', 'icon' => 'ri-close-circle-line text-red-500'],
        ];

        if (empty($this->diagnosisListOptions)) {
            $this->diagnosisListOptions = DB::table('mst_diagnosis')
                ->select('kode_diagnosa as value', 'nama_diagnosa as label')
                ->get()
                ->map(fn($d) => ['value' => $d->value, 'label' => $d->value . ' - ' . $d->label, 'icon' => 'ri-microscope-line text-warning'])
                ->toArray();
                
            $this->tindakanListOptions = DB::table('mst_tindakan')
                ->where('status', 'Aktif')
                ->select('kode_tindakan as value', 'nama_tindakan as label')
                ->get()
                ->map(fn($t) => ['value' => $t->value, 'label' => $t->value . ' - ' . $t->label, 'icon' => 'ri-hand-heart-line text-primary'])
                ->toArray();
                
            $this->obatListOptions = DB::table('mst_obat')
                ->where('status', 'Aktif')
                ->select('kode_obat as value', 'nama_obat as label')
                ->get()
                ->map(fn($o) => ['value' => $o->value, 'label' => $o->value . ' - ' . $o->label, 'icon' => 'ri-capsule-line text-emerald-500'])
                ->toArray();
                
            $this->bmhpListOptions = DB::table('mst_bmhp')
                ->where('status', 'Aktif')
                ->select('kode_bmhp as value', 'nama_bmhp as label')
                ->get()
                ->map(fn($b) => ['value' => $b->value, 'label' => $b->value . ' - ' . $b->label, 'icon' => 'ri-flask-line text-purple-500'])
                ->toArray();
        }

        $query = TrxPendaftaran::with(['pasien', 'poli', 'dokter'])
            ->whereDate('created_at', $this->selectedDate)
            ->whereIn('status', ['terdaftar', 'menunggu_screening', 'selesai']);

        if ($this->selectedPoli !== 'all') {
            $query->where('poli_id', $this->selectedPoli);
        }

        if ($this->searchPasien) {
            $query->whereHas('pasien', function($q) {
                $q->where('nama_pasien', 'like', '%' . $this->searchPasien . '%')
                  ->orWhere('no_rm', 'like', '%' . $this->searchPasien . '%');
            });
        }

        $this->pasienList = $query->orderBy('created_at', 'asc')->get();

        return <<<'HTML'
        <div x-data="{ 
            activeTab: 'soap',
            medicalTab: 'diagnosis',
            searchDiag: '',
            searchTind: '',
            searchObat: '',
            searchBmhp: '',
            initDataTable() { 
                const t='#patientTable'; 
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} 
                $(t).DataTable({scrollX:false,dom:'rtp',pageLength:10,language:{zeroRecords:'Tidak ada pasien',emptyTable:'Belum ada pendaftaran',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}});
            },
            init(){ 
                $nextTick(()=>{ this.initDataTable(); });
                if (window.Livewire) {
                    Livewire.hook('morph.updated', () => {
                        $nextTick(() => { this.initDataTable(); });
                    });
                }
            }
        }" @patient-selected.window="$nextTick(()=>{ initDataTable(); })" @refresh-table.window="$nextTick(()=>{ initDataTable(); })" x-init="initDataTable()">
            
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon"><i class="ri-exchange-funds-line"></i></div>
                    <h1>Transaksi Layanan Dokter</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
                    <span class="sep">/</span>
                    <span>Transaksi</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar Left: Filtering & Patient List -->
                <div class="space-y-6">
                    <!-- Card: Filter Data -->
                    <div class="card shadow-sm border-t-4 border-[#405189] relative z-[30]" style="overflow: visible !important;">
                        <div class="p-5">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="h-10 w-10 flex items-center justify-center bg-[#405189]/10 text-[#405189] rounded-xl shadow-sm">
                                    <i class="ri-filter-3-line text-lg"></i>
                                </div>
                                <h5 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-0">Filter Data</h5>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tanggal</label>
                                    <input type="date" wire:model.live="selectedDate" class="form-control text-sm h-10 border-gray-200 rounded-lg w-full focus:border-[#405189] transition-all">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Poli Tujuan</label>
                                    <x-custom-dropdown model="selectedPoli" :options="$this->poliListOptions" placeholder="Pilih Poli" searchable="true" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient List Card -->
                    <div class="card shadow-sm overflow-hidden flex-1 border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                            <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0 flex items-center gap-2">
                                <i class="ri-group-line"></i> Daftar Pasien
                            </h6>
                            <span class="badge bg-[#405189] text-white rounded-full px-2 text-[10px]">{{ $this->pasienList->count() }}</span>
                        </div>
                        <div class="p-4 bg-white">
                            <div class="relative mb-3">
                                <input type="text" wire:model.live.debounce.300ms="searchPasien" class="form-control text-xs h-9 pl-8 border-gray-200 rounded-lg w-full focus:border-[#405189] transition-all" placeholder="Cari nama/RM...">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <div class="max-h-[500px] overflow-y-auto space-y-2 p-1">
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
                                        <p class="text-xs font-bold">Tidak ada pasien</p>
                                    </div>
                                @endforelse
                            </div>
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
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-shield-user-line text-[#405189]"></i> {{ $selectedPendaftaran->asuransi->nama_asuransi ?? 'UMUM / PRIBADI' }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-hospital-line text-[#405189]"></i> {{ $selectedPendaftaran->poli->nama_poli }}</span>
                                                <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-heart-pulse-line text-[#405189]"></i> Agama: {{ $selectedPendaftaran->pasien->agama ?? '-' }}</span>
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
                                            <p class="text-xs font-black text-[#405189] m-0 flex items-center gap-1.5"><i class="ri-user-star-line text-amber-500"></i> {{ $selectedPendaftaran->dokter->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Transaction Tabs / Cards -->
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
                                        </div>
                                        <div class="flex justify-end pt-4 mt-6 border-t border-gray-100">
                                            <button type="button" wire:click="savePemeriksaan" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                                                <svg wire:loading wire:target="savePemeriksaan" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <i wire:loading.remove wire:target="savePemeriksaan" class="ri-save-line"></i>
                                                <span wire:loading.remove wire:target="savePemeriksaan">Simpan Data</span>
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

                                        <!-- Premium Card Table Replacement -->
                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($diagnoses as $idx => $diag)
                                                <div wire:key="diag-card-{{ $diag['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($diag['nama']) }} {{ strtolower($diag['kode']) }}"
                                                     x-show="searchDiag === '' || $el.dataset.search.includes(searchDiag.toLowerCase())" 
                                                     class="group relative flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <!-- Index / Number -->
                                                    <div class="flex-none w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <!-- Diagnosis Data -->
                                                    <div class="flex-grow">
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
                                                    <div class="flex-none flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
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
                                                            " class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-lg"></i>
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

                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($tindakans as $idx => $tdk)
                                                <div wire:key="tindakan-card-{{ $tdk['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($tdk['nama'] ?? '') }}"
                                                     x-show="searchTind === '' || $el.dataset.search.includes(searchTind.toLowerCase())" 
                                                     class="group relative flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <div class="flex-none w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <div class="flex-grow">
                                                        <span class="text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors block mb-1">{{ $tdk['nama'] }}</span>
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

                                                    <div class="flex-none flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
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
                                                            " class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-lg"></i>
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

                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($reseps as $idx => $rsp)
                                                <div wire:key="resep-card-{{ $rsp['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($rsp['nama'] ?? '') }}"
                                                     x-show="searchObat === '' || $el.dataset.search.includes(searchObat.toLowerCase())" 
                                                     class="group relative flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <div class="flex-none w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <div class="flex-grow">
                                                        <div class="flex items-center gap-3 mb-1">
                                                            <span class="text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors">{{ $rsp['nama'] }}</span>
                                                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600 text-[10px] font-black border border-emerald-100 italic"> Dosis : {{ $rsp['qty'] }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="ri-information-line text-gray-400 text-xs"></i>
                                                            <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Aturan pakai : {{ $rsp['signa'] }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex-none flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
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
                                                            " class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-lg"></i>
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

                                        <div class="space-y-3 min-h-[200px]" wire:loading.class="opacity-50">
                                            @forelse($bmhps as $idx => $bm)
                                                <div wire:key="bmhp-card-{{ $bm['id'] ?? $idx }}"
                                                     data-search="{{ strtolower($bm['nama'] ?? '') }}"
                                                     x-show="searchBmhp === '' || $el.dataset.search.includes(searchBmhp.toLowerCase())" 
                                                     class="group relative flex items-center gap-4 p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-[#405189]/20 hover:bg-[#405189]/[0.02] transition-all duration-300">
                                                    
                                                    <div class="flex-none w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 font-bold text-xs group-hover:bg-[#405189] group-hover:text-white transition-all">
                                                        {{ $idx + 1 }}
                                                    </div>

                                                    <div class="flex-grow">
                                                        <div class="text-sm font-bold text-[#2d3748] tracking-tight group-hover:text-[#405189] transition-colors mb-0.5">
                                                            {{ $bm['nama'] }}
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="ri-stack-line text-gray-400 text-[10px]"></i>
                                                            <span class="text-[11px] font-bold text-purple-600 uppercase tracking-wider italic">Jumlah: {{ $bm['jumlah'] }} {{ $bm['satuan'] }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex-none flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
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
                                                            " class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                            <i class="ri-delete-bin-line text-lg"></i>
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
                                <x-custom-dropdown 
                                    model="kode_diagnosa" 
                                    :options="$diagnosisListOptions"
                                    placeholder="Pilih Diagnosis (ICD-10)"
                                    searchable="true"
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
                                <x-custom-dropdown 
                                    model="kode_tindakan" 
                                    :options="$tindakanListOptions"
                                    placeholder="Cari tindakan..."
                                    searchable="true"
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
                                <x-custom-dropdown 
                                    model="kode_obat" 
                                    :options="$obatListOptions"
                                    placeholder="Cari nama obat..."
                                    searchable="true"
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
                                <x-custom-dropdown 
                                    model="kode_bmhp" 
                                    :options="$bmhpListOptions"
                                    placeholder="Cari BMHP..."
                                    searchable="true"
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
            </style>
        </div>
        HTML;
    }
}
