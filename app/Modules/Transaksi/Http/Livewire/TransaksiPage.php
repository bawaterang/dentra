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
            initDataTable() { 
                const t='#patientTable'; 
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} 
                $(t).DataTable({scrollX:false,dom:'rtp',pageLength:10,language:{zeroRecords:'Tidak ada pasien',emptyTable:'Belum ada pendaftaran',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}});
            },
            init(){ $nextTick(()=>this.initDataTable()) }
        }" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            
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


                            <!-- DIAGNOSIS -->
                            <div class="card shadow-sm border-t-2 border-orange-400">
                                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-orange-50/20">
                                    <h6 class="text-xs font-black text-orange-600 uppercase tracking-widest mb-0">
                                        <i class="ri-microscope-line mr-1"></i> Diagnosis Pasien
                                    </h6>
                                    <button class="text-[10px] uppercase font-black text-orange-600 hover:underline"><i class="ri-search-eye-line"></i> ICD-10 Library</button>
                                </div>
                                <div class="p-4">
                                    <div class="flex gap-2 mb-4 bg-gray-50 p-2 rounded-2xl border border-gray-100">
                                        <div class="relative flex-1">
                                            <input type="text" wire:model.defer="tempDiagnosis" wire:keydown.enter="addDiagnosis" class="form-control text-xs h-10 rounded-xl border-none pl-10 bg-white shadow-sm focus:ring-2 focus:ring-orange-200 transition-all font-bold" placeholder="Cari Kode ICD-10...">
                                            <i class="ri-search-2-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        <button wire:click="addDiagnosis" class="btn bg-orange-500 text-white px-4 rounded-xl font-black shadow-lg shadow-orange-200 flex items-center gap-2 hover:translate-y-[-2px] transition-all"><i class="ri-add-line text-lg"></i> <span class="hidden sm:inline">TAMBAH</span></button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <thead class="bg-orange-50 text-orange-600">
                                                <tr>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-l-lg p-2">#</th>
                                                    <th class="text-[9px] font-black uppercase p-2">Diagnosis / ICD-10</th>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-r-lg p-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @forelse($diagnoses as $idx => $diag)
                                                    <tr>
                                                        <td class="text-center font-bold text-gray-400 text-[10px]">{{ $idx + 1 }}</td>
                                                        <td>
                                                            <div class="flex items-center gap-2 font-black">
                                                                <span class="text-xs text-gray-700">{{ $diag['nama'] }}</span>
                                                                <span class="px-1.5 px-0.5 rounded bg-orange-100 text-orange-600 text-[8px] font-mono leading-tight">{{ $diag['kode'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <button wire:click="removeItem('diagnoses', {{ $idx }})" class="h-8 w-8 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all"><i class="ri-delete-bin-line"></i></button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center py-10 opacity-30 italic font-bold text-xs">Belum ada diagnosis terpilih</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- TINDAKAN DOKTER -->
                            <div class="card shadow-sm border-t-2 border-blue-400">
                                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-blue-50/20">
                                    <h6 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-0">
                                        <i class="ri-hand-heart-line mr-1"></i> Tindakan Medis
                                    </h6>
                                </div>
                                <div class="p-4">
                                    <div class="flex gap-2 mb-4 bg-gray-50 p-2 rounded-2xl border border-gray-100">
                                        <div class="relative flex-1">
                                            <input type="text" wire:model.defer="tempTindakan" wire:keydown.enter="addTindakan" class="form-control text-xs h-10 rounded-xl border-none pl-10 bg-white shadow-sm focus:ring-2 focus:ring-blue-200 transition-all font-bold" placeholder="Cari Tindakan / Layanan...">
                                            <i class="ri-service-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        <button wire:click="addTindakan" class="btn bg-blue-500 text-white px-4 rounded-xl font-black shadow-lg shadow-blue-200 flex items-center gap-2 hover:translate-y-[-2px] transition-all"><i class="ri-add-line text-lg"></i> <span class="hidden sm:inline">TAMBAH</span></button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <thead class="bg-blue-50 text-blue-600">
                                                <tr>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-l-lg p-2">#</th>
                                                    <th class="text-[9px] font-black uppercase p-2">Nama Tindakan</th>
                                                    <th class="text-[9px] font-black uppercase text-right p-2">Biaya</th>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-r-lg p-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @forelse($tindakans as $idx => $tdk)
                                                    <tr>
                                                        <td class="text-center font-bold text-gray-400 text-[10px]">{{ $idx + 1 }}</td>
                                                        <td><span class="text-xs font-black text-gray-700">{{ $tdk['nama'] }}</span></td>
                                                        <td class="text-right"><span class="text-xs font-black text-blue-600">Rp {{ number_format($tdk['biaya'], 0, ',', '.') }}</span></td>
                                                        <td class="text-center">
                                                            <button wire:click="removeItem('tindakans', {{ $idx }})" class="h-8 w-8 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all"><i class="ri-delete-bin-line"></i></button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center py-10 opacity-30 italic font-bold text-xs">Belum ada tindakan medis</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- PERESEPAN OBAT -->
                            <div class="card shadow-sm border-t-2 border-emerald-400">
                                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-emerald-50/20">
                                    <h6 class="text-xs font-black text-emerald-600 uppercase tracking-widest mb-0">
                                        <i class="ri-capsule-line mr-1"></i> Peresepan Obat
                                    </h6>
                                </div>
                                <div class="p-4">
                                    <div class="flex flex-wrap gap-2 mb-4 bg-gray-50 p-2 rounded-2xl border border-gray-100">
                                        <div class="relative flex-1 min-w-[200px]">
                                            <input type="text" wire:model.defer="tempObat" class="form-control text-xs h-10 rounded-xl border-none pl-10 bg-white shadow-sm focus:ring-2 focus:ring-emerald-200 transition-all font-bold" placeholder="Cari Nama Obat...">
                                            <i class="ri-capsule-fill absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        <div class="w-20">
                                            <input type="number" wire:model.defer="tempQty" class="form-control text-xs h-10 rounded-xl border-none text-center bg-white shadow-sm focus:ring-2 focus:ring-emerald-200 font-black" placeholder="Qty">
                                        </div>
                                        <button wire:click="addResep" class="btn bg-emerald-500 text-white px-4 rounded-xl font-black shadow-lg shadow-emerald-200 flex items-center gap-2 hover:translate-y-[-2px] transition-all"><i class="ri-add-line text-lg"></i> <span class="hidden sm:inline">TAMBAH</span></button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <thead class="bg-emerald-50 text-emerald-600">
                                                <tr>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-l-lg p-2">#</th>
                                                    <th class="text-[9px] font-black uppercase p-2">Nama Obat</th>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 p-2">Qty</th>
                                                    <th class="text-[9px] font-black uppercase p-2">Aturan Pakai</th>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-r-lg p-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @forelse($reseps as $idx => $rsp)
                                                    <tr>
                                                        <td class="text-center font-bold text-gray-400 text-[10px]">{{ $idx + 1 }}</td>
                                                        <td><span class="text-xs font-black text-gray-700">{{ $rsp['nama'] }}</span></td>
                                                        <td class="text-center"><span class="badge bg-emerald-100 text-emerald-600 font-black rounded-lg px-2 py-1 text-[10px]">{{ $rsp['qty'] }}</span></td>
                                                        <td><span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $rsp['signa'] }}</span></td>
                                                        <td class="text-center">
                                                            <button wire:click="removeItem('reseps', {{ $idx }})" class="h-8 w-8 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all"><i class="ri-delete-bin-line"></i></button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center py-10 opacity-30 italic font-bold text-xs">Belum ada resep obat</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- BMHP -->
                            <div class="card shadow-sm border-t-2 border-purple-400">
                                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-purple-50/20">
                                    <h6 class="text-xs font-black text-purple-600 uppercase tracking-widest mb-0">
                                        <i class="ri-flask-line mr-1"></i> Bahan Medis (BMHP)
                                    </h6>
                                </div>
                                <div class="p-4">
                                    <div class="flex gap-2 mb-4 bg-gray-50 p-2 rounded-2xl border border-gray-100">
                                        <div class="relative flex-1">
                                            <input type="text" wire:model.defer="tempBmhp" wire:keydown.enter="addBmhp" class="form-control text-xs h-10 rounded-xl border-none pl-10 bg-white shadow-sm focus:ring-2 focus:ring-purple-200 transition-all font-bold" placeholder="Cari BMHP / Alkes...">
                                            <i class="ri-flask-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        <button wire:click="addBmhp" class="btn bg-purple-500 text-white px-4 rounded-xl font-black shadow-lg shadow-purple-200 flex items-center gap-2 hover:translate-y-[-2px] transition-all"><i class="ri-add-line text-lg"></i> <span class="hidden sm:inline">TAMBAH</span></button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <thead class="bg-purple-50 text-purple-600">
                                                <tr>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-l-lg p-2">#</th>
                                                    <th class="text-[9px] font-black uppercase p-2">Bahan Medis (BMHP)</th>
                                                    <th class="text-[9px] font-black uppercase text-center w-12 rounded-r-lg p-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @forelse($bmhps as $idx => $bm)
                                                    <tr>
                                                        <td class="text-center font-bold text-gray-400 text-[10px]">{{ $idx + 1 }}</td>
                                                        <td><span class="text-xs font-black text-gray-700">{{ $bm['nama'] }}</span></td>
                                                        <td class="text-center">
                                                            <button wire:click="removeItem('bmhps', {{ $idx }})" class="h-8 w-8 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all"><i class="ri-delete-bin-line"></i></button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center py-10 opacity-30 italic font-bold text-xs">Belum ada BMHP terpilih</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="md:col-span-2 flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button class="btn btn-light px-6 font-bold text-[#878a99] h-11 border-gray-200 shadow-sm"><i class="ri-history-line mr-1"></i> Lihat Rekam Medis Lama</button>
                                <button class="btn bg-[#0ab39c] text-white px-8 font-black uppercase tracking-widest h-11 shadow-lg shadow-[#0ab39c]/20 flex items-center gap-2 hover:translate-y-[-2px] transition-all"><i class="ri-save-line text-lg"></i> Simpan Transaksi Layanan</button>
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
