<?php

namespace App\Modules\Pendaftaran\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use Carbon\Carbon;

class PendaftaranPage extends Component
{
    public $selectedDate;
    public $selectedStatus = 'all';
    public $pendaftaranList = [];

    // Edit Pendaftaran
    public $showEditModal = false;
    public $editPendaftaranId, $editPasienId;
    public $editPoliId, $editDokterId, $editAsuransiId, $editNoKartuAsuransi;
    public $editKesadaran = 'Compos Mentis', $editTd, $editNadi, $editSuhu, $editBb, $editTb;
    public $editRiwayat, $editAlergi, $editKet;
    
    // Dropdown Data
    public $poliList = [], $dokterList = [], $asuransiList = [];
    public $kesadaranList = ['Compos Mentis', 'Apatis', 'Delirium', 'Somnolen', 'Sopor', 'Semi-Koma', 'Koma'];

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->kesadaranList = ['Compos Mentis', 'Apatis', 'Delirium', 'Somnolen', 'Sopor', 'Semi-Koma', 'Koma'];
    }

    public function updatedSelectedDate() { $this->dispatch('refresh-table'); }
    public function setStatus($status) { $this->selectedStatus = $status; $this->dispatch('refresh-table'); }

    public function editPendaftaran($id)
    {
        $p = TrxPendaftaran::findOrFail($id);
        $this->editPendaftaranId = $p->id;
        $this->editPasienId = $p->pasien_id;
        $this->editPoliId = $p->poli_id;
        $this->editDokterId = $p->dokter_id;
        $this->editAsuransiId = $p->asuransi_id;
        $this->editNoKartuAsuransi = $p->no_kartu_asuransi;
        $this->editKesadaran = $p->kesadaran;
        $this->editTd = $p->tekanan_darah;
        $this->editNadi = $p->nadi;
        $this->editSuhu = $p->suhu;
        $this->editBb = $p->berat_badan;
        $this->editTb = $p->tinggi_badan;
        $this->editRiwayat = $p->riwayat_penyakit;
        $this->editAlergi = $p->alergi;
        $this->editKet = $p->keterangan_lain;

        $this->poliList = \App\Models\MstPoli::where(fn($q) => $q->where('status', 'Aktif'))->get()->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli, 'icon' => 'ri-hospital-line text-blue-500'])->toArray();
        $this->dokterList = \App\Models\MstDokter::where(fn($q) => $q->where('status', 'Aktif'))->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->nama_dokter, 'icon' => 'ri-user-star-line text-purple-500'])->toArray();
        $this->asuransiList = \App\Models\MstAsuransi::where(fn($q) => $q->where('status', 'Aktif'))->get()->map(fn($a) => ['value' => $a->id, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-check-line text-green-500'])->toArray();

        $this->showEditModal = true;
        $this->dispatch('refresh-table');
    }

    public function updatePendaftaran()
    {
        $this->validate([
            'editPoliId' => 'required|exists:mst_poli,id',
            'editDokterId' => 'required|exists:mst_dokter,id',
        ]);

        $p = TrxPendaftaran::findOrFail($this->editPendaftaranId);
        $p->update([
            'poli_id' => $this->editPoliId,
            'dokter_id' => $this->editDokterId,
            'asuransi_id' => $this->editAsuransiId,
            'no_kartu_asuransi' => $this->editNoKartuAsuransi,
            'kesadaran' => $this->editKesadaran,
            'tekanan_darah' => $this->editTd,
            'nadi' => $this->editNadi,
            'suhu' => $this->editSuhu,
            'berat_badan' => $this->editBb,
            'tinggi_badan' => $this->editTb,
            'riwayat_penyakit' => $this->editRiwayat,
            'alergi' => $this->editAlergi,
            'keterangan_lain' => $this->editKet,
        ]);

        $this->showEditModal = false;
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pendaftaran berhasil diperbarui.']);
    }

    public function render()
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'asuransi'])
            ->whereDate('created_at', $this->selectedDate);

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        $this->pendaftaranList = $query->orderBy('created_at', 'desc')->get();

        return <<<'HTML'
        <div x-data="{ 
            initDataTable() { 
                const t='#pendaftaranTable'; 
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} 
                $(t).DataTable({scrollX:false,dom:'lrtip',pageLength:25,language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Tidak ada data',zeroRecords:'Tidak ada pendaftaran',emptyTable:'Belum ada pendaftaran hari ini',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}});
            },
            init(){ $nextTick(()=>this.initDataTable()) }
        }" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-file-add-line"></i></div><h1>Pendaftaran</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a><span class="sep">/</span><span>Pendaftaran</span></div></div>

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
                        <div class="p-4 border-t border-[#eff2f7]">
                            <a href="{{ route('pendaftaran.create') }}" wire:navigate class="btn btn-primary w-full h-10 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider"><i class="ri-add-circle-line text-lg"></i> Pendaftaran Baru</a>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="lg:col-span-3">
                    <div class="card overflow-hidden border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'terdaftar' ? 'active active-pill-success' : '' }}" wire:click="setStatus('terdaftar')" role="button"><i class="ri-checkbox-circle-line"></i><span>Terdaftar</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'menunggu_screening' ? 'active active-pill-warning' : '' }}" wire:click="setStatus('menunggu_screening')" role="button"><i class="ri-time-line"></i><span>Menunggu Screening</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'selesai' ? 'active active-pill-success' : '' }}" wire:click="setStatus('selesai')" role="button"><i class="ri-check-double-line"></i><span>Selesai</span></a></li>
                            </ul></div>
                        </div></div>
                        <div class="card-body p-0"><div class="table-responsive dark:bg-transparent">
                            <table id="pendaftaranTable" class="table align-middle table-nowrap w-full">
                            <thead class="table-light text-muted"><tr><th>No Kunjungan</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Asuransi</th><th>Status</th><th class="!text-center" style="text-align:center!important;">Aksi</th></tr></thead>
                            <tbody>
                                @foreach($pendaftaranList as $item)
                                <tr wire:key="daftar-{{ $item->id }}">
                                    <td><span class="font-mono font-bold text-[#405189] text-xs">{{ $item->nomor_kunjungan }}</span></td>
                                    <td><div class="font-semibold text-[#495057]">{{ $item->pasien->nama_pasien ?? '-' }}</div><span class="text-[10px] text-[#878a99]">{{ $item->pasien->no_rm ?? '' }}</span></td>
                                    <td>{{ $item->poli->nama_poli ?? '-' }}</td>
                                    <td>{{ $item->dokter->nama_dokter ?? '-' }}</td>
                                    <td>{{ $item->asuransi?->nama_asuransi ?? 'Umum' }}</td>
                                    <td>
                                        @php $sc = ['terdaftar'=>'bg-info-subtle','menunggu_screening'=>'bg-warning-subtle','selesai'=>'bg-success-subtle']; @endphp
                                        <span class="badge {{ $sc[$item->status] ?? 'bg-secondary-subtle' }}">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span>
                                    </td>
                                    <td class="text-center"><div class="flex justify-center gap-1">
                                        <button wire:click="editPendaftaran({{ $item->id }})" class="flex h-7 px-2 items-center justify-center rounded bg-[#0ab39c]/10 text-[#0ab39c] hover:bg-[#0ab39c] hover:text-white transition-all text-[10px] font-bold gap-1" title="Edit"><i class="ri-edit-line"></i></button>
                                        <a href="{{ route('pendaftaran.print', $item->id) }}" target="_blank" class="flex h-7 px-2 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all text-[10px] font-bold gap-1" title="Cetak"><i class="ri-printer-line"></i></a>
                                    </div></td>
                                </tr>
                                @endforeach
                            </tbody></table>
                        </div></div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div x-show="$wire.showEditModal" class="fixed inset-0 z-[1050] overflow-y-auto" x-cloak>
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" x-show="$wire.showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative z-10" x-show="$wire.showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <div class="bg-[#405189] px-6 py-4 flex justify-between items-center shadow-lg">
                            <h3 class="text-white font-bold flex items-center gap-2"><i class="ri-edit-box-line text-xl"></i> Edit Pendaftaran</h3>
                            <button @click="$wire.set('showEditModal', false)" class="text-white/80 hover:text-white transition-colors"><i class="ri-close-line text-2xl"></i></button>
                        </div>
                        <form wire:submit.prevent="updatePendaftaran">
                            <div class="px-8 py-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                    <!-- Kolom Kiri: Informasi Kunjungan -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-3 mb-6">
                                            <div class="w-10 h-10 rounded-xl bg-[#405189]/10 text-[#405189] flex items-center justify-center text-xl shadow-inner"><i class="ri-information-line"></i></div>
                                            <div>
                                                <h6 class="text-xs font-black text-[#405189] uppercase tracking-[2.5px] m-0">Informasi Kunjungan</h6>
                                                <p class="text-[10px] text-gray-400 font-medium">Pilih poli, dokter, dan asuransi</p>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5">
                                            <x-custom-dropdown label="Poli Tujuan" model="editPoliId" :options="$poliList" placeholder="Pilih Poli..." />
                                            <x-custom-dropdown label="Dokter" model="editDokterId" :options="$dokterList" placeholder="Pilih Dokter..." />
                                            <x-custom-dropdown label="Asuransi / Penjamin" model="editAsuransiId" :options="$asuransiList" placeholder="Pilih Asuransi..." />
                                            
                                            <div class="space-y-1.5">
                                                <label class="block text-xs font-bold text-gray-500 ml-1 uppercase tracking-wider">No Kartu Asuransi</label>
                                                <div class="relative group">
                                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none group-focus-within:text-[#405189] transition-colors text-gray-400">
                                                        <i class="ri-id-card-line"></i>
                                                    </div>
                                                    <input type="text" wire:model="editNoKartuAsuransi" wire:key="edit-nokartu" class="block w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-4 focus:ring-[#405189]/10 focus:border-[#405189]" placeholder="Masukkan nomor kartu...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kolom Kanan: Data Klinis & SOAP -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-3 mb-6">
                                            <div class="w-10 h-10 rounded-xl bg-[#0ab39c]/10 text-[#0ab39c] flex items-center justify-center text-xl shadow-inner"><i class="ri-heart-pulse-line"></i></div>
                                            <div>
                                                <h6 class="text-xs font-black text-[#0ab39c] uppercase tracking-[2.5px] m-0">Pemeriksaan Awal</h6>
                                                <p class="text-[10px] text-gray-400 font-medium">Vitals & riwayat medis dasar</p>
                                            </div>
                                        </div>

                                        <div class="bg-white border border-gray-100 rounded-3xl shadow-sm p-6 space-y-6">
                                            <!-- Vitals Grid -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="col-span-2">
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Tingkat Kesadaran</label>
                                                    <select wire:model="editKesadaran" wire:key="edit-kesadaran" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-[#0ab39c]/10 focus:border-[#0ab39c] transition-all">
                                                        @foreach($kesadaranList as $k)
                                                        <option value="{{ $k }}">{{ $k }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Tekanan Darah</label>
                                                    <input type="text" wire:model="editTd" wire:key="edit-td" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="120/80">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Nadi (bpm)</label>
                                                    <input type="text" wire:model="editNadi" wire:key="edit-nadi" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="80">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Suhu (deg. C)</label>
                                                    <input type="number" step="0.1" wire:model="editSuhu" wire:key="edit-suhu" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="36.5">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Berat Badan (kg)</label>
                                                    <input type="number" step="0.1" wire:model="editBb" wire:key="edit-bb" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="60">
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Tinggi Badan (cm)</label>
                                                    <input type="number" step="0.1" wire:model="editTb" wire:key="edit-tb" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="170">
                                                </div>
                                            </div>

                                            <div class="border-t border-gray-100 pt-6 space-y-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-red-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px] flex items-center gap-1"><i class="ri-error-warning-line"></i> Alergi</label>
                                                    <textarea wire:model="editAlergi" wire:key="edit-alergi" rows="2" class="block w-full px-4 py-3 bg-red-50/20 border border-red-100 rounded-xl text-sm focus:ring-4 focus:ring-red-500/5 focus:border-red-500 transition-all shadow-sm" placeholder="Catatan alergi..."></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px] flex items-center gap-1"><i class="ri-history-line"></i> Riwayat Penyakit</label>
                                                    <textarea wire:model="editRiwayat" wire:key="edit-riwayat" rows="2" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-[#0ab39c]/5 focus:border-[#0ab39c] transition-all shadow-sm" placeholder="Riwayat medis sebelumnya..."></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px] flex items-center gap-1"><i class="ri-chat-4-line"></i> Keterangan Lain</label>
                                                    <textarea wire:model="editKet" wire:key="edit-ket" rows="2" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-[#0ab39c]/5 focus:border-[#0ab39c] transition-all shadow-sm" placeholder="Catatan tambahan..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-8 py-5 flex justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                                <button type="button" @click="$wire.set('showEditModal', false)" class="px-6 py-2.5 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-200 transition-all">Batal</button>
                                <button type="submit" class="px-8 py-2.5 bg-[#405189] text-white rounded-xl text-sm font-bold shadow-lg shadow-[#405189]/30 hover:bg-[#364574] hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2"><i class="ri-save-3-line"></i> Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
