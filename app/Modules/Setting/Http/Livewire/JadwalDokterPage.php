<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use App\Models\MstJadwalDokter;
use App\Models\MstDokter;

class JadwalDokterPage extends Component
{
    // Form Properties
    public $jadwalId;
    public $kode_dokter, $hari, $jam_mulai, $jam_selesai, $status_kehadiran = 'Hadir';
    public $is_active = true;

    // View Properties
    public $jadwalList = [];
    public $dokterList = [];
    
    public $selectedHari = 'all';
    public $isEdit = false;

    public $totalAktif = 0;
    public $totalLiburCuti = 0;
    public $totalJadwal = 0;

    public $hariOptions = [];
    public $statusOptions = [];
    public $dokterOptions = [];

    public function setHari($hari)
    {
        $this->selectedHari = $hari;
        $this->dispatch('refresh-table');
    }

    protected function rules()
    {
        return [
            'kode_dokter' => 'required|exists:mst_dokter,kode_dokter',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'status_kehadiran' => 'required|in:Hadir,Libur,Cuti',
            'is_active' => 'boolean',
        ];
    }

    public function mount()
    {
        $this->dokterList = MstDokter::where('status', 'Aktif')->get()->toArray();
    }

    public function resetForm()
    {
        $this->reset(['jadwalId', 'kode_dokter', 'hari', 'jam_mulai', 'jam_selesai', 'isEdit']);
        $this->status_kehadiran = 'Hadir';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-jadwal-modal');
        $this->dispatch('refresh-table');
    }

    public function edit($id)
    {
        $this->resetForm();
        
        $jadwal = MstJadwalDokter::findOrFail($id);
        
        $this->jadwalId = $jadwal->id;
        $this->kode_dokter = $jadwal->kode_dokter;
        $this->hari = $jadwal->hari;
        $this->jam_mulai = $jadwal->jam_mulai ? \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') : null;
        $this->jam_selesai = $jadwal->jam_selesai ? \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') : null;
        $this->status_kehadiran = $jadwal->status_kehadiran;
        $this->is_active = $jadwal->is_active;
        
        $this->isEdit = true;
        $this->dispatch('open-jadwal-modal');
        $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $this->validate();

            $jadwal = $this->jadwalId 
                ? MstJadwalDokter::findOrFail($this->jadwalId) 
                : new MstJadwalDokter();

            $jadwal->fill([
                'kode_dokter' => $this->kode_dokter,
                'hari' => $this->hari,
                'jam_mulai' => $this->jam_mulai ?: null,
                'jam_selesai' => $this->jam_selesai ?: null,
                'status_kehadiran' => $this->status_kehadiran,
                'is_active' => $this->is_active,
            ]);

            $jadwal->save();

            $this->dispatch('close-jadwal-modal');
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Jadwal dokter berhasil diperbarui!' : 'Jadwal dokter baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data yang Anda masukkan tidak valid. Silakan periksa kembali kolom yang bertanda merah.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $jadwal = MstJadwalDokter::findOrFail($id);
        $jadwal->delete();
        
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Jadwal dokter berhasil dihapus!']);
    }

    public function render()
    {
        $query = MstJadwalDokter::with('dokter');
        
        if ($this->selectedHari !== 'all') {
            $query->where('hari', $this->selectedHari);
        }
        
        $this->jadwalList = $query->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")->get();
        
        $this->totalAktif = MstJadwalDokter::where('is_active', true)->where('status_kehadiran', 'Hadir')->count();
        $this->totalLiburCuti = MstJadwalDokter::whereIn('status_kehadiran', ['Libur', 'Cuti'])->count();
        $this->totalJadwal = MstJadwalDokter::count();

        $this->hariOptions = collect(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])->map(fn($h) => [
            'value' => $h,
            'label' => $h,
            'icon' => 'ri-calendar-event-line'
        ])->toArray();

