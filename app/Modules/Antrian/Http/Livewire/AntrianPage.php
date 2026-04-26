<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use App\Models\TrxAntrian;
use App\Models\MstPasien;
use Carbon\Carbon;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class AntrianPage extends Component
{
    use WithPagination;

    public $selectedDate;
    public $selectedStatus = 'all';
    public $totalAntrian = 0;
    public $menunggu = 0;
    public $dipanggil = 0;
    public $selesai = 0;
    public $batal = 0;
    public $search = '';
    public $viewMode = 'table'; // table or grid

    public $syncAntrianId;
    public $searchPasien = '';
    public $pasienResults = [];
    public $showSyncModal = false;
    public $isSyncForEdit = false; // Flag to check if search is from Edit modal

    // Edit Antrian Modal
    public $editAntrianId;
    public $editNamaPasien, $editPoli, $editDokter, $editTanggal, $editAsuransi, $editNoAsuransi;
    public $poliList = [], $dokterList = [], $asuransiList = [];
    public $showEditModal = false;

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function updatedSelectedDate() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }

    public function prevDate()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        $this->resetPage();
    }

    public function nextDate()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->resetPage();
    }

    public function setStatus($status) 
    { 
        $this->selectedStatus = $status; 
        $this->resetPage();
    }

    public function panggilBerikutnya()
    {
        $next = TrxAntrian::where(fn($q) => $q->where('tanggal_antrian', $this->selectedDate))
            ->where(fn($q) => $q->where('status', 'menunggu'))
            ->orderBy('nomor_antrian')
            ->first();

        if (!$next) {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Tidak ada antrian yang menunggu.']);
            return;
        }

        $next->update([
            'status' => 'dipanggil',
            'waktu_panggil' => now(),
        ]);

        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Memanggil antrian nomor ' . $next->nomor_antrian . ' - ' . ($next->pasien?->nama_pasien ?? $next->nama_pasien_input_manual)]);
    }

    public function ubahStatus($id, $status)
    {
        $antrian = TrxAntrian::findOrFail($id);
        $data = ['status' => $status];
        if ($status === 'hadir') { $data['waktu_hadir'] = now(); }
        if ($status === 'dipanggil') { $data['waktu_panggil'] = now(); }
        $antrian->update($data);
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status antrian diubah menjadi ' . ucfirst(str_replace('_', ' ', $status))]);
    }

    // Sinkronisasi pasien
    public function openSyncModal($antrianId)
    {
        $this->syncAntrianId = $antrianId;
        $this->searchPasien = '';
        $this->pasienResults = [];
        $this->isSyncForEdit = false;
        $this->showSyncModal = true;
        $this->dispatch('refresh-table');
    }

    public function updatedSearchPasien($value)
    {
        if (strlen($value) >= 2) {
            $this->pasienResults = MstPasien::where(function ($q) use ($value) {
                    $q->where('nama_pasien', 'like', '%' . $value . '%')
                      ->orWhere('nik', 'like', '%' . $value . '%')
                      ->orWhere('no_telepon', 'like', '%' . $value . '%')
                      ->orWhere('no_rm', 'like', '%' . $value . '%');
                })
                ->limit(10)
                ->get()
                ->toArray();
        } else {
            $this->pasienResults = [];
        }
    }

    public function pilihPasien($pasienId)
    {
        $pasien = MstPasien::findOrFail($pasienId);

        if ($this->isSyncForEdit) {
            $this->editNamaPasien = $pasien->nama_pasien;
            // Also update the hidden pasien_id for the edit
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Pasien terpilih: ' . $pasien->nama_pasien]);
        } else {
            $antrian = TrxAntrian::findOrFail($this->syncAntrianId);
            $antrian->update(['pasien_id' => $pasien->id]);
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Pasien berhasil disinkronkan: ' . $pasien->nama_pasien . ' (' . $pasien->no_rm . ')']);
        }

        $this->showSyncModal = false;
        $this->dispatch('refresh-table');
    }

    public function daftarkan($antrianId)
    {
        $antrian = TrxAntrian::findOrFail($antrianId);
        return redirect()->route('pendaftaran.create', ['antrian_id' => $antrian->id, 'pasien_id' => $antrian->pasien_id]);
    }

    public function editAntrian($id)
    {
        $antrian = TrxAntrian::findOrFail($id);
        $this->editAntrianId = $antrian->id;
        $this->editNamaPasien = $antrian->pasien?->nama_pasien ?? $antrian->nama_pasien_input_manual;
        $this->editPoli = $antrian->kode_poli;
        $this->editDokter = $antrian->kode_dokter;
        $this->editTanggal = \Carbon\Carbon::parse($antrian->tanggal_antrian)->format('Y-m-d');
        $this->editAsuransi = $antrian->asuransi;
        $this->editNoAsuransi = $antrian->no_asuransi;
        
        $this->poliList = \App\Models\MstPoli::where(fn($q) => $q->where('status', 'Aktif'))->get()->toArray();
        $this->dokterList = \App\Models\MstDokter::where(fn($q) => $q->where('status', 'Aktif'))->get()->toArray();
        $this->asuransiList = \App\Models\MstAsuransi::where(fn($q) => $q->where('status', 'Aktif'))->get()->toArray();
        $this->showEditModal = true;
        
        $this->dispatch('refresh-table');
    }

    public function updateAntrian()
    {
        $antrian = TrxAntrian::findOrFail($this->editAntrianId);
        
        $data = [
            'tanggal_antrian' => $this->editTanggal,
            'kode_poli' => $this->editPoli,
            'kode_dokter' => $this->editDokter,
            'asuransi' => $this->editAsuransi,
            'no_asuransi' => $this->editNoAsuransi,
        ];
        if (!$antrian->pasien_id) {
            $data['nama_pasien_input_manual'] = $this->editNamaPasien;
        }
        
        $antrian->update($data);
        $this->showEditModal = false;
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data antrian berhasil diperbarui.']);
    }

    #[Computed]
    public function antrianList()
    {
        $query = TrxAntrian::with(['pasien', 'poli', 'dokter'])
            ->whereDate('tanggal_antrian', $this->selectedDate)
            ->where('status', '!=', 'batal');
        
        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nomor_antrian', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_pasien_input_manual', 'like', '%' . $this->search . '%')
                  ->orWhereHas('pasien', function($qp) {
                      $qp->where('nama_pasien', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->orderBy('nomor_antrian')->paginate(25);
    }

    #[Computed]
    public function groupedAntrianList()
    {
        $query = TrxAntrian::with(['pasien', 'poli', 'dokter'])
            ->whereDate('tanggal_antrian', $this->selectedDate)
            ->where('status', '!=', 'batal');
        
        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nomor_antrian', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_pasien_input_manual', 'like', '%' . $this->search . '%')
                  ->orWhereHas('pasien', function($qp) {
                      $qp->where('nama_pasien', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $allData = $query->orderBy('nomor_antrian')->get();
        
        $grouped = [];
        foreach($allData as $item) {
            $slot = $item->time_slot ? substr($item->time_slot, 0, 5) : 'Walk-in';
            if (!isset($grouped[$slot])) {
                $grouped[$slot] = [];
            }
            $grouped[$slot][] = $item;
        }
        
        ksort($grouped);
        return $grouped;
    }

    public function render()
    {
        $dayQuery = TrxAntrian::where(fn($q) => $q->where('tanggal_antrian', $this->selectedDate));
        $this->totalAntrian = (clone $dayQuery)->count();
        $this->menunggu = (clone $dayQuery)->where(fn($q) => $q->where('status', 'menunggu'))->count();
        $this->dipanggil = (clone $dayQuery)->where(fn($q) => $q->where('status', 'dipanggil'))->count();
        $this->selesai = (clone $dayQuery)->where(fn($q) => $q->where('status', 'selesai'))->count();
        $this->batal = (clone $dayQuery)->where(fn($q) => $q->where('status', 'batal'))->count();

        return <<<'HTML'
        <div x-data="{ 
            showSyncModal: @entangle('showSyncModal'),
            showEditModal: @entangle('showEditModal')
        }">
            <style>
                .custom-row:hover {
                    background-color: #d8dce1ff !important;
                    transition: all 0.3s ease;
                }
                .pagination-custom nav span.relative.z-0 { 
                    display: flex !important; 
                    gap: 4px !important; 
                    flex-wrap: wrap !important;
                    justify-content: center !important;
                }
                .pagination-custom nav a, 
                .pagination-custom nav span[aria-disabled="true"] span,
                .pagination-custom nav span[aria-current="page"] span {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    min-width: 38px !important;
                    height: 38px !important;
                    padding: 0 12px !important;
                    border-radius: 8px !important;
                    border: 1px solid #767070ff !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    transition: all 0.2s ease-in-out !important;
                    background-color: #ffffff !important;
                    color: #475569 !important;
                    text-decoration: none !important;
                }
                .pagination-custom nav a:hover {
                    background-color: #f1f5f9 !important;
                    border-color: #405189 !important;
                    color: #405189 !important;
                    transform: translateY(-1px) !important;
                }
                .pagination-custom nav p.text-sm {
                    display: none !important;
                }
                .pagination-custom nav > div:last-child > div:first-child {
                    display: none !important;
                }
                .pagination-custom [aria-current="page"], 
                .pagination-custom [aria-current="page"] *,
                .pagination-custom .active,
                .pagination-custom .active * {
                    background-color: #405189 !important;
                    color: #ffffff !important;
                    border-color: #405189 !important;
                    box-shadow: 0 4px 10px rgba(64, 81, 137, 0.3) !important;
                    z-index: 10 !important;
                }
            </style>
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-list-ordered"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Antrian Pasien</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Pantau dan kelola antrian kunjungan pasien secara real-time.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Antrian</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Daftar Antrian</span>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5 mb-8">
                <div class="group relative overflow-hidden bg-white rounded-2xl p-4 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#405189]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-[#405189] group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-list-ordered text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[9px] uppercase tracking-[0.1em]">Total</p>
                            <h4 class="text-xl font-black text-[#2c3e50] leading-none mt-1">{{ number_format($totalAntrian) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-4 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f7b84b]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-[#f7b84b] group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-time-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[9px] uppercase tracking-[0.1em]">Menunggu</p>
                            <h4 class="text-xl font-black text-[#2c3e50] leading-none mt-1 text-[#f7b84b]">{{ number_format($menunggu) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-4 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#3577f1]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#3577f1] group-hover:bg-blue-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-notification-3-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[9px] uppercase tracking-[0.1em]">Dipanggil</p>
                            <h4 class="text-xl font-black text-[#2c3e50] leading-none mt-1 text-[#3577f1]">{{ number_format($dipanggil) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-4 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#0ab39c]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-[#0ab39c] group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-checkbox-circle-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[9px] uppercase tracking-[0.1em]">Selesai</p>
                            <h4 class="text-xl font-black text-[#2c3e50] leading-none mt-1 text-[#0ab39c]">{{ number_format($selesai) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-4 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f06548]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-[#f06548] group-hover:bg-rose-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-close-circle-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[9px] uppercase tracking-[0.1em]">Batal</p>
                            <h4 class="text-xl font-black text-[#2c3e50] leading-none mt-1 text-[#f06548]">{{ number_format($batal) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Calendar Sidebar -->
                <div class="lg:col-span-1">
                    <div class="card shadow-sm border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]"><h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0"><i class="ri-calendar-line mr-1"></i>Pilih Tanggal</h6></div>
                        <div class="p-4">
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
                        </div>
                        <div class="p-4 border-t border-[#eff2f7] space-y-2">
                            <a href="{{ route('antrian.ambil') }}" wire:navigate class="btn btn-primary w-full h-10 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider"><i class="ri-add-circle-line text-lg"></i> Ambil Antrian</a>
                            <button wire:click="panggilBerikutnya" class="btn bg-[#f7b84b] text-white w-full h-10 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider hover:bg-[#e5a93a] transition-all"><i class="ri-notification-3-line text-lg"></i> Panggil Berikutnya</button>
                            <button onclick="openMonitor()" class="btn bg-[#299cdb] text-white w-full h-10 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider hover:bg-[#2089c2] transition-all"><i class="ri-tv-2-line text-lg"></i> Buka Monitor</button>
                        </div>
                    </div>
                </div>

                <!-- Antrian Table -->
                <div class="lg:col-span-3">
                    <div class="card overflow-hidden border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'menunggu' ? 'active active-pill-warning' : '' }}" wire:click="setStatus('menunggu')" role="button"><i class="ri-time-line"></i><span>Menunggu</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'dipanggil' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('dipanggil')" role="button"><i class="ri-notification-3-line"></i><span>Dipanggil</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'hadir' ? 'active active-pill-success' : '' }}" wire:click="setStatus('hadir')" role="button"><i class="ri-user-follow-line"></i><span>Hadir</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'selesai' ? 'active active-pill-success' : '' }}" wire:click="setStatus('selesai')" role="button"><i class="ri-checkbox-circle-line"></i><span>Selesai</span></a></li>
                            </ul></div>
                            
                            <div class="flex items-center gap-3 w-full lg:w-auto">
                                <div class="bg-gray-100 p-1 rounded-xl flex items-center shrink-0">
                                    <button wire:click="$set('viewMode', 'table')" class="w-9 h-8 flex items-center justify-center rounded-lg transition-all {{ $viewMode === 'table' ? 'bg-white shadow-sm text-[#405189]' : 'text-gray-500 hover:text-gray-700' }}" title="Tampilan Tabel">
                                        <i class="ri-list-check"></i>
                                    </button>
                                    <button wire:click="$set('viewMode', 'grid')" class="w-9 h-8 flex items-center justify-center rounded-lg transition-all {{ $viewMode === 'grid' ? 'bg-white shadow-sm text-[#405189]' : 'text-gray-500 hover:text-gray-700' }}" title="Tampilan Grup Waktu">
                                        <i class="ri-grid-fill"></i>
                                    </button>
                                </div>
                                <div class="relative flex-grow max-w-[320px] lg:w-80">
                                    <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full bg-gray-50 border border-gray-200 rounded-2xl py-2 pl-11 pr-4 text-sm font-medium outline-none transition-all focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 placeholder:text-gray-300" placeholder="Cari pasien atau nomor...">
                                </div>
                            </div>
                        </div></div>
                        @if($viewMode === 'table')
                        <div class="card-body p-0"><div class="overflow-x-auto dark:bg-transparent">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Waktu</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pasien</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Poli</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($this->antrianList as $item)
                                    <tr wire:key="antrian-{{ $item->id }}" class="custom-row transition-all duration-200 {{ $item->status === 'dipanggil' ? 'bg-blue-50/50 dark:bg-blue-900/20' : ($item->status === 'hadir' ? 'bg-green-50/50 dark:bg-green-900/20' : ($item->status === 'tidak_hadir' ? 'bg-red-50/50 dark:bg-red-900/20' : 'bg-transparent')) }}">
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center justify-center min-w-[32px] px-2 h-8 rounded-lg bg-[#405189] text-white font-bold text-sm shadow-sm">{{ $item->nomor_antrian }}</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($item->time_slot)
                                                <span class="text-xs font-bold text-[#0ab39c]"><i class="ri-time-line mr-1 opacity-70"></i>{{ substr($item->time_slot, 0, 5) }}</span>
                                            @else
                                                <span class="text-xs text-gray-300">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 min-w-[200px]">
                                            <div class="font-bold text-[#2c3e50] text-sm">{{ $item->pasien?->nama_pasien ?? $item->nama_pasien_input_manual ?? '-' }}</div>
                                            <div class="flex items-center gap-2 mt-1">
                                                @if($item->pasien_id)
                                                    <span class="text-[11px] font-mono text-gray-400">{{ $item->pasien?->no_rm }}</span>
                                                @else
                                                    <span class="text-[10px] text-orange-500 font-bold inline-block"><i class="ri-alert-line mr-0.5"></i>Belum sinkron</span>
                                                @endif
                                                @if($item->jenis_antrian === 'online')
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-info-subtle text-info border border-info/10">{{ $item->jenis_antrian }}</span>
                                                @elseif($item->jenis_antrian === 'mobile_jkn')
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-purple-100 text-purple-600 border border-purple-200">Mobile JKN</span>
                                                @else
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-gray-100 text-gray-500 border border-gray-200">{{ $item->jenis_antrian }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-[#495057] text-sm">{{ $item->poli?->nama_poli ?? $item->kode_poli ?? '-' }}</div>
                                            <div class="text-[10px] text-gray-400 font-medium italic mt-0.5"><i class="ri-user-star-line mr-0.5"></i> {{ $item->dokter?->nama_dokter ?? $item->kode_dokter ?? 'Belum ada dokter' }}</div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php $statusColors = ['menunggu'=>'bg-warning-subtle text-amber-600','dipanggil'=>'bg-primary-subtle text-[#405189]','hadir'=>'bg-success-subtle text-emerald-600','tidak_hadir'=>'bg-danger-subtle text-rose-600','batal'=>'bg-secondary-subtle text-gray-600','selesai'=>'bg-success-subtle text-emerald-600']; @endphp
                                            <span class="px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center w-max gap-1.5 {{ $statusColors[$item->status] ?? 'bg-secondary-subtle' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ str_contains($statusColors[$item->status] ?? '', 'amber') ? 'bg-amber-500' : (str_contains($statusColors[$item->status] ?? '', '#405189') ? 'bg-[#405189] animate-ping' : 'bg-current') }}"></span>
                                                {{ ucfirst(str_replace('_',' ',$item->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('antrian.cetak', $item->id) }}" target="_blank" class="w-8 h-8 rounded-full flex items-center justify-center bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white transition-all shadow-sm" title="Cetak Tiket"><i class="ri-printer-line"></i></a>
                                                @if(in_array($item->status, ['menunggu','dipanggil']))
                                                    <button wire:click="editAntrian({{ $item->id }})" class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white transition-all shadow-sm" title="Edit Antrian"><i class="ri-edit-line"></i></button>
                                                @endif
                                                @if($item->status === 'menunggu')
                                                    <button wire:click="ubahStatus({{ $item->id }}, 'dipanggil')" class="w-8 h-8 rounded-full flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white transition-all shadow-sm" title="Panggil"><i class="ri-notification-3-line"></i></button>
                                                @endif
                                                @if($item->status === 'dipanggil')
                                                    <button wire:click="ubahStatus({{ $item->id }}, 'hadir')" class="w-8 h-8 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all shadow-sm" title="Hadir"><i class="ri-user-follow-line"></i></button>
                                                    <button wire:click="ubahStatus({{ $item->id }}, 'tidak_hadir')" class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Tidak Hadir"><i class="ri-user-unfollow-line"></i></button>
                                                @endif
                                                @if(!$item->pasien_id && in_array($item->status, ['menunggu','dipanggil','hadir']))
                                                    <button wire:click="openSyncModal({{ $item->id }})" class="flex h-8 px-3 rounded-full items-center justify-center bg-purple-50 text-purple-600 hover:bg-purple-600 hover:text-white transition-all shadow-sm text-xs font-bold gap-1" title="Sinkron Pasien"><i class="ri-link"></i> Sinkron</button>
                                                @endif
                                                @if(in_array($item->status, ['hadir','dipanggil']))
                                                    <button wire:click="daftarkan({{ $item->id }})" class="flex h-8 px-3 rounded-full items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm text-xs font-bold gap-1" title="Daftarkan"><i class="ri-file-add-line"></i> Daftar</button>
                                                @endif
                                                @if(in_array($item->status, ['menunggu','dipanggil']))
                                                    <button @click="Swal.fire({title:'Batalkan Antrian?',text:'Antrian ini akan dibatalkan.',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Batalkan!',cancelButtonText:'Tidak',reverseButtons:true}).then(r=>{if(r.isConfirmed)$wire.ubahStatus({{ $item->id }},'batal')})" class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Batal"><i class="ri-close-line"></i></button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="py-16 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="ri-list-ordered text-4xl text-gray-300"></i>
                                                </div>
                                                <p class="text-base font-bold text-gray-500">Belum ada data antrian</p>
                                                <p class="text-xs text-gray-400 mt-1">Belum ada pasien yang mendaftar pada tanggal ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if($this->antrianList->hasPages())
                            <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                                    <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                                        <span class="hidden sm:inline">Menampilkan</span> 
                                        <span class="text-[#405189] font-black">{{ $this->antrianList->firstItem() }} - {{ $this->antrianList->lastItem() }}</span> 
                                        dari <span class="text-[#405189] font-black">{{ number_format($this->antrianList->total()) }}</span> 
                                        <span class="hidden sm:inline">antrian</span>
                                    </div>
                                    {{ $this->antrianList->links() }}
                                </div>
                            </div>
                            @endif
                        </div></div>
                        @else
                        <div class="p-6 bg-gray-50/50 min-h-[400px]">
                            @php $groupedData = $this->groupedAntrianList; @endphp
                            
                            @if(count($groupedData) === 0)
                                <div class="flex flex-col items-center justify-center py-16">
                                    <div class="w-20 h-20 bg-white shadow-sm rounded-full flex items-center justify-center mb-4">
                                        <i class="ri-list-ordered text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-base font-bold text-gray-500">Belum ada data antrian</p>
                                    <p class="text-xs text-gray-400 mt-1">Gunakan filter pencarian atau ubah tanggal.</p>
                                </div>
                            @else
                                <div class="space-y-10">
                                    @foreach($groupedData as $slot => $items)
                                        <div>
                                            <div class="flex items-center gap-4 mb-5">
                                                <div class="h-9 px-5 rounded-full bg-white border-2 border-[#405189] text-[#405189] flex items-center justify-center font-bold text-sm shadow-sm">
                                                    @if($slot === 'Walk-in')
                                                        <i class="ri-walk-line mr-2 text-lg"></i> Walk-in (Tanpa Waktu)
                                                    @else
                                                        <i class="ri-time-line mr-2 text-lg"></i> {{ $slot }} WIB
                                                    @endif
                                                </div>
                                                <div class="h-px bg-gray-200 flex-1"></div>
                                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest bg-white px-3 py-1 rounded-md border border-gray-100 shadow-sm">{{ count($items) }} Pasien</div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                                                @foreach($items as $item)
                                                     <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-lg transition-all duration-300 group relative overflow-hidden flex flex-col" style="{{ $item->dokter?->color ? 'background-color: ' . $item->dokter->color . '08;' : '' }}">
                                                          @php 
                                                             $statusColorsList = ['menunggu'=>'bg-warning-subtle text-amber-600','dipanggil'=>'bg-primary-subtle text-[#405189]','hadir'=>'bg-success-subtle text-emerald-600','tidak_hadir'=>'bg-danger-subtle text-rose-600','batal'=>'bg-secondary-subtle text-gray-600','selesai'=>'bg-success-subtle text-emerald-600']; 
                                                             $borderColors = ['menunggu'=>'bg-amber-500','dipanggil'=>'bg-[#405189]','hadir'=>'bg-emerald-500','tidak_hadir'=>'bg-rose-500','batal'=>'bg-gray-400','selesai'=>'bg-emerald-500'];
                                                             $doctorColor = $item->dokter?->color ?? '#405189';
                                                          @endphp
                                                          <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background-color: {{ $doctorColor }}"></div>
                                                          
                                                          <div class="flex justify-between items-start mb-3 pl-2">
                                                               <div class="flex items-center gap-2">
                                                                  <span class="inline-flex items-center justify-center h-8 px-2.5 rounded-lg bg-gray-100 text-gray-700 font-bold text-sm">{{ $item->nomor_antrian }}</span>
                                                                  @if($item->jenis_antrian === 'online')
                                                                      <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-widest bg-info-subtle text-info border border-info/10"><i class="ri-global-line mr-0.5"></i>Online</span>
                                                                  @elseif($item->jenis_antrian === 'mobile_jkn')
                                                                      <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-widest bg-purple-100 text-purple-600 border border-purple-200"><i class="ri-smartphone-line mr-0.5"></i>JKN</span>
                                                                  @else
                                                                      <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-widest bg-gray-100 text-gray-500 border border-gray-200">Offline</span>
                                                                  @endif
                                                               </div>
                                                               <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold flex items-center gap-1.5 {{ $statusColorsList[$item->status] ?? 'bg-secondary-subtle' }}">
                                                                   <span class="h-1.5 w-1.5 rounded-full {{ str_contains($borderColors[$item->status] ?? '', '#405189') ? 'bg-current animate-ping' : 'bg-current' }}"></span>
                                                                   {{ ucfirst(str_replace('_',' ',$item->status)) }}
                                                               </span>
                                                          </div>
                                                          <div class="mb-4 pl-2 flex-grow">
                                                               <h5 class="text-base font-black text-[#2c3e50] mb-1 leading-tight">{{ $item->pasien?->nama_pasien ?? $item->nama_pasien_input_manual ?? '-' }}</h5>
                                                               @if($item->pasien_id)
                                                                 <div class="text-[11px] font-mono text-gray-400">RM: {{ $item->pasien?->no_rm }}</div>
                                                               @else
                                                                 <div class="text-[10px] text-orange-500 font-bold"><i class="ri-alert-line mr-0.5"></i>Belum sinkron</div>
                                                               @endif
                                                          </div>
                                                          
                                                          <div class="pt-3 border-t border-gray-50 pl-2">
                                                               <div class="flex items-center gap-3">
                                                                   <div class="h-8 w-8 rounded-full flex items-center justify-center shrink-0" style="background-color: {{ $doctorColor }}15; color: {{ $doctorColor }}">
                                                                       <i class="ri-stethoscope-line text-lg"></i>
                                                                   </div>
                                                                   <div>
                                                                       <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $item->poli?->nama_poli ?? $item->kode_poli ?? '-' }}</p>
                                                                       <p class="text-xs font-semibold" style="color: {{ $doctorColor }}">{{ $item->dokter?->nama_dokter ?? 'Belum terhubung dokter' }}</p>
                                                                   </div>
                                                               </div>
                                                          </div>
                                                          
                                                          <!-- Hover Actions Overlay -->
                                                          <div class="absolute inset-0 bg-white rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2 z-10">
                                                              <a href="{{ route('antrian.cetak', $item->id) }}" target="_blank" class="w-10 h-10 rounded-full flex items-center justify-center bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Cetak Tiket"><i class="ri-printer-line text-lg"></i></a>
                                                              @if(in_array($item->status, ['menunggu','dipanggil']))
                                                                  <button wire:click="editAntrian({{ $item->id }})" class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Edit"><i class="ri-edit-line text-lg"></i></button>
                                                              @endif
                                                              @if($item->status === 'menunggu')
                                                                  <button wire:click="ubahStatus({{ $item->id }}, 'dipanggil')" class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Panggil"><i class="ri-notification-3-line text-lg"></i></button>
                                                              @endif
                                                              @if($item->status === 'dipanggil')
                                                                  <button wire:click="ubahStatus({{ $item->id }}, 'hadir')" class="w-10 h-10 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-500 hover:bg-emerald-500 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Hadir"><i class="ri-user-follow-line text-lg"></i></button>
                                                                  <button wire:click="ubahStatus({{ $item->id }}, 'tidak_hadir')" class="w-10 h-10 rounded-full flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Tidak Hadir"><i class="ri-user-unfollow-line text-lg"></i></button>
                                                              @endif
                                                              @if(!$item->pasien_id && in_array($item->status, ['menunggu','dipanggil','hadir']))
                                                                  <button wire:click="openSyncModal({{ $item->id }})" class="w-10 h-10 rounded-full flex items-center justify-center bg-purple-50 text-purple-600 hover:bg-purple-600 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Sinkron Pasien"><i class="ri-link text-lg"></i></button>
                                                              @endif
                                                              @if(in_array($item->status, ['hadir','dipanggil']))
                                                                  <button wire:click="daftarkan({{ $item->id }})" class="w-10 h-10 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Daftarkan"><i class="ri-file-add-line text-lg"></i></button>
                                                              @endif
                                                         </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sync Pasien Modal -->
            <div x-show="showSyncModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display:none;">
                <div x-show="showSyncModal" x-transition.scale.95 class="w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]"><i class="ri-link mr-2 text-[#405189]"></i>Sinkronisasi Pasien</h5><button @click="showSyncModal=false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-6 py-5">
                        <div class="relative mb-4"><input type="text" wire:model.live.debounce.300ms="searchPasien" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Cari berdasarkan Nama, NIK, No HP, atau No RM..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99]"></i></div>
                        <div class="max-h-[300px] overflow-y-auto space-y-2">
                            @forelse($pasienResults as $p)
                            <button wire:key="psearch-{{ $p['id'] }}" wire:click="pilihPasien({{ $p['id'] }})" class="w-full text-left p-3 rounded-lg border border-gray-100 hover:border-[#405189] hover:bg-[#405189]/5 transition-all group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-[#495057] text-sm group-hover:text-[#405189]">{{ $p['nama_pasien'] }}</p>
                                        <p class="text-[11px] text-[#878a99]">{{ $p['no_rm'] }} · NIK: {{ $p['nik'] ?? '-' }} · {{ $p['no_telepon'] ?? '-' }}</p>
                                    </div>
                                    <i class="ri-arrow-right-s-line text-gray-300 group-hover:text-[#405189] text-xl"></i>
                                </div>
                            </button>
                            @empty
                            @if(strlen($searchPasien) >= 2)
                            <div class="text-center py-6 text-[#878a99]"><i class="ri-user-search-line text-3xl mb-2 block"></i><p class="text-sm">Tidak ada pasien ditemukan</p></div>
                            @else
                            <div class="text-center py-6 text-[#878a99]"><i class="ri-search-eye-line text-3xl mb-2 block"></i><p class="text-sm">Ketik minimal 2 karakter untuk mencari</p></div>
                            @endif
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Antrian Modal -->
            <div x-show="showEditModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display:none;">
                <div x-show="showEditModal" x-transition.scale.95 class="w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]"><i class="ri-edit-line mr-2 text-[#405189]"></i>Edit Antrian (Kiosk)</h5><button @click="showEditModal=false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="updateAntrian">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Pasien <span class="text-red-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="text" wire:model="editNamaPasien" class="flex-1 rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nama pasien">
                                        <button type="button" @click="$wire.set('isSyncForEdit', true); $wire.set('searchPasien', ''); $wire.set('pasienResults', []); showSyncModal = true" class="btn bg-[#299cdb] text-white h-[42px] px-3 flex items-center gap-1 text-[10px] font-bold whitespace-nowrap"><i class="ri-search-2-line"></i> CARI PASIEN</button>
                                    </div>
                                    @error('editNamaPasien') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Poli Tujuan</label>
                                    <select wire:model="editPoli" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all">
                                        <option value="">-- Pilih Poli --</option>
                                        @foreach($poliList as $p)
                                            <option value="{{ $p['kode_poli'] }}">{{ $p['nama_poli'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Dokter</label>
                                    <select wire:model="editDokter" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all">
                                        <option value="">-- Kosongkan / Belum Memilih --</option>
                                        @foreach($dokterList as $d)
                                            <option value="{{ $d['kode_dokter'] }}">{{ $d['nama_dokter'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Antrian <span class="text-red-500">*</span></label>
                                        <input type="date" wire:model="editTanggal" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Asuransi</label>
                                        <select wire:model="editAsuransi" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all">
                                            <option value="">-- Tanpa Asuransi / Umum --</option>
                                            @foreach($asuransiList as $a)
                                                <option value="{{ $a['nama_asuransi'] }}">{{ $a['nama_asuransi'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">No Asuransi</label>
                                    <input type="text" wire:model="editNoAsuransi" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nomor kartu asuransi">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showEditModal = false" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Batal</button>
                        <button type="button" wire:click="updateAntrian" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7]"><i class="ri-save-line"></i> Simpan</button>
                    </div>
                </div>
            </div>

            <script>
            function openMonitor() {
                const monitorUrl = '{{ route("antrian.monitor") }}';
                const monitorWindow = window.open(monitorUrl, 'AntrianMonitor', 'width=1200,height=800,scrollbars=no,resizable=yes');
                if (!monitorWindow || monitorWindow.closed) {
                    Swal.fire({title:'Monitor Antrian',text:'Pop-up terblokir oleh browser. Izinkan pop-up untuk menampilkan monitor antrian.',icon:'warning',confirmButtonColor:'#405189'});
                }
            }
            </script>
        </div>
        HTML;
    }
}
