<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstAsuransi;
use Illuminate\Validation\Rule;

class AsuransiPage extends Component
{
    public $asuransiId;
    public $kode_asuransi, $nama_asuransi, $tipe_asuransi, $diskon, $no_telepon, $email, $alamat, $status;
    
    public $asuransiList = [];
    public $totalAsuransi = 0;
    public $asuransiAktif = 0;
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
            'kode_asuransi' => ['required', 'string', 'max:20', Rule::unique('mst_asuransi', 'kode_asuransi')->ignore($this->asuransiId)],
            'nama_asuransi' => 'required|string|max:100',
            'tipe_asuransi' => 'required|in:Pemerintah,Swasta,Lainnya',
            'diskon' => 'nullable|numeric|min:0|max:100',
            'no_telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'alamat' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['asuransiId', 'kode_asuransi', 'nama_asuransi', 'tipe_asuransi', 'diskon', 'no_telepon', 'email', 'alamat', 'isEdit']);
        $this->status = 'Aktif';
        $this->tipe_asuransi = 'Swasta';
        $this->diskon = 0;
        $this->resetErrorBag();
    }

    private function generateKode()
    {
        $last = MstAsuransi::withTrashed()->orderBy('id', 'desc')->first();
        $next = 1;
        if ($last && $last->kode_asuransi) {
            $num = (int) substr($last->kode_asuransi, 3);
            $next = $num + 1;
        }
        return 'ASR' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        $this->resetForm();
        $this->kode_asuransi = $this->generateKode();
        $this->dispatch('open-modal');
        $this->dispatch('refresh-table');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstAsuransi::withTrashed()->findOrFail($id);
        
        $this->asuransiId = $item->id;
        $this->kode_asuransi = $item->kode_asuransi;
        $this->nama_asuransi = $item->nama_asuransi;
        $this->tipe_asuransi = $item->tipe_asuransi;
        $this->diskon = $item->diskon;
        $this->no_telepon = $item->no_telepon;
        $this->email = $item->email;
        $this->alamat = $item->alamat;
        $this->status = $item->status;
        
        $this->isEdit = true;
        $this->dispatch('open-modal');
        $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $formRules = $this->rules();
            if (!$this->asuransiId) {
                unset($formRules['kode_asuransi']);
            }
            $this->validate($formRules);

            $attempts = 0;
            $maxAttempts = 5;
            $success = false;

            while (!$success && $attempts < $maxAttempts) {
                try {
                    $item = $this->asuransiId 
                        ? MstAsuransi::withTrashed()->findOrFail($this->asuransiId) 
                        : new MstAsuransi();

                    if (!$this->asuransiId && empty($this->kode_asuransi)) {
                        $this->kode_asuransi = $this->generateKode();
                    }

                    $item->fill([
                        'kode_asuransi' => $this->kode_asuransi,
                        'nama_asuransi' => $this->nama_asuransi,
                        'tipe_asuransi' => $this->tipe_asuransi,
                        'diskon' => $this->diskon,
                        'no_telepon' => $this->no_telepon,
                        'email' => $this->email,
                        'alamat' => $this->alamat,
                        'status' => $this->status ?? 'Aktif',
                    ]);
                    $item->save();

                    if ($this->status === 'Aktif' && $item->trashed()) {
                        $item->restore();
                    } elseif ($this->status === 'Tidak Aktif' && !$item->trashed()) {
                        $item->delete();
                    }
                    $success = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'kode_asuransi')) {
                        if (!$this->asuransiId) {
                            $attempts++;
                            $this->kode_asuransi = $this->generateKode();
                            continue;
                        }
                    }
                    throw $e;
                }
            }

            if (!$success) {
                throw new \Exception("Gagal menghasilkan kode unik setelah beberapa kali percobaan.");
            }

            $this->dispatch('close-modal');
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data asuransi berhasil diperbarui!' : 'Asuransi baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid. Periksa kembali kolom yang bertanda merah.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $item = MstAsuransi::withTrashed()->findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);
            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status asuransi telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $query = MstAsuransi::withTrashed();
        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }
        $this->asuransiList = $query->get();
        $this->totalAsuransi = MstAsuransi::withTrashed()->count();
        $this->asuransiAktif = MstAsuransi::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstAsuransi::withTrashed()->where('status', 'Tidak Aktif')->count();

        return <<<'HTML'
        <div x-data="{ 
            showModal: false,
            initDataTable() {
                const tableId = '#asuransiTable';
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
                        $nextTick(() => { this.$refs.firstInput && this.$refs.firstInput.focus() });
                    }
                    $nextTick(() => this.initDataTable());
                });
                $nextTick(() => this.initDataTable());
            }
        }" 
        @open-modal.window="showModal = true"
        @close-modal.window="showModal = false"
        @refresh-table.window="$nextTick(() => initDataTable())"
        x-init="initDataTable()">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <h1>Asuransi</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate>
                        <i class="ri-database-2-line"></i>
                    </a>
                    <span class="sep">/</span>
                    <a href="#">Master</a>
                    <span class="sep">/</span>
                    <span>Data Asuransi</span>
                </div>
            </div>

            <!-- Summary Widgets -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info">
                            <i class="ri-shield-check-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Asuransi</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalAsuransi) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success">
                            <i class="ri-checkbox-circle-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Asuransi Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($asuransiAktif) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger">
                            <i class="ri-close-circle-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Tidak Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7] bg-white">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom">
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button">
                                        <i class="ri-layout-grid-line"></i>
                                        <span>Semua Data</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button">
                                        <i class="ri-checkbox-circle-line"></i>
                                        <span>Aktif</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button">
                                        <i class="ri-close-circle-line"></i>
                                        <span>Tidak Aktif</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                            <div class="relative flex-grow md:flex-none">
                                <input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg bg-[#f3f6f9] border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari data asuransi...">
                                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                            </div>
                            
                            <div class="flex items-center gap-1.5 p-1 bg-[#f3f6f9] rounded-lg border border-[#e9ecef]">
                                <a href="{{ route('master.asuransi.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-white hover:shadow-sm transition-all" title="Cetak PDF">
                                    <i class="ri-printer-line text-lg"></i>
                                </a>
                                <div class="w-[1px] h-4 bg-[#e9ecef]"></div>
                                <a href="{{ route('master.asuransi.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-white hover:shadow-sm transition-all" title="Unduh Excel">
                                    <i class="ri-file-excel-2-line text-lg"></i>
                                </a>
                            </div>

                            <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>

                            <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                <i class="ri-add-line text-lg"></i>
                                <span class="font-semibold text-xs uppercase tracking-wider">Tambah Asuransi</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive bg-white">
                        <table id="asuransiTable" class="display w-full">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Asuransi</th>
                                <th>Tipe</th>
                                <th>Diskon (%)</th>
                                <th>No. Telepon</th>
                                <th>Status</th>
                                <th class="!text-center" style="text-align: center !important;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($asuransiList as $item)
                            <tr wire:key="asuransi-{{ $item->id }}">
                                <td><span class="font-semibold text-[#405189]">{{ $item->kode_asuransi }}</span></td>
                                <td>{{ $item->nama_asuransi }}</td>
                                <td>{{ $item->tipe_asuransi }}</td>
                                <td>{{ number_format($item->diskon, 2) }}%</td>
                                <td>{{ $item->no_telepon ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $item->status == 'Aktif' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <button wire:click="edit({{ $item->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button @click="
                                            if ('{{ $item->status }}' === 'Tidak Aktif') {
                                                Swal.fire({ title: 'Informasi', text: 'Data dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.', icon: 'info', confirmButtonColor: '#405189' });
                                            } else {
                                                Swal.fire({ title: 'Konfirmasi', text: 'Apakah Anda yakin ingin menonaktifkan data asuransi ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#f06548', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Nonaktifkan!', cancelButtonText: 'Batal', reverseButtons: true }).then((result) => { if (result.isConfirmed) { $wire.delete({{ $item->id }}) } })
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

            <!-- Modal -->
            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-3xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057]">
                            {{ $isEdit ? 'Ubah Data Asuransi' : 'Tambah Asuransi Baru' }}
                        </h5>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save" id="asuransiForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Informasi Utama</h6>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Kode Asuransi <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="kode_asuransi" x-ref="firstInput" class="w-full rounded-lg border-gray-100 bg-gray-50/50 text-sm px-4 py-2.5 font-bold text-[#405189] @error('kode_asuransi') border-red-400 @enderror" readonly tabindex="-1">
                                        @error('kode_asuransi') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Asuransi <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="nama_asuransi" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('nama_asuransi') border-red-400 @enderror" placeholder="Contoh: BPJS Kesehatan">
                                        @error('nama_asuransi') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Tipe Asuransi</label>
                                        <x-custom-dropdown 
                                            model="tipe_asuransi" 
                                            :options="[
                                                ['value' => 'Pemerintah', 'label' => 'Pemerintah', 'icon' => 'ri-government-line text-blue-500'],
                                                ['value' => 'Swasta', 'label' => 'Swasta', 'icon' => 'ri-building-line text-indigo-500'],
                                                ['value' => 'Lainnya', 'label' => 'Lainnya', 'icon' => 'ri-more-line text-gray-400']
                                            ]"
                                            placeholder="Pilih Tipe Asuransi"
                                        />
                                        @error('tipe_asuransi') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Diskon Default (%)</label>
                                        <input type="number" step="0.01" wire:model="diskon" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#0ab39c] uppercase tracking-widest border-b pb-2">Kontak & Alamat</h6>
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">No. Telepon</label>
                                        <input type="text" wire:model="no_telepon" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="08xxxx">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Email</label>
                                        <input type="email" wire:model="email" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('email') border-red-400 @enderror" placeholder="email@asuransi.com">
                                        @error('email') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Alamat</label>
                                        <textarea wire:model="alamat" rows="3" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all" placeholder="Alamat lengkap..."></textarea>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-2 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full {{ $status === 'Aktif' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                                            <span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status</span>
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
