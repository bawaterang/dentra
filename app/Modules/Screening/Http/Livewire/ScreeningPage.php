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

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function updatedSelectedDate() { $this->dispatch('refresh-table'); }
    public function setTab($tab) { $this->selectedTab = $tab; $this->dispatch('refresh-table'); }

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

        $totalBelum = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->whereIn('status', ['terdaftar','menunggu_screening'])->count();
        $totalSudah = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->where('status', 'selesai')->count();

        return <<<'HTML'
        <div x-data="{ 
            initDataTable() { 
                const t='#screeningTable'; 
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} 
                $(t).DataTable({scrollX:false,dom:'lrtip',pageLength:25,language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Tidak ada data',zeroRecords:'Tidak ada data screening',emptyTable:'Belum ada pasien untuk screening',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}});
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
                        <a href="{{ route('screening.export', ['date' => $selectedDate]) }}" class="btn bg-green-600 text-white w-full h-9 flex items-center justify-center gap-2 text-xs font-bold hover:bg-green-700 transition-all mb-2"><i class="ri-file-excel-2-line"></i> Unduh Excel</a>
                    </div>
                </div>

                <!-- Table -->
                <div class="lg:col-span-3">
                    <div class="card overflow-hidden border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7] bg-white">
                            <div class="flex overflow-x-auto scrollbar-hide"><ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedTab === 'belum' ? 'active active-pill-warning' : '' }}" wire:click="setTab('belum')" role="button"><i class="ri-time-line"></i><span>Belum Screening ({{ $totalBelum }})</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedTab === 'sudah' ? 'active active-pill-success' : '' }}" wire:click="setTab('sudah')" role="button"><i class="ri-checkbox-circle-line"></i><span>Sudah Screening ({{ $totalSudah }})</span></a></li>
                            </ul></div>
                        </div>
                        <div class="card-body p-0"><div class="table-responsive bg-white">
                            <table id="screeningTable" class="display w-full">
                            <thead><tr><th>No Kunjungan</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Status</th><th class="!text-center" style="text-align:center!important;">Aksi</th></tr></thead>
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
                                        @endif
                                    </div></td>
                                </tr>
                                @endforeach
                            </tbody></table>
                        </div></div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
