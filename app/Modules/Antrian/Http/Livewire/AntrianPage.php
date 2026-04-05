<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use App\Models\TrxAntrian;
use App\Models\MstPasien;
use Carbon\Carbon;

class AntrianPage extends Component
{
    public $selectedDate;
    public $selectedStatus = 'all';
    public $antrianList = [];
    public $totalAntrian = 0;
    public $menunggu = 0;
    public $dipanggil = 0;
    public $selesai = 0;

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

    public function updatedSelectedDate()
    {
        $this->dispatch('refresh-table');
    }

    public function setStatus($status) 
    { 
        $this->selectedStatus = $status; 
        $this->dispatch('refresh-table'); 
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
        $this->editTanggal = $antrian->tanggal_antrian->format('Y-m-d');
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

    public function render()
    {
        $query = TrxAntrian::with(['pasien', 'poli', 'dokter'])->where(fn($q) => $q->where('tanggal_antrian', $this->selectedDate));
        
        if ($this->selectedStatus !== 'all') {
            $query->where(fn($q) => $q->where('status', $this->selectedStatus));
        }

        $this->antrianList = $query->orderBy('nomor_antrian')->get();
        
        $dayQuery = TrxAntrian::where(fn($q) => $q->where('tanggal_antrian', $this->selectedDate));
        $this->totalAntrian = (clone $dayQuery)->count();
        $this->menunggu = (clone $dayQuery)->where(fn($q) => $q->where('status', 'menunggu'))->count();
        $this->dipanggil = (clone $dayQuery)->where(fn($q) => $q->where('status', 'dipanggil'))->count();
        $this->selesai = (clone $dayQuery)->where(fn($q) => $q->where('status', 'selesai'))->count();

        return <<<'HTML'
        <div x-data="{ 
            showSyncModal: @entangle('showSyncModal'),
            showEditModal: @entangle('showEditModal'),
            initDataTable() { 
                const t='#antrianTable'; 
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} 
                $(t).DataTable({scrollX:false,dom:'lrtip',pageLength:25,language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Tidak ada data',zeroRecords:'Tidak ada antrian',emptyTable:'Belum ada antrian untuk tanggal ini',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}});
            },
            init(){ $nextTick(()=>this.initDataTable()) }
        }" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-list-ordered"></i></div><h1>Antrian</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a><span class="sep">/</span><span>Antrian</span></div></div>

            <!-- Info Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;"><div class="flex items-center p-4 gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-lg bg-info-subtle text-info"><i class="ri-list-ordered text-xl"></i></div><div><p class="mb-0.5 text-[#878a99] font-medium text-[10px] uppercase tracking-wider">Total</p><h4 class="mb-0 font-bold text-xl text-[#495057]">{{ $totalAntrian }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f7b84b;"><div class="flex items-center p-4 gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-lg bg-warning-subtle text-warning"><i class="ri-time-line text-xl"></i></div><div><p class="mb-0.5 text-[#878a99] font-medium text-[10px] uppercase tracking-wider">Menunggu</p><h4 class="mb-0 font-bold text-xl text-[#495057]">{{ $menunggu }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #3577f1;"><div class="flex items-center p-4 gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-subtle text-primary"><i class="ri-notification-3-line text-xl"></i></div><div><p class="mb-0.5 text-[#878a99] font-medium text-[10px] uppercase tracking-wider">Dipanggil</p><h4 class="mb-0 font-bold text-xl text-[#495057]">{{ $dipanggil }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;"><div class="flex items-center p-4 gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-lg bg-success-subtle text-success"><i class="ri-checkbox-circle-line text-xl"></i></div><div><p class="mb-0.5 text-[#878a99] font-medium text-[10px] uppercase tracking-wider">Selesai</p><h4 class="mb-0 font-bold text-xl text-[#495057]">{{ $selesai }}</h4></div></div></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Calendar Sidebar -->
                <div class="lg:col-span-1">
                    <div class="card shadow-sm border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]"><h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0"><i class="ri-calendar-line mr-1"></i>Pilih Tanggal</h6></div>
                        <div class="p-4">
                            <input type="date" wire:model.live="selectedDate" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all text-center font-semibold">
                            <div class="mt-3 text-center">
                                <p class="text-xs text-[#878a99]">Tanggal dipilih:</p>
                                <p class="font-bold text-[#405189] text-sm">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
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
                        <div class="p-4 border-b border-[#eff2f7] dark:border-white/10 dark:bg-transparent"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'menunggu' ? 'active active-pill-warning' : '' }}" wire:click="setStatus('menunggu')" role="button"><i class="ri-time-line"></i><span>Menunggu</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'dipanggil' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('dipanggil')" role="button"><i class="ri-notification-3-line"></i><span>Dipanggil</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'hadir' ? 'active active-pill-success' : '' }}" wire:click="setStatus('hadir')" role="button"><i class="ri-user-follow-line"></i><span>Hadir</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'selesai' ? 'active active-pill-success' : '' }}" wire:click="setStatus('selesai')" role="button"><i class="ri-checkbox-circle-line"></i><span>Selesai</span></a></li>
                            </ul></div>
                        </div></div>
                        <div class="card-body p-0"><div class="table-responsive dark:bg-transparent">
                            <table id="antrianTable" class="table align-middle table-nowrap w-full">
                            <thead class="table-light text-muted"><tr><th width="8%">No</th><th>No RM</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Jenis</th><th>Status</th><th class="!text-center" style="text-align:center!important;">Aksi</th></tr></thead>
                            <tbody>
                                @foreach($antrianList as $item)
                                <tr wire:key="antrian-{{ $item->id }}" class="{{ $item->status === 'dipanggil' ? 'bg-blue-50 dark:bg-blue-900/20' : ($item->status === 'hadir' ? 'bg-green-50 dark:bg-green-900/20' : ($item->status === 'tidak_hadir' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-transparent')) }}">
                                    <td><span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-[#405189] text-white font-bold text-sm">{{ $item->nomor_antrian }}</span></td>
                                    <td><span class="font-mono text-sm font-semibold text-[#405189] dark:text-[#8ab4f8]">{{ $item->pasien?->no_rm ?? '-' }}</span></td>
                                    <td>
                                        <div class="font-semibold text-[#495057] dark:text-[#000000]">{{ $item->pasien?->nama_pasien ?? $item->nama_pasien_input_manual ?? '-' }}</div>
                                        @if(!$item->pasien_id)<span class="text-[10px] text-orange-500 font-medium"><i class="ri-alert-line mr-0.5"></i>Belum sinkron</span>@endif
                                    </td>
                                    <td>{{ $item->poli?->nama_poli ?? $item->kode_poli ?? '-' }}</td>
                                    <td>{{ $item->dokter?->nama_dokter ?? $item->kode_dokter ?? '-' }}</td>
                                    <td><span class="badge {{ $item->jenis_antrian === 'online' ? 'bg-info-subtle' : 'bg-secondary-subtle' }}">{{ ucfirst($item->jenis_antrian) }}</span></td>
                                    <td>
                                        @php $statusColors = ['menunggu'=>'bg-warning-subtle','dipanggil'=>'bg-primary-subtle','hadir'=>'bg-success-subtle','tidak_hadir'=>'bg-danger-subtle','batal'=>'bg-secondary-subtle','selesai'=>'bg-success-subtle']; @endphp
                                        <span class="badge {{ $statusColors[$item->status] ?? 'bg-secondary-subtle' }}">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-1">
                                            <a href="{{ route('antrian.cetak', $item->id) }}" target="_blank" class="flex h-7 px-2 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all text-[10px] font-bold gap-1" title="Cetak Tiket"><i class="ri-printer-line"></i></a>
                                            @if(in_array($item->status, ['menunggu','dipanggil']))
                                                <button wire:click="editAntrian({{ $item->id }})" class="flex h-7 px-2 items-center justify-center rounded bg-orange-100 text-orange-600 hover:bg-orange-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Edit Antrian"><i class="ri-edit-line"></i></button>
                                            @endif
                                            @if($item->status === 'menunggu')
                                                <button wire:click="ubahStatus({{ $item->id }}, 'dipanggil')" class="flex h-7 px-2 items-center justify-center rounded bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Panggil"><i class="ri-notification-3-line"></i></button>
                                            @endif
                                            @if($item->status === 'dipanggil')
                                                <button wire:click="ubahStatus({{ $item->id }}, 'hadir')" class="flex h-7 px-2 items-center justify-center rounded bg-green-100 text-green-600 hover:bg-green-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Hadir"><i class="ri-user-follow-line"></i></button>
                                                <button wire:click="ubahStatus({{ $item->id }}, 'tidak_hadir')" class="flex h-7 px-2 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Tidak Hadir"><i class="ri-user-unfollow-line"></i></button>
                                            @endif
                                            @if(!$item->pasien_id && in_array($item->status, ['menunggu','dipanggil','hadir']))
                                                <button wire:click="openSyncModal({{ $item->id }})" class="flex h-7 px-2 items-center justify-center rounded bg-purple-100 text-purple-600 hover:bg-purple-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Sinkron Pasien"><i class="ri-link"></i></button>
                                            @endif
                                            @if(in_array($item->status, ['hadir','dipanggil']))
                                                <button wire:click="daftarkan({{ $item->id }})" class="flex h-7 px-2 items-center justify-center rounded bg-[#0ab39c]/10 text-[#0ab39c] hover:bg-[#0ab39c] hover:text-white transition-all text-[10px] font-bold gap-1" title="Daftarkan"><i class="ri-file-add-line"></i> Daftar</button>
                                            @endif
                                            @if(in_array($item->status, ['menunggu','dipanggil']))
                                                <button @click="Swal.fire({title:'Batalkan Antrian?',text:'Antrian ini akan dibatalkan.',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Batalkan!',cancelButtonText:'Tidak',reverseButtons:true}).then(r=>{if(r.isConfirmed)$wire.ubahStatus({{ $item->id }},'batal')})" class="flex h-7 px-2 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Batal"><i class="ri-close-line"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody></table>
                        </div></div>
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
