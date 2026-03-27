<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstDokter;
use Illuminate\Validation\Rule;

class DokterPage extends Component
{
    // Form Properties
    public $dokterId;
    public $kode_dokter, $nama_dokter, $nik, $jenis_kelamin, $tempat_lahir, $tanggal_lahir;
    public $alamat, $no_telepon, $agama, $spesialisasi, $no_sip, $no_str, $status;
    
    // View Properties
    public $dokterList = [];
    public $totalDokter = 0;
    public $totalSpesialis = 0;
    public $takAktif = 0;
    
    public $selectedStatus = 'all';
    public $isEdit = false;

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->dispatch('refresh-table');
    }

    protected function rules()
    {
        return [
            'kode_dokter' => ['required', 'string', 'max:20', Rule::unique('mst_dokter', 'kode_dokter')->ignore($this->dokterId)],
            'nama_dokter' => 'required|string|max:100',
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('mst_dokter', 'nik')->ignore($this->dokterId)],
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:20',
            'agama' => 'nullable|string|max:20',
            'spesialisasi' => 'nullable|string|max:100',
            'no_sip' => 'nullable|string|max:50',
            'no_str' => 'nullable|string|max:50',
        ];
    }

    public function resetForm()
    {
        $this->reset(['dokterId', 'kode_dokter', 'nama_dokter', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_telepon', 'agama', 'spesialisasi', 'no_sip', 'no_str', 'isEdit']);
        $this->status = 'Aktif';
        $this->resetErrorBag();
    }

    private function generateKodeDokter()
    {
        $lastDokter = MstDokter::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = 1;
        if ($lastDokter && $lastDokter->kode_dokter) {
            $lastNumber = (int) substr($lastDokter->kode_dokter, 1);
            $nextNumber = $lastNumber + 1;
        }
        return 'D' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        $this->resetForm();
        $this->kode_dokter = $this->generateKodeDokter();
        $this->dispatch('open-dokter-modal');
        $this->dispatch('refresh-table');
    }

    public function edit($id)
    {
        $this->resetForm();
        $dokter = MstDokter::withTrashed()->findOrFail($id);
        
        $this->dokterId = $dokter->id;
        $this->kode_dokter = $dokter->kode_dokter;
        $this->nama_dokter = $dokter->nama_dokter;
        $this->nik = $dokter->nik;
        $this->jenis_kelamin = $dokter->jenis_kelamin;
        $this->tempat_lahir = $dokter->tempat_lahir;
        $this->tanggal_lahir = $dokter->tanggal_lahir ? $dokter->tanggal_lahir->format('Y-m-d') : null;
        $this->alamat = $dokter->alamat;
        $this->no_telepon = $dokter->no_telepon;
        $this->agama = $dokter->agama;
        $this->spesialisasi = $dokter->spesialisasi;
        $this->no_sip = $dokter->no_sip;
        $this->no_str = $dokter->no_str;
        $this->status = $dokter->status;
        
        $this->isEdit = true;
        $this->dispatch('open-dokter-modal');
        $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $formRules = $this->rules();
            if (!$this->dokterId) {
                unset($formRules['kode_dokter']);
            }
            $this->validate($formRules);

            $attempts = 0;
            $maxAttempts = 5;
            $success = false;

            while (!$success && $attempts < $maxAttempts) {
                try {
                    $dokter = $this->dokterId 
                        ? MstDokter::withTrashed()->findOrFail($this->dokterId) 
                        : new MstDokter();

                    if (!$this->dokterId && empty($this->kode_dokter)) {
                        $this->kode_dokter = $this->generateKodeDokter();
                    }

                    $dokter->fill([
                        'kode_dokter' => $this->kode_dokter,
                        'nama_dokter' => $this->nama_dokter,
                        'nik' => $this->nik,
                        'jenis_kelamin' => $this->jenis_kelamin,
                        'tempat_lahir' => $this->tempat_lahir,
                        'tanggal_lahir' => $this->tanggal_lahir,
                        'alamat' => $this->alamat,
                        'no_telepon' => $this->no_telepon,
                        'agama' => $this->agama,
                        'spesialisasi' => $this->spesialisasi,
                        'no_sip' => $this->no_sip,
                        'no_str' => $this->no_str,
                        'status' => $this->status ?? 'Aktif',
                    ]);

                    $dokter->save();

                    if ($this->status === 'Aktif' && $dokter->trashed()) {
                        $dokter->restore();
                    } elseif ($this->status === 'Tidak Aktif' && !$dokter->trashed()) {
                        $dokter->delete();
                    }

                    $success = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'kode_dokter')) {
                        if (!$this->dokterId) {
                            $attempts++;
                            $this->kode_dokter = $this->generateKodeDokter();
                            continue; 
                        }
                    }
                    throw $e;
                }
            }

            if (!$success) {
                throw new \Exception("Gagal menghasilkan Kode Dokter yang unik setelah beberapa kali percobaan.");
            }

            $this->dispatch('close-dokter-modal');
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data dokter berhasil diperbarui!' : 'Dokter baru berhasil ditambahkan!']);
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
        $dokter = MstDokter::findOrFail($id);
        $dokter->update(['status' => 'Tidak Aktif']);
        $dokter->delete(); // Soft Delete
        
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status dokter telah diubah menjadi Tidak Aktif (Soft Delete)!']);
    }

    public function render()
    {
        $query = MstDokter::withTrashed();
        
        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }
        
        $this->dokterList = $query->get();
        $this->totalDokter = MstDokter::withTrashed()->count();
        $this->totalSpesialis = MstDokter::withTrashed()->whereNotNull('spesialisasi')->where('spesialisasi', '!=', '')->count();
        $this->takAktif = MstDokter::withTrashed()->where('status', 'Tidak Aktif')->count();

        return <<<'HTML'
        <div x-data="{ 
            showModal: false,
            initDataTable() {
                const tableId = '#dokterTable';
                if ($.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().destroy();
                }
                const table = $(tableId).DataTable({
                    scrollX: false,
                    dom: 'lrtip',
                    language: {
                        lengthMenu: '_MENU_',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                        infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                        infoFiltered: '(disaring dari total _MAX_ data)',
                        zeroRecords: 'Tidak ada data yang ditemukan',
                        emptyTable: 'Tidak ada data dalam tabel',
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
        @open-dokter-modal.window="showModal = true"
        @close-dokter-modal.window="showModal = false"
        @refresh-table.window="$nextTick(() => initDataTable())"
        x-init="initDataTable()">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon">
                        <i class="ri-nurse-line"></i>
                    </div>
                    <h1>Dokter</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate>
                        <i class="ri-database-2-line"></i>
                    </a>
                    <span class="sep">/</span>
                    <a href="#">Master</a>
                    <span class="sep">/</span>
                    <span>Data Dokter</span>
                </div>
            </div>

            <!-- Summary Widgets -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info">
                            <i class="ri-user-star-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Dokter</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalDokter) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-subtle text-primary">
                            <i class="ri-medal-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Spesialis</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalSpesialis) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger">
                            <i class="ri-user-unfollow-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Dokter Tak Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <!-- Unified Action Bar (Modern & High-Density) -->
                <div class="p-4 border-b border-[#eff2f7] bg-white">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Left Side: Filter Tabs -->
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom">
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button">
                                        <i class="ri-layout-grid-line"></i>
                                        <span>Semua Dokter</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button">
                                        <i class="ri-user-follow-line"></i>
                                        <span>Dokter Aktif</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button">
                                        <i class="ri-user-unfollow-line"></i>
                                        <span>Tidak Aktif</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Right Side: Search & Actions Group -->
                        <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                            <!-- Search Input -->
                            <div class="relative flex-grow md:flex-none">
                                <input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg bg-[#f3f6f9] border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari data dokter...">
                                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                            </div>
                            
                            <!-- Utility Actions (Print & Export) -->
                            <div class="flex items-center gap-1.5 p-1 bg-[#f3f6f9] rounded-lg border border-[#e9ecef]">
                                <a href="{{ route('master.dokter.print') }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-white hover:shadow-sm transition-all" title="Cetak PDF">
                                    <i class="ri-printer-line text-lg"></i>
                                </a>
                                <div class="w-[1px] h-4 bg-[#e9ecef]"></div>
                                <a href="{{ route('master.dokter.export') }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-white hover:shadow-sm transition-all" title="Unduh Excel">
                                    <i class="ri-file-excel-2-line text-lg"></i>
                                </a>
                            </div>

                            <!-- Visual Divider -->
                            <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>

                            <!-- Primary Action: Add Button -->
                            <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95">
                                <i class="ri-add-line text-lg"></i>
                                <span class="font-semibold text-xs uppercase tracking-wider">Tambah Dokter</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive bg-white">
                        <table id="dokterTable" class="display w-full">
                        <thead>
                            <tr>
                                <th>Kode Dokter</th>
                                <th>Nama Dokter</th>
                                <th>Spesialisasi</th>
                                <th>No. SIP</th>
                                <th>No. Telepon</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dokterList as $dokter)
                            <tr wire:key="dokter-{{ $dokter->id }}">
                                <td><span class="font-semibold text-[#405189]">{{ $dokter->kode_dokter }}</span></td>
                                <td>{{ $dokter->nama_dokter }}</td>
                                <td>{{ $dokter->spesialisasi ?? '-' }}</td>
                                <td>{{ $dokter->no_sip ?? '-' }}</td>
                                <td>{{ $dokter->no_telepon ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $dokter->status == 'Aktif' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $dokter->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-center gap-2">
                                        <button wire:click="edit({{ $dokter->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button @click="
                                            Swal.fire({
                                                title: 'Konfirmasi',
                                                text: 'Apakah Anda yakin ingin mengubah status dokter ini menjadi Tidak Aktif?',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#f06548',
                                                cancelButtonColor: '#6c757d',
                                                confirmButtonText: 'Ya, Nonaktifkan!',
                                                cancelButtonText: 'Batal',
                                                reverseButtons: true
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    $wire.delete({{ $dokter->id }})
                                                }
                                            })
                                        " class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

            <!-- Modal: Tambah/Edit Dokter -->
            <div x-show="showModal" 
                 class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 x-transition.opacity
                 style="display: none;">
                
                <div x-show="showModal"
                     x-transition.scale.95
                     class="w-full max-w-5xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057]">
                            {{ $isEdit ? 'Ubah Data Dokter' : 'Registrasi Dokter Baru' }}
                        </h5>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save" id="dokterForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Identitas Utama</h6>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Kode Dokter <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="kode_dokter" class="w-full rounded-lg border-gray-100 bg-gray-50/50 text-sm px-4 py-2.5 font-bold text-[#405189] @error('kode_dokter') border-red-400 @enderror" readonly tabindex="-1">
                                        @error('kode_dokter') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                        <input type="text" x-ref="firstInput" wire:model="nama_dokter" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('nama_dokter') border-red-400 @enderror" placeholder="Contoh: drg. Ahmad Sulaiman">
                                        @error('nama_dokter') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">NIK (Nomor Induk Kependudukan)</label>
                                        <input type="text" wire:model="nik" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('nik') border-red-400 @enderror" placeholder="16 Digit Nomor KTP">
                                        @error('nik') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                                            <select wire:model="jenis_kelamin" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('jenis_kelamin') border-red-400 @enderror">
                                                <option value="">Pilih</option>
                                                <option value="Laki-laki">Laki-laki</option>
                                                <option value="Perempuan">Perempuan</option>
                                            </select>
                                            @error('jenis_kelamin') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1 text-red-600 font-bold">Spesialisasi</label>
                                            <input type="text" wire:model="spesialisasi" class="w-full rounded-lg border-red-100 bg-red-50/30 text-sm px-4 py-2.5 focus:border-red-400 transition-all font-semibold" placeholder="Contoh: Bedah Mulut">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Tempat Lahir</label>
                                            <input type="text" wire:model="tempat_lahir" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('tempat_lahir') border-red-400 @enderror" placeholder="Kota Lahir">
                                            @error('tempat_lahir') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Tgl. Lahir</label>
                                            <input type="date" wire:model="tanggal_lahir" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('tanggal_lahir') border-red-400 @enderror">
                                            @error('tanggal_lahir') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#0ab39c] uppercase tracking-widest border-b pb-2">Kontak & Legalitas</h6>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Alamat Domisili</label>
                                        <textarea wire:model="alamat" rows="2" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all @error('alamat') border-red-400 @enderror" placeholder="Alamat lengkap..."></textarea>
                                        @error('alamat') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">No. SIP</label>
                                            <input type="text" wire:model="no_sip" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Surat Izin Praktik">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">No. STR</label>
                                            <input type="text" wire:model="no_str" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Surat Tanda Registrasi">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nomor Telepon/WA</label>
                                        <input type="text" wire:model="no_telepon" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('no_telepon') border-red-400 @enderror" placeholder="08xxxx">
                                        @error('no_telepon') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-2 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full {{ $status === 'Aktif' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                                            <span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status Dokter</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-extrabold {{ $status === 'Aktif' ? 'text-green-600' : 'text-red-500' }}">
                                                {{ strtoupper($status) }}
                                            </span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" class="sr-only peer" 
                                                    {{ $status === 'Aktif' ? 'checked' : '' }}
                                                    @click="$wire.set('status', '{{ $status === 'Aktif' ? 'Tidak Aktif' : 'Aktif' }}')">
                                                <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0ab39c]"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600">
                            <i class="ri-arrow-go-back-line"></i>
                            Batal
                        </button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <i wire:loading.remove wire:target="save" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}</span>
                            <span wire:loading wire:target="save">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}

