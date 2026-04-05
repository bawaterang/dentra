<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstPasien;
use Illuminate\Validation\Rule;

class PasienPage extends Component
{
    // Form Properties
    public $pasienId;
    public $no_rm, $nama_pasien, $nik, $jenis_kelamin, $tempat_lahir, $tanggal_lahir;
    public $alamat, $no_telepon, $agama, $pekerjaan, $no_penjamin, $golongan_darah, $alergi, $status;
    
    // View Properties
    public $pasienList = [];
    public $totalPasien = 0;
    public $pasienBaru = 0;
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
            'no_rm' => ['required', 'string', 'max:20', Rule::unique('mst_pasien', 'no_rm')->ignore($this->pasienId)],
            'nama_pasien' => 'required|string|max:100',
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('mst_pasien', 'nik')->ignore($this->pasienId)],
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:20',
            'agama' => 'nullable|string|max:20',
            'pekerjaan' => 'nullable|string|max:50',
            'no_penjamin' => 'nullable|string|max:50',
            'golongan_darah' => 'nullable|string|max:5',
            'alergi' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['pasienId', 'no_rm', 'nama_pasien', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_telepon', 'agama', 'pekerjaan', 'no_penjamin', 'golongan_darah', 'alergi', 'isEdit']);
        $this->status = 'Aktif';
        $this->resetErrorBag();
    }

    private function generateNoRM()
    {
        $lastPasien = MstPasien::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = 1;
        if ($lastPasien && $lastPasien->no_rm) {
            $lastNumber = (int) substr($lastPasien->no_rm, 1);
            $nextNumber = $lastNumber + 1;
        }
        return 'P' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        $this->resetForm();
        $this->no_rm = $this->generateNoRM();
        $this->dispatch('open-pasien-modal');
        $this->dispatch('refresh-table');
    }

    public function history($id)
    {
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Fitur riwayat pasien sedang dalam pengembangan.']);
    }

    public function edit($id)
    {
        $this->resetForm();
        $pasien = MstPasien::withTrashed()->findOrFail($id);
        
        $this->pasienId = $pasien->id;
        $this->no_rm = $pasien->no_rm;
        $this->nama_pasien = $pasien->nama_pasien;
        $this->nik = $pasien->nik;
        $this->jenis_kelamin = $pasien->jenis_kelamin;
        $this->tempat_lahir = $pasien->tempat_lahir;
        $this->tanggal_lahir = $pasien->tanggal_lahir ? $pasien->tanggal_lahir->format('Y-m-d') : null;
        $this->alamat = $pasien->alamat;
        $this->no_telepon = $pasien->no_telepon;
        $this->agama = $pasien->agama;
        $this->pekerjaan = $pasien->pekerjaan;
        $this->no_penjamin = $pasien->no_penjamin;
        $this->golongan_darah = $pasien->golongan_darah;
        $this->alergi = $pasien->alergi;
        $this->status = $pasien->status;
        
        $this->isEdit = true;
        $this->dispatch('open-pasien-modal');
        $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            // 1. Remove 'no_rm' from rules if NEW record, because it's auto-generated
            $formRules = $this->rules();
            if (!$this->pasienId) {
                unset($formRules['no_rm']);
            }
            $this->validate($formRules);

            // 2. Attempt save with collision handling
            $attempts = 0;
            $maxAttempts = 5;
            $success = false;

            while (!$success && $attempts < $maxAttempts) {
                try {
                    $pasien = $this->pasienId 
                        ? MstPasien::withTrashed()->findOrFail($this->pasienId) 
                        : new MstPasien();

                    if (!$this->pasienId && empty($this->no_rm)) {
                        $this->no_rm = $this->generateNoRM();
                    }

                    $pasien->fill([
                        'no_rm' => $this->no_rm,
                        'nama_pasien' => $this->nama_pasien,
                        'nik' => $this->nik,
                        'jenis_kelamin' => $this->jenis_kelamin,
                        'tempat_lahir' => $this->tempat_lahir,
                        'tanggal_lahir' => $this->tanggal_lahir,
                        'alamat' => $this->alamat,
                        'no_telepon' => $this->no_telepon,
                        'agama' => $this->agama,
                        'pekerjaan' => $this->pekerjaan,
                        'no_penjamin' => $this->no_penjamin,
                        'golongan_darah' => $this->golongan_darah,
                        'alergi' => $this->alergi,
                        'status' => $this->status ?? 'Aktif',
                    ]);

                    $pasien->save();

                    // Handle Soft Deletes Sync
                    if ($this->status === 'Aktif' && $pasien->trashed()) {
                        $pasien->restore();
                    } elseif ($this->status === 'Tidak Aktif' && !$pasien->trashed()) {
                        $pasien->delete();
                    }

                    $success = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Check if it's a unique constraint violation for 'no_rm'
                    if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'no_rm')) {
                        if (!$this->pasienId) {
                            $attempts++;
                            $this->no_rm = $this->generateNoRM(); // Explicitly regenerate
                            continue; 
                        }
                    }
                    throw $e;
                }
            }

            if (!$success) {
                throw new \Exception("Gagal menghasilkan Nomor RM yang unik setelah beberapa kali percobaan.");
            }

            $this->dispatch('close-pasien-modal');
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data pasien berhasil diperbarui!' : 'Pasien baru berhasil ditambahkan!']);
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
        $pasien = MstPasien::withTrashed()->findOrFail($id);
        
        if ($pasien->status === 'Tidak Aktif') {
            $this->dispatch('alert', [
                'type' => 'info', 
                'message' => 'Informasi: Pasien dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu jika ingin memproses kembali.'
            ]);
            return;
        }

        $pasien->update(['status' => 'Tidak Aktif']);
        $pasien->delete(); // Soft Delete
        
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status pasien telah diubah menjadi Tidak Aktif (Soft Delete)!']);
    }

    public function render()
    {
        $query = MstPasien::withTrashed();
        
        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }
        
        $this->pasienList = $query->get();
        $this->totalPasien = MstPasien::withTrashed()->count();
        $this->pasienBaru = MstPasien::withTrashed()->where('created_at', '>=', now()->subDays(30))->count();
        $this->takAktif = MstPasien::withTrashed()->where('status', 'Tidak Aktif')->count();

        return <<<'HTML'
        <div x-data="{ 
            showModal: false,
            initDataTable() {
                const tableId = '#pasienTable';
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
                    // Re-init DataTable whenever modal state changes just in case
                    $nextTick(() => this.initDataTable());
                });
                // Call it twice for safety after initial mount
                $nextTick(() => this.initDataTable());
            }
        }" 
        @open-pasien-modal.window="showModal = true"
        @close-pasien-modal.window="showModal = false"
        @refresh-table.window="$nextTick(() => initDataTable())"
        x-init="initDataTable()">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon">
                        <i class="ri-user-heart-line"></i>
                    </div>
                    <h1>Pasien</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate>
                        <i class="ri-database-2-line"></i>
                    </a>
                    <span class="sep">/</span>
                    <a href="#">Master</a>
                    <span class="sep">/</span>
                    <span>Data Pasien</span>
                </div>
            </div>

            <!-- Summary Widgets -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info">
                            <i class="ri-user-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Pasien</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalPasien) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success">
                            <i class="ri-user-add-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Pasien Baru</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($pasienBaru) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger">
                            <i class="ri-user-unfollow-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Pasien Tak Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <!-- Unified Action Bar (Modern & High-Density) -->
                <div class="p-4 border-b border-[#eff2f7]">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Left Side: Filter Tabs -->
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom">
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button">
                                        <i class="ri-layout-grid-line"></i>
                                        <span>Semua Pasien</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button">
                                        <i class="ri-user-follow-line"></i>
                                        <span>Pasien Aktif</span>
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
                                <input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari data pasien...">
                                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                            </div>
                            
                            <!-- Utility Actions (Print & Export) -->
                            <div class="flex items-center gap-1.5 p-1 rounded-lg border border-[#e9ecef]">
                                <a href="{{ route('master.pasien.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-white hover:shadow-sm transition-all" title="Cetak PDF">
                                    <i class="ri-printer-line text-lg"></i>
                                </a>
                                <div class="w-[1px] h-4 bg-[#e9ecef]"></div>
                                <a href="{{ route('master.pasien.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-white hover:shadow-sm transition-all" title="Unduh Excel">
                                    <i class="ri-file-excel-2-line text-lg"></i>
                                </a>
                            </div>

                            <!-- Visual Divider -->
                            <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>

                            <!-- Primary Action: Add Button -->
                            <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                <i class="ri-add-line text-lg"></i>
                                <span class="font-semibold text-xs uppercase tracking-wider">Tambah Pasien</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive dark:bg-transparent">
                        <table id="pasienTable" class="table align-middle table-nowrap w-full">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>NIK</th>
                                <th>Jenis Kelamin</th>
                                <th>No. Telepon</th>
                                <th>Status</th>
                                <th class="!text-center" style="text-align: center !important;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pasienList as $pasien)
                            <tr wire:key="pasien-{{ $pasien->id }}">
                                <td><span class="font-semibold text-[#405189]">{{ $pasien->no_rm }}</span></td>
                                <td>{{ $pasien->nama_pasien }}</td>
                                <td>{{ $pasien->nik ?? '-' }}</td>
                                <td>{{ $pasien->jenis_kelamin }}</td>
                                <td>{{ $pasien->no_telepon ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $pasien->status == 'Aktif' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $pasien->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <button wire:click="history({{ $pasien->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#0ab39c]/10 text-[#0ab39c] hover:bg-[#0ab39c] hover:text-white transition-all" title="Riwayat">
                                            <i class="ri-history-line"></i>
                                        </button>
                                        <button wire:click="edit({{ $pasien->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button @click="
                                            if ('{{ $pasien->status }}' === 'Tidak Aktif') {
                                                Swal.fire({
                                                    title: 'Informasi',
                                                    text: 'Pasien dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.',
                                                    icon: 'info',
                                                    confirmButtonColor: '#405189'
                                                });
                                            } else {
                                                Swal.fire({
                                                    title: 'Konfirmasi',
                                                    text: 'Apakah Anda yakin ingin mengubah status pasien ini menjadi Tidak Aktif?',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#f06548',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: 'Ya, Nonaktifkan!',
                                                    cancelButtonText: 'Batal',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.delete({{ $pasien->id }})
                                                    }
                                                })
                                            }
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

            <!-- Integration Modal: Tambah/Edit Pasien -->
            <div x-show="showModal" 
                 class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 x-transition.opacity
                 style="display: none;">
                
                <div x-show="showModal"
                     x-transition.scale.95
                     class="w-full max-w-5xl bg-white rounded-xl shadow-2xl overflow-visible">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057]">
                            {{ $isEdit ? 'Ubah Data Pasien' : 'Registrasi Pasien Baru' }}
                        </h5>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-8 py-6 max-h-[75vh] overflow-visible">
                        <form wire:submit.prevent="save" id="pasienForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Core Identity Section -->
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Identitas Utama</h6>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nomor RM <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="no_rm" class="w-full rounded-lg border-gray-100 bg-gray-50/50 text-sm px-4 py-2.5 font-bold text-[#405189] @error('no_rm') border-red-400 @enderror" readonly tabindex="-1">
                                        @error('no_rm') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="nama_pasien" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('nama_pasien') border-red-400 @enderror" placeholder="Contoh: Budi Santoso">
                                        @error('nama_pasien') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">NIK (Nomor Induk Kependudukan)</label>
                                        <input type="text" wire:model="nik" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('nik') border-red-400 @enderror" placeholder="16 Digit Nomor KTP">
                                        @error('nik') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                                            <x-custom-dropdown 
                                                model="jenis_kelamin" 
                                                :options="[
                                                    ['value' => 'Laki-laki', 'label' => 'Laki-laki', 'icon' => 'ri-men-line text-blue-500'],
                                                    ['value' => 'Perempuan', 'label' => 'Perempuan', 'icon' => 'ri-women-line text-pink-500']
                                                ]"
                                                placeholder="Pilih Jenis Kelamin"
                                            />
                                            @error('jenis_kelamin') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Gol. Darah</label>
                                            <x-custom-dropdown 
                                                model="golongan_darah" 
                                                :options="[
                                                    ['value' => 'A', 'label' => 'A'],
                                                    ['value' => 'B', 'label' => 'B'],
                                                    ['value' => 'AB', 'label' => 'AB'],
                                                    ['value' => 'O', 'label' => 'O']
                                                ]"
                                                placeholder="Golongan Darah"
                                                icon="ri-drop-line"
                                            />
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

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nomor Telepon/WA</label>
                                        <input type="text" wire:model="no_telepon" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('no_telepon') border-red-400 @enderror" placeholder="08xxxx">
                                        @error('no_telepon') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Contact & Clinical Section -->
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#0ab39c] uppercase tracking-widest border-b pb-2">Kontak & Klinis</h6>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Alamat Domisili</label>
                                        <textarea wire:model="alamat" rows="2" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all @error('alamat') border-red-400 @enderror" placeholder="Alamat lengkap..."></textarea>
                                        @error('alamat') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Agama</label>
                                            <x-custom-dropdown 
                                                model="agama" 
                                                :options="[
                                                    ['value' => 'Islam', 'label' => 'Islam'],
                                                    ['value' => 'Kristen', 'label' => 'Kristen'],
                                                    ['value' => 'Katolik', 'label' => 'Katolik'],
                                                    ['value' => 'Hindu', 'label' => 'Hindu'],
                                                    ['value' => 'Budha', 'label' => 'Budha'],
                                                    ['value' => 'Konghucu', 'label' => 'Konghucu'],
                                                    ['value' => 'Lainnya', 'label' => 'Lainnya']
                                                ]"
                                                placeholder="Pilih Agama"
                                                searchable="true"
                                                icon="ri-service-line"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Pekerjaan</label>
                                            <input type="text" wire:model="pekerjaan" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Pekerjaan">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">No. Penjamin (BPJS/Asuransi)</label>
                                        <input type="text" wire:model="no_penjamin" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Nomor Kartu">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 text-red-600 font-bold">Riwayat Alergi</label>
                                        <textarea wire:model="alergi" rows="2" class="w-full rounded-lg border-red-100 bg-red-50/30 text-sm px-4 py-2 focus:border-red-400 focus:ring-red-100 transition-all placeholder:text-red-300" placeholder="Sebutkan alergi jika ada..."></textarea>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-2 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full {{ $status === 'Aktif' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                                            <span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status Pasien</span>
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

                    <!-- Modal Footer -->
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