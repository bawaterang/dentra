<?php

namespace App\Modules\Transaksi\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use App\Models\MstPoli;
use Carbon\Carbon;

class TransaksiPage extends Component
{
    public $selectedDate;
    public $selectedPoli = 'all';
    public $searchPasien = '';
    public $selectedPendaftaranId;
    public $selectedPendaftaran;
    public $poliList = [];
    public $pasienList = [];

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

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function selectPendaftaran($id)
    {
        $this->selectedPendaftaranId = $id;
        $this->selectedPendaftaran = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'asuransi'])->find($id);
        
        // SOAP (Simulated)
        $this->subyektif = '';
        $this->obyektif = '';
        $this->assessment = '';
        $this->planning = '';
        
        // Vitals / Pemeriksaan Awal (From Pendaftaran)
        $this->kesadaran = $this->selectedPendaftaran->kesadaran ?? '';
        $this->tekanan_darah = $this->selectedPendaftaran->tekanan_darah ?? '';
        $this->nadi = $this->selectedPendaftaran->nadi ?? '';
        $this->suhu = $this->selectedPendaftaran->suhu ?? '';
        $this->berat_badan = $this->selectedPendaftaran->berat_badan ?? '';
        $this->tinggi_badan = $this->selectedPendaftaran->tinggi_badan ?? '';
        $this->riwayat_penyakit = $this->selectedPendaftaran->riwayat_penyakit ?? '';
        $this->alergi = $this->selectedPendaftaran->alergi ?? '';
        $this->keterangan_lain = $this->selectedPendaftaran->keterangan_lain ?? '';
        
        // Load existing data if any (Simulated)
        $this->diagnoses = [];
        $this->tindakans = [];
        $this->reseps = [];
        $this->bmhps = [];
        
        $this->dispatch('patient-selected');
    }

    public function addDiagnosis()
    {
        if ($this->tempDiagnosis) {
            $this->diagnoses[] = ['id' => count($this->diagnoses) + 1, 'nama' => $this->tempDiagnosis, 'kode' => 'ICD-' . rand(100, 999)];
            $this->tempDiagnosis = '';
        }
    }

    public function addTindakan()
    {
        if ($this->tempTindakan) {
            $this->tindakans[] = ['id' => count($this->tindakans) + 1, 'nama' => $this->tempTindakan, 'biaya' => rand(50000, 200000)];
            $this->tempTindakan = '';
        }
    }

    public function addResep()
    {
        if ($this->tempObat) {
            $this->reseps[] = ['id' => count($this->reseps) + 1, 'nama' => $this->tempObat, 'qty' => $this->tempQty, 'signa' => '3x1'];
            $this->tempObat = '';
            $this->tempQty = 1;
        }
    }

    public function addBmhp()
    {
        if ($this->tempBmhp) {
            $this->bmhps[] = ['id' => count($this->bmhps) + 1, 'nama' => $this->tempBmhp, 'qty' => 1];
            $this->tempBmhp = '';
        }
    }

    public function removeItem($list, $index)
    {
        unset($this->{$list}[$index]);
        $this->{$list} = array_values($this->{$list});
    }

    public function render()
    {
        // Filter Poli based on User Mapping
        $this->poliList = auth()->user()->polis()->where('mst_poli.status', 'Aktif')->get();
        
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
            dtConfig: {scrollX:false,dom:'lrtip',pageLength:10,language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ - _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 data',infoFiltered:'(disaring dari _MAX_ total)',zeroRecords:'Tidak ada data ditemukan',emptyTable:'Tidak ada data',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}},
            initDataTable() { 
                const t='#patientTable'; 
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} 
                $(t).DataTable({scrollX:false,dom:'rtp',pageLength:10,language:{zeroRecords:'Tidak ada pasien',emptyTable:'Belum ada pendaftaran',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}});
            },
            initMedicalTable(tableId, searchId) {
                if($.fn.DataTable.isDataTable(tableId)){$(tableId).DataTable().destroy()}
                const tbl = $(tableId).DataTable(this.dtConfig);
                $(searchId).off('keyup').on('keyup', function(){ tbl.search(this.value).draw(); });
            },
            initCurrentMedicalTab() {
                const map = {diagnosis:['#diagnosisTable','#searchDiagnosis'], tindakan:['#tindakanTable','#searchTindakan'], resep:['#resepTable','#searchResep'], bmhp:['#bmhpTable','#searchBmhp']};
                const cfg = map[this.medicalTab];
                if(cfg) this.initMedicalTable(cfg[0], cfg[1]);
            },
            init(){ $nextTick(()=>this.initDataTable()) }
        }" @refresh-table.window="$nextTick(()=>{ initDataTable(); initCurrentMedicalTab(); })" x-init="initDataTable()" x-effect="if(medicalTab){ $nextTick(()=>initCurrentMedicalTab()) }">
            
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
                                    <div x-show="medicalTab === 'diagnosis'" x-cloak>
                                        <div class="p-4 border-b border-[#eff2f7]">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                                <h6 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-0 flex items-center gap-2">
                                                    <i class="ri-microscope-line text-orange-500"></i> Diagnosis Pasien
                                                </h6>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" id="searchDiagnosis" wire:model.defer="tempDiagnosis" wire:keydown.enter="addDiagnosis" class="h-10 w-full sm:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari kode ICD-10...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                                                    </div>
                                                    <button wire:click="addDiagnosis" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="diagnosisTable" class="table align-middle table-nowrap w-full">
                                                <thead class="table-light text-muted">
                                                    <tr>
                                                        <th class="!text-center" style="width:50px">#</th>
                                                        <th>Diagnosis / ICD-10</th>
                                                        <th class="!text-center" style="width:60px">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($diagnoses as $idx => $diag)
                                                        <tr>
                                                            <td class="text-center"><span class="text-[#878a99] font-semibold">{{ $idx + 1 }}</span></td>
                                                            <td>
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-semibold text-[#495057]">{{ $diag['nama'] }}</span>
                                                                    <span class="badge bg-warning-subtle text-warning">{{ $diag['kode'] }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <button wire:click="removeItem('diagnoses', {{ $idx }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="text-center py-8">
                                                                <div class="opacity-40">
                                                                    <i class="ri-microscope-line text-3xl block mb-2"></i>
                                                                    <p class="text-xs font-bold mb-0">Belum ada diagnosis terpilih</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- TAB: Tindakan Medis -->
                                    <div x-show="medicalTab === 'tindakan'" x-cloak>
                                        <div class="p-4 border-b border-[#eff2f7]">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                                <h6 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-0 flex items-center gap-2">
                                                    <i class="ri-hand-heart-line text-blue-500"></i> Tindakan Medis
                                                </h6>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" id="searchTindakan" wire:model.defer="tempTindakan" wire:keydown.enter="addTindakan" class="h-10 w-full sm:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari tindakan / layanan...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                                                    </div>
                                                    <button wire:click="addTindakan" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="tindakanTable" class="table align-middle table-nowrap w-full">
                                                <thead class="table-light text-muted">
                                                    <tr>
                                                        <th class="!text-center" style="width:50px">#</th>
                                                        <th>Nama Tindakan</th>
                                                        <th class="!text-right">Biaya</th>
                                                        <th class="!text-center" style="width:60px">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($tindakans as $idx => $tdk)
                                                        <tr>
                                                            <td class="text-center"><span class="text-[#878a99] font-semibold">{{ $idx + 1 }}</span></td>
                                                            <td><span class="font-semibold text-[#495057]">{{ $tdk['nama'] }}</span></td>
                                                            <td class="text-right"><span class="font-bold text-[#405189]">Rp {{ number_format($tdk['biaya'], 0, ',', '.') }}</span></td>
                                                            <td class="text-center">
                                                                <button wire:click="removeItem('tindakans', {{ $idx }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="text-center py-8">
                                                                <div class="opacity-40">
                                                                    <i class="ri-hand-heart-line text-3xl block mb-2"></i>
                                                                    <p class="text-xs font-bold mb-0">Belum ada tindakan medis</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- TAB: Peresepan Obat -->
                                    <div x-show="medicalTab === 'resep'" x-cloak>
                                        <div class="p-4 border-b border-[#eff2f7]">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                                <h6 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-0 flex items-center gap-2">
                                                    <i class="ri-capsule-line text-emerald-500"></i> Peresepan Obat
                                                </h6>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" id="searchResep" wire:model.defer="tempObat" class="h-10 w-full sm:w-52 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari nama obat...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                                                    </div>
                                                    <button wire:click="addResep" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="resepTable" class="table align-middle table-nowrap w-full">
                                                <thead class="table-light text-muted">
                                                    <tr>
                                                        <th class="!text-center" style="width:50px">#</th>
                                                        <th>Nama Obat</th>
                                                        <th class="!text-center" style="width:60px">Qty</th>
                                                        <th>Aturan Pakai</th>
                                                        <th class="!text-center" style="width:60px">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($reseps as $idx => $rsp)
                                                        <tr>
                                                            <td class="text-center"><span class="text-[#878a99] font-semibold">{{ $idx + 1 }}</span></td>
                                                            <td><span class="font-semibold text-[#495057]">{{ $rsp['nama'] }}</span></td>
                                                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $rsp['qty'] }}</span></td>
                                                            <td><span class="text-[#878a99] font-medium">{{ $rsp['signa'] }}</span></td>
                                                            <td class="text-center">
                                                                <button wire:click="removeItem('reseps', {{ $idx }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center py-8">
                                                                <div class="opacity-40">
                                                                    <i class="ri-capsule-line text-3xl block mb-2"></i>
                                                                    <p class="text-xs font-bold mb-0">Belum ada resep obat</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- TAB: BMHP -->
                                    <div x-show="medicalTab === 'bmhp'" x-cloak>
                                        <div class="p-4 border-b border-[#eff2f7]">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                                <h6 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-0 flex items-center gap-2">
                                                    <i class="ri-flask-line text-purple-500"></i> Bahan Medis (BMHP)
                                                </h6>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <div class="relative flex-grow sm:flex-none">
                                                        <input type="text" id="searchBmhp" wire:model.defer="tempBmhp" wire:keydown.enter="addBmhp" class="h-10 w-full sm:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari BMHP / alkes...">
                                                        <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                                                    </div>
                                                    <button wire:click="addBmhp" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                                        <i class="ri-add-line text-lg"></i>
                                                        <span class="font-semibold text-xs uppercase tracking-wider">Tambah</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="bmhpTable" class="table align-middle table-nowrap w-full">
                                                <thead class="table-light text-muted">
                                                    <tr>
                                                        <th class="!text-center" style="width:50px">#</th>
                                                        <th>Bahan Medis (BMHP)</th>
                                                        <th class="!text-center" style="width:60px">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($bmhps as $idx => $bm)
                                                        <tr>
                                                            <td class="text-center"><span class="text-[#878a99] font-semibold">{{ $idx + 1 }}</span></td>
                                                            <td><span class="font-semibold text-[#495057]">{{ $bm['nama'] }}</span></td>
                                                            <td class="text-center">
                                                                <button wire:click="removeItem('bmhps', {{ $idx }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="text-center py-8">
                                                                <div class="opacity-40">
                                                                    <i class="ri-flask-line text-3xl block mb-2"></i>
                                                                    <p class="text-xs font-bold mb-0">Belum ada BMHP terpilih</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
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
            
            <style>
                .scrollbar-hide::-webkit-scrollbar { display: none; }
                .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
                [x-cloak] { display: none !important; }
            </style>
        </div>
        HTML;
    }
}
