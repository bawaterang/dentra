<?php

namespace App\Modules\Screening\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use Carbon\Carbon;

class ScreeningPage extends Component
{
    public $selectedDate;
    public $selectedTab = 'belum'; // belum / sudah
    public $pendaftaranList = [];
    public $totalBelum = 0;
    public $totalSudah = 0;

    // Edit Modal Properties
    public $showEditModal = false;
    public $editPendaftaranId;
    public $pertanyaanList = [];
    public $jawaban = [];
    public $keterangan = [];
    public $editPasienName = '', $editNoRm = '', $editKunjungan = '', $editPoliName = '', $editDokterName = '';

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->pertanyaanList = \App\Models\MstSurvei::where('status', 'Aktif')->where('jenis_survei', 'screening')->get();
    }

    public function updatedSelectedDate() { $this->dispatch('refresh-table'); }
    public function setTab($tab) { $this->selectedTab = $tab; $this->dispatch('refresh-table'); }

    public function editScreening($id)
    {
        $p = TrxPendaftaran::with(['pasien', 'poli', 'dokter'])->findOrFail($id);
        $this->editPendaftaranId = $p->id;
        $this->editPasienName = $p->pasien->nama_pasien ?? '-';
        $this->editNoRm = $p->pasien->no_rm ?? '-';
        $this->editKunjungan = $p->nomor_kunjungan;
        $this->editPoliName = $p->poli->nama_poli ?? '-';
        $this->editDokterName = $p->dokter->nama_dokter ?? '-';

        $existing = \App\Models\TrxScreening::where('pendaftaran_id', $id)->get();
        if ($existing->count() > 0) {
            foreach ($existing as $scr) {
                $this->jawaban[$scr->survei_id] = $scr->jawaban;
                $this->keterangan[$scr->survei_id] = $scr->keterangan;
            }
        } else {
            foreach ($this->pertanyaanList as $p_survei) {
                $this->jawaban[$p_survei->id] = 'tidak';
                $this->keterangan[$p_survei->id] = '';
            }
        }

        $this->showEditModal = true;
        $this->dispatch('refresh-table'); 
    }

    public function updateScreening()
    {
        $p = TrxPendaftaran::findOrFail($this->editPendaftaranId);
        foreach ($this->pertanyaanList as $q) {
            \App\Models\TrxScreening::updateOrCreate(
                ['pendaftaran_id' => $this->editPendaftaranId, 'survei_id' => $q->id],
                [
                    'pasien_id' => $p->pasien_id,
                    'jawaban' => $this->jawaban[$q->id] ?? 'tidak',
                    'keterangan' => $this->keterangan[$q->id] ?? null,
                ]
            );
        }

        $this->showEditModal = false;
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data screening berhasil diperbarui.']);
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->dispatch('refresh-table');
    }

    public function render()
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'screenings'])
            ->whereDate('created_at', $this->selectedDate);

        if ($this->selectedTab === 'belum') {
            $query->whereIn('status', ['terdaftar', 'menunggu_screening']);
        } else {
            $query->where('status', 'selesai');
        }

        $this->pendaftaranList = $query->orderBy('created_at', 'desc')->get();

        $this->totalBelum = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->whereIn('status', ['terdaftar','menunggu_screening'])->count();
        $this->totalSudah = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->where('status', 'selesai')->count();

        return <<<'HTML'
        <div x-data="{ 
            initDataTable() { 
                const t='#screeningTable'; 
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} 
                $(t).DataTable({
                    scrollX:false,
                    dom:'lrtip',
                    pageLength:25,
                    autoWidth: false,
                    responsive: true,
                    language:{
                        lengthMenu:'_MENU_',
                        info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                        infoEmpty:'Tidak ada data',
                        zeroRecords:'Tidak ada data screening',
                        emptyTable:'Belum ada pasien untuk screening',
                        paginate:{
                            previous:'<i class=ri-arrow-left-s-line></i>',
                            next:'<i class=ri-arrow-right-s-line></i>'
                        }
                    }
                });
            },
            init(){ $nextTick(()=>this.initDataTable()) }
        }" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-shield-check-line"></i></div><h1>Screening Pasien</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a><span class="sep">/</span><span>Screening</span></div></div>

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
                        <div class="p-4 border-t border-[#eff2f7] space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-lg bg-orange-50">
                                <div class="flex items-center gap-2"><i class="ri-time-line text-orange-500"></i><span class="text-xs font-semibold text-orange-700">Belum Screening</span></div>
                                <span class="text-lg font-bold text-orange-600">{{ $totalBelum }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-green-50">
                                <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i><span class="text-xs font-semibold text-green-700">Sudah Screening</span></div>
                                <span class="text-lg font-bold text-green-600">{{ $totalSudah }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm mt-4 p-4">
                        <div class="flex items-center gap-2 mb-3"><i class="ri-download-2-line text-[#405189]"></i><span class="text-xs font-bold text-[#405189] uppercase tracking-widest">Eksport</span></div>
                        <a href="{{ route('screening.export', ['date' => $selectedDate]) }}" target="_blank" class="btn bg-green-600 text-white w-full h-9 flex items-center justify-center gap-2 text-xs font-bold hover:bg-green-700 transition-all mb-2"><i class="ri-file-excel-2-line"></i> Unduh Excel</a>
                    </div>
                </div>

                <!-- Table -->
                <div class="lg:col-span-3">
                    <div class="card overflow-hidden border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]">
                            <div class="flex overflow-x-auto scrollbar-hide"><ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedTab === 'belum' ? 'active active-pill-warning' : '' }}" wire:click="setTab('belum')" role="button"><i class="ri-time-line"></i><span>Belum Screening ({{ $totalBelum }})</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedTab === 'sudah' ? 'active active-pill-success' : '' }}" wire:click="setTab('sudah')" role="button"><i class="ri-checkbox-circle-line"></i><span>Sudah Screening ({{ $totalSudah }})</span></a></li>
                            </ul></div>
                        </div>
                        <div class="card-body p-0"><div class="table-responsive dark:bg-transparent">
                            <table id="screeningTable" class="table align-middle table-nowrap w-full">
                            <thead class="table-light text-muted"><tr><th>No Kunjungan</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Status</th><th class="!text-center" style="text-align:center!important;">Aksi</th></tr></thead>
                            <tbody>
                                @foreach($pendaftaranList as $item)
                                <tr wire:key="scr-{{ $item->id }}">
                                    <td><span class="font-mono font-bold text-[#405189] text-xs">{{ $item->nomor_kunjungan }}</span></td>
                                    <td><div class="font-semibold text-[#495057]">{{ $item->pasien->nama_pasien ?? '-' }}</div><span class="text-[10px] text-[#878a99]">{{ $item->pasien->no_rm ?? '' }}</span></td>
                                    <td>{{ $item->poli->nama_poli ?? '-' }}</td>
                                    <td>{{ $item->dokter->nama_dokter ?? '-' }}</td>
                                    <td>
                                        @if($item->status === 'selesai')
                                        <span class="badge bg-success-subtle"><i class="ri-checkbox-circle-line mr-1"></i>Selesai</span>
                                        @else
                                        <span class="badge bg-warning-subtle"><i class="ri-time-line mr-1"></i>Belum Screening</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><div class="flex justify-center gap-1">
                                        @if($item->status !== 'selesai')
                                        <a href="{{ route('screening.form', $item->id) }}" wire:navigate class="flex h-7 px-3 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all text-[10px] font-bold gap-1"><i class="ri-shield-check-line"></i> Screening</a>
                                        @else
                                        <a href="{{ route('screening.print', $item->id) }}" target="_blank" class="flex h-7 px-2 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all text-[10px] font-bold gap-1" title="Cetak"><i class="ri-printer-line"></i></a>
                                        <a href="{{ route('screening.form', $item->id) }}" wire:navigate class="flex h-7 px-2 items-center justify-center rounded bg-green-100 text-green-600 hover:bg-green-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Lihat"><i class="ri-eye-line"></i></a>
                                        <button type="button" wire:click="editScreening({{ $item->id }})" class="flex h-7 px-2 items-center justify-center rounded bg-orange-100 text-orange-600 hover:bg-orange-600 hover:text-white transition-all text-[10px] font-bold gap-1" title="Edit"><i class="ri-edit-line"></i></button>
                                        @endif
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
                             <h3 class="text-white font-bold flex items-center gap-2"><i class="ri-edit-box-line text-xl"></i> Edit Screening</h3>
                             <button wire:click="closeEditModal" type="button" class="text-white/80 hover:text-white transition-colors"><i class="ri-close-line text-2xl"></i></button>
                        </div>
                        <form wire:submit.prevent="updateScreening">
                            <div class="px-8 py-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                                
                                <!-- Patient Info Card -->
                                <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                     <div class="flex items-center gap-4">
                                         <div class="h-14 w-14 rounded-full bg-gradient-to-br from-[#405189] to-[#3577f1] text-white flex items-center justify-center font-bold text-xl">{{ substr($editPasienName, 0, 1) }}</div>
                                         <div>
                                             <h4 class="font-bold text-lg text-[#495057]">{{ $editPasienName }}</h4>
                                             <div class="flex flex-wrap gap-3 text-xs text-[#878a99]">
                                                 <span><i class="ri-hashtag mr-1"></i>{{ $editNoRm }}</span>
                                                 <span><i class="ri-hospital-line mr-1"></i>{{ $editPoliName }}</span>
                                                 <span><i class="ri-user-star-line mr-1"></i>{{ $editDokterName }}</span>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="text-right">
                                         <span class="font-mono font-bold text-[#0ab39c] text-sm">{{ $editKunjungan }}</span>
                                     </div>
                                </div>

                                <!-- Pertanyaan Grid -->
                                <div class="space-y-4">
                                    @foreach($pertanyaanList as $index => $p_survei)
                                    <div class="p-4 rounded-xl border border-gray-100 hover:border-[#405189]/20 transition-all {{ isset($jawaban[$p_survei->id]) && $jawaban[$p_survei->id] === 'ya' ? 'bg-red-50 border-red-200' : 'bg-white' }}">
                                        <div class="flex items-start gap-4">
                                            <span class="flex-shrink-0 h-7 w-7 rounded-lg bg-[#405189] text-white flex items-center justify-center text-xs font-bold mt-0.5">{{ $index + 1 }}</span>
                                            <div class="flex-1">
                                                <p class="font-medium text-[#495057] text-sm mb-3">{{ $p_survei->pertanyaan }}</p>
                                                <div class="flex items-center gap-6">
                                                     <label class="flex items-center gap-2 cursor-pointer group">
                                                         <input type="radio" wire:model="jawaban.{{ $p_survei->id }}" value="ya" class="w-4 h-4 text-red-500 border-gray-300 focus:ring-red-400">
                                                         <span class="text-sm font-semibold {{ isset($jawaban[$p_survei->id]) && $jawaban[$p_survei->id] === 'ya' ? 'text-red-600' : 'text-gray-500' }} group-hover:text-red-500">Ya</span>
                                                     </label>
                                                     <label class="flex items-center gap-2 cursor-pointer group">
                                                         <input type="radio" wire:model="jawaban.{{ $p_survei->id }}" value="tidak" class="w-4 h-4 text-green-500 border-gray-300 focus:ring-green-400">
                                                         <span class="text-sm font-semibold {{ isset($jawaban[$p_survei->id]) && $jawaban[$p_survei->id] === 'tidak' ? 'text-green-600' : 'text-gray-500' }} group-hover:text-green-500">Tidak</span>
                                                     </label>
                                                </div>
                                                <div class="mt-4">
                                                     <input type="text" wire:model="keterangan.{{ $p_survei->id }}" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all bg-gray-50/50" placeholder="Tambahkan keterangan rincian (opsional)...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="bg-gray-50 px-8 py-5 flex justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                                 <button type="button" wire:click="closeEditModal" class="btn bg-gray-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-gray-600 rounded-xl font-bold"><i class="ri-close-line"></i> Batal</button>
                                 <button type="submit" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] rounded-xl font-bold"><i class="ri-save-line"></i> Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