        $this->statusOptions = [
            ['value' => 'Hadir', 'label' => 'Hadir Praktik', 'icon' => 'ri-checkbox-circle-line'],
            ['value' => 'Libur', 'label' => 'Libur Reguler', 'icon' => 'ri-calendar-close-line'],
            ['value' => 'Cuti', 'label' => 'Sedang Cuti/Izin', 'icon' => 'ri-user-unfollow-line'],
        ];

        $this->dokterOptions = collect($this->dokterList)->map(fn($d) => [
            'value' => $d['kode_dokter'],
            'label' => $d['nama_dokter'] . ' (' . ($d['spesialisasi'] ?? 'Umum') . ')',
            'icon' => 'ri-user-star-line'
        ])->toArray();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return <<<'HTML'
        <div x-data="{ 
            showModal: false,
            initDataTable() {
                const tableId = '#jadwalTable';
                if ($.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().destroy();
                }
                const table = $(tableId).DataTable({
                    scrollX: false,
                    dom: 'lrtip',
                    language: {
                        lengthMenu: '_MENU_',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ jadwal',
                        infoEmpty: 'Menampilkan 0 sampai 0 dari 0 jadwal',
                        infoFiltered: '(disaring dari total _MAX_ jadwal)',
                        zeroRecords: 'Tidak ada jadwal yang ditemukan',
                        emptyTable: 'Tidak ada jadwal dalam tabel',
                        paginate: {
                            previous: '<i class=ri-arrow-left-s-line></i>',
                            next: '<i class=ri-arrow-right-s-line></i>'
                        }
                    }
                });
                $('#customSearch').off('keyup').on('keyup', function() {
                    table.search(this.value).draw();
                });
            },
            init() {
                this.$watch('showModal', value => {
                    if (value) {
                        $nextTick(() => { this.$refs.firstInput.focus() });
                    }
                    $nextTick(() => this.initDataTable());
                });
                $nextTick(() => this.initDataTable());
            }
        }" 
        @open-jadwal-modal.window="showModal = true"
        @close-jadwal-modal.window="showModal = false"
        @refresh-table.window="$nextTick(() => initDataTable())"
        x-init="initDataTable()">
            
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon">
                        <i class="ri-calendar-2-line"></i>
                    </div>
                    <h1>Jadwal Dokter</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a>
                    <span class="sep">/</span><a href="#">Master</a>
                    <span class="sep">/</span><span>Jadwal Dokter</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info"><i class="ri-calendar-todo-line text-xl"></i></div>
                        <div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Jadwal</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalJadwal) }}</h4></div>
                    </div>
                </div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success"><i class="ri-checkbox-circle-line text-xl"></i></div>
                        <div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Jadwal Aktif Terjadwal</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalAktif) }}</h4></div>
                    </div>
                </div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f7b84b;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-subtle text-warning"><i class="ri-alarm-warning-line text-xl"></i></div>
                        <div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Jadwal Cuti/Libur</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalLiburCuti) }}</h4></div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7]">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom flex space-x-1">
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedHari === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setHari('all')" role="button">
                                        <i class="ri-layout-grid-line"></i><span>Semua Hari</span>
                                    </a>
                                </li>
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h)
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedHari === $h ? 'active active-pill-primary' : '' }}" wire:click="setHari('{{ $h }}')" role="button"><span>{{ $h }}</span></a>
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                            <div class="relative flex-grow md:flex-none">
                                <input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari jadwal...">
                                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                            </div>

                            <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>

                            <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                <i class="ri-add-line text-lg"></i><span class="font-semibold text-xs uppercase tracking-wider">Tambah Jadwal</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive dark:bg-transparent">
                        <table id="jadwalTable" class="table align-middle table-nowrap w-full">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Hari</th>
                                <th>Nama Dokter</th>
                                <th>Spesialisasi</th>
                                <th>Jam Praktik</th>
                                <th>Status/Kehadiran</th>
                                <th class="!text-center" style="text-align: center !important;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jadwalList as $jadwal)
                            <tr wire:key="jadwal-{{ $jadwal->id }}">
                                <td><span class="font-semibold text-[#405189]">{{ $jadwal->hari }}</span></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-full bg-[#405189]/10 text-[#405189] flex items-center justify-center font-bold text-xs">{{ substr($jadwal->dokter?->nama_dokter ?? '?', 0, 1) }}</div>
                                        <div><h6 class="mb-0">{{ $jadwal->dokter?->nama_dokter ?? '-' }}</h6></div>
                                    </div>
                                </td>
                                <td>{{ $jadwal->dokter?->spesialisasi ?? '-' }}</td>
                                <td>
                                    @if($jadwal->jam_mulai && $jadwal->jam_selesai)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                                            <i class="ri-time-line text-blue-500"></i> {{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-500 italic">Tidak ada jam khusus</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-col gap-1 items-start">
                                        @if($jadwal->is_active)
                                            <span class="badge bg-success-subtle text-success">Jadwal Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Jadwal Nonaktif</span>
                                        @endif
                                        
                                        @if($jadwal->status_kehadiran == 'Hadir')
                                            <span class="badge bg-primary-subtle text-primary">Status: Hadir</span>
                                        @elseif($jadwal->status_kehadiran == 'Libur')
                                            <span class="badge bg-secondary-subtle text-secondary">Status: Libur</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Status: Cuti</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <button wire:click="edit({{ $jadwal->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all"><i class="ri-edit-line"></i></button>
                                        <button @click="
                                            Swal.fire({
                                                title: 'Hapus Jadwal?',
                                                text: 'Tindakan ini akan menghapus jadwal secara permanen.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#f06548',
                                                cancelButtonColor: '#6c757d',
                                                confirmButtonText: 'Ya, Hapus!',
                                                cancelButtonText: 'Batal',
                                                reverseButtons: true
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    $wire.delete({{ $jadwal->id }})
                                                }
                                            })
                                        " class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all"><i class="ri-delete-bin-line"></i></button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal: Tambah/Edit Jadwal -->
            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Jadwal Dokter' : 'Tambah Jadwal Dokter' }}</h5>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Dokter <span class="text-red-500">*</span></label>
                                    <x-custom-dropdown 
                                        model="kode_dokter" 
                                        :options="$dokterOptions" 
                                        searchable="true" 
                                        placeholder="-- Pilih Dokter --" 
                                        icon="ri-user-star-line" 
                                    />
                                    @error('kode_dokter') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Hari Praktik <span class="text-red-500">*</span></label>
                                        <x-custom-dropdown 
                                            model="hari" 
                                            :options="$hariOptions" 
                                            placeholder="-- Hari --" 
                                            icon="ri-calendar-event-line" 
                                        />
                                        @error('hari') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Status Kehadiran <span class="text-red-500">*</span></label>
                                        <x-custom-dropdown 
                                            model="status_kehadiran" 
                                            :options="$statusOptions" 
                                            icon="ri-checkbox-circle-line" 
                                        />
                                        @error('status_kehadiran') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Jam Mulai</label>
                                        <input type="time" wire:model="jam_mulai" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all">
                                        @error('jam_mulai') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Jam Selesai</label>
                                        <input type="time" wire:model="jam_selesai" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all">
                                        @error('jam_selesai') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2 mt-2">
                                    <input type="checkbox" id="isActiveCheck" wire:model="is_active" class="h-4 w-4 rounded border-gray-300 text-[#405189] focus:ring-[#405189]">
                                    <label for="isActiveCheck" class="text-sm font-semibold text-gray-700">Jadwal Aktif (Tampilkan Jadwal Ini ke Sistem)</label>
                                </div>
                                @error('is_active') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            </div>
                        </form>
                    </div>

                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Batal</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70"><svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><i wire:loading.remove wire:target="save" class="ri-save-line"></i><span wire:loading.remove wire:target="save">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal' }}</span><span wire:loading wire:target="save">Memproses...</span></button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
