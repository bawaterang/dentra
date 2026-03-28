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

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function updatedSelectedDate() { $this->dispatch('refresh-table'); }
    public function setStatus($status) { $this->selectedStatus = $status; $this->dispatch('refresh-table'); }

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
                        <div class="p-4 border-b border-[#eff2f7] bg-white"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'terdaftar' ? 'active active-pill-success' : '' }}" wire:click="setStatus('terdaftar')" role="button"><i class="ri-checkbox-circle-line"></i><span>Terdaftar</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'menunggu_screening' ? 'active active-pill-warning' : '' }}" wire:click="setStatus('menunggu_screening')" role="button"><i class="ri-time-line"></i><span>Menunggu Screening</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'selesai' ? 'active active-pill-success' : '' }}" wire:click="setStatus('selesai')" role="button"><i class="ri-check-double-line"></i><span>Selesai</span></a></li>
                            </ul></div>
                        </div></div>
                        <div class="card-body p-0"><div class="table-responsive bg-white">
                            <table id="pendaftaranTable" class="display w-full">
                            <thead><tr><th>No Kunjungan</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Asuransi</th><th>Status</th><th class="!text-center" style="text-align:center!important;">Aksi</th></tr></thead>
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
                                        <a href="{{ route('pendaftaran.print', $item->id) }}" target="_blank" class="flex h-7 px-2 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all text-[10px] font-bold gap-1" title="Cetak"><i class="ri-printer-line"></i></a>
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
