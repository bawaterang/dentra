<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserPage extends Component
{
    // Form Properties
    public $userId;
    public $username, $email, $password, $full_name, $phone;
    public $is_active = true;
    public $color = '#405189';
    public $user_code;

    // View Properties
    public $selectedStatus = 'all';
    public $isEdit = false;
    public $activeTab = 'users'; // 'users' or 'mapping'

    // Mapping Properties
    public $selectedUserId = '';
    public $mappedPolis = [];

    // View data as public properties
    public $users = [];
    public $allUsers = [];
    public $allPolis = [];
    public $totalUsers = 0;
    public $activeUsers = 0;
    public $inactiveUsers = 0;

    protected function rules()
    {
        $rules = [
            'username' => 'required|string|max:255|unique:mst_user,username,' . $this->userId,
            'email' => 'required|email|max:255|unique:mst_user,email,' . $this->userId,
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:20',
        ];

        if (!$this->isEdit || $this->password) {
            $rules['password'] = 'required|string|min:6';
        }

        return $rules;
    }

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->dispatch('refresh-table');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->dispatch('refresh-table');
    }

    public function updatedSelectedUserId($value)
    {
        if ($value) {
            $user = User::with('polis')->find($value);
            if ($user) {
                $this->mappedPolis = $user->polis->pluck('id')->toArray();
            } else {
                $this->mappedPolis = [];
            }
        } else {
            $this->mappedPolis = [];
        }
    }

    public function saveMapping()
    {
        if (!$this->selectedUserId) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Silakan pilih User terlebih dahulu!']);
            return;
        }

        try {
            $user = User::findOrFail($this->selectedUserId);
            $user->polis()->sync($this->mappedPolis);
            
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Pemetaan User ke Poli berhasil disimpan!']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Pemetaan Gagal: ' . $e->getMessage()]);
        }
    }

    public function resetForm()
    {
        $this->reset(['userId', 'username', 'email', 'password', 'full_name', 'phone', 'user_code']);
        $this->is_active = true;
        $this->color = '#405189';
        $this->isEdit = false;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->user_code = $this->generateUserCode();
        $this->dispatch('open-user-modal');
    }

    private function generateUserCode()
    {
        $lastUser = User::orderBy('id', 'desc')->first();
        $nextNumber = 1;
        if ($lastUser && $lastUser->user_code) {
            $lastNumber = (int) substr($lastUser->user_code, 1);
            $nextNumber = $lastNumber + 1;
        }
        return 'U' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function edit($id)
    {
        $this->resetForm();
        $user = User::findOrFail($id);
        
        $this->userId = $user->id;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->full_name = $user->full_name;
        $this->phone = $user->phone;
        $this->is_active = $user->is_active;
        $this->color = $user->color ?? '#405189';
        $this->user_code = $user->user_code;
        $this->password = ''; 
        
        $this->isEdit = true;
        $this->dispatch('open-user-modal');
    }

    public function save()
    {
        try {
            $this->validate();

            $user = $this->userId ? User::findOrFail($this->userId) : new User();

            if (!$this->userId) {
                if (empty($this->user_code)) {
                    $this->user_code = $this->generateUserCode();
                }
                $user->user_code = $this->user_code;
            }

            $user->username = $this->username;
            $user->email = $this->email;
            $user->full_name = $this->full_name;
            $user->phone = $this->phone;
            $user->is_active = $this->is_active;
            $user->color = $this->color;

            if ($this->password) {
                $user->password = Hash::make($this->password);
            }

            $user->save();

            $this->dispatch('close-user-modal');
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data User berhasil diperbarui!' : 'User baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        if ($id == auth()->id()) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri!']);
            return;
        }
        
        $user = User::findOrFail($id);
        $user->delete();
        
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'User berhasil dihapus!']);
    }

    public function toggleActive($id)
    {
        if ($id == auth()->id()) {
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri!']);
            return;
        }

        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status user berhasil diubah!']);
    }

    public function render()
    {
        // For Users Tab
        $query = User::query();
        if ($this->selectedStatus === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->selectedStatus === 'Tidak Aktif') {
            $query->where('is_active', false);
        }
        $this->users = $query->orderBy('full_name')->get();

        // For Mapping Tab
        $this->allUsers = User::where('is_active', true)->orderBy('full_name')->get();
        $this->allPolis = \App\Models\MstPoli::whereNull('deleted_at')->get();

        $this->totalUsers = User::count();
        $this->activeUsers = User::where('is_active', true)->count();
        $this->inactiveUsers = User::where('is_active', false)->count();

        return <<<'HTML'
        <div x-data="{ 
            showModal: false, 
            initDataTable() {
                const t='#userTable';
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()}
                const tb=$(t).DataTable({
                    scrollX:false,
                    dom:'lrtip',
                    language:{
                        lengthMenu:'_MENU_',
                        info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                        infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',
                        infoFiltered:'(disaring dari total _MAX_ data)',
                        zeroRecords:'Tidak ada data yang ditemukan',
                        emptyTable:'Tidak ada data dalam tabel',
                        paginate:{
                            previous:'<i class=ri-arrow-left-s-line></i>',
                            next:'<i class=ri-arrow-right-s-line></i>'
                        }
                    }
                });
                $('#customSearch').off('keyup').on('keyup',function(){tb.search(this.value).draw()})
            },
            init() {
                this.$watch('showModal', v => { if(v){ $nextTick(()=>this.$refs.firstInput && this.$refs.firstInput.focus()) } $nextTick(()=>this.initDataTable()) });
                $nextTick(()=>this.initDataTable());
            }
        }" 
        @open-user-modal.window="showModal = true" 
        @close-user-modal.window="showModal = false"
        @refresh-table.window="$nextTick(()=>initDataTable())"
        x-init="initDataTable()">
            
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon">
                        <i class="ri-user-settings-line"></i>
                    </div>
                    <h1>Setting User</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a>
                    <span class="sep">/</span><a href="#">Setting</a>
                    <span class="sep">/</span><span>User</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 text-[#405189] shrink-0">
                            <i class="ri-team-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total User</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalUsers) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-500 shrink-0">
                            <i class="ri-user-follow-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">User Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($activeUsers) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 text-red-500 shrink-0">
                            <i class="ri-user-unfollow-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">User Tak Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($inactiveUsers) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="card mb-6">
                <div class="card-body p-0">
                    <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 border-b border-gray-200">
                        <li class="me-2">
                            <button wire:click="switchTab('users')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg transition-all {{ $activeTab === 'users' ? 'text-[#405189] border-[#405189] font-bold bg-[#405189]/5' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <i class="ri-user-settings-line mr-2 text-lg"></i>
                                Manajemen User
                            </button>
                        </li>
                        <li class="me-2">
                            <button wire:click="switchTab('mapping')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg transition-all {{ $activeTab === 'mapping' ? 'text-[#f7b84b] border-[#f7b84b] font-bold bg-[#f7b84b]/5' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <i class="ri-hospital-line mr-2 text-lg"></i>
                                Pemetaan User ke Poli
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            @if($activeTab === 'users')
            <div class="card overflow-hidden border-t-2 border-[#405189] animate-fade-in-up">
                <div class="p-4 border-b border-[#eff2f7]">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Left Side: Filter Tabs -->
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom">
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button">
                                        <i class="ri-layout-grid-line"></i>
                                        <span>Semua User</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button">
                                        <i class="ri-user-follow-line"></i>
                                        <span>Aktif</span>
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

                        <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                            <!-- Search Input -->
                            <div class="relative flex-grow md:flex-none">
                                <input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari nama, username...">
                                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                            </div>

                            <!-- Utility Actions (Print & Export) - Standard Layout -->
                            <div class="flex items-center gap-1.5 p-1 rounded-lg border border-[#e9ecef]">
                                <a href="{{ route('setting.user.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-indigo-50 hover:shadow-sm transition-all" title="Cetak PDF">
                                    <i class="ri-printer-line text-lg"></i>
                                </a>
                                <div class="w-[1px] h-4 bg-[#e9ecef]"></div>
                                <a href="{{ route('setting.user.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-emerald-50 hover:shadow-sm transition-all" title="Unduh Excel">
                                    <i class="ri-file-excel-2-line text-lg"></i>
                                </a>
                            </div>

                            <!-- Visual Divider -->
                            <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>

                            <!-- Primary Action: Add Button (Standard Color) -->
                            <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                <i class="ri-add-line text-lg"></i>
                                <span class="font-semibold text-xs uppercase tracking-wider">Tambah User</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="userTable" class="table align-middle table-nowrap mb-0 w-full">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th width="5%">No</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Identitas User</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Username</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider text-center">Status</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Kontak</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider !text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $index => $user)
                                <tr wire:key="user-row-{{ $user->id }}" class="hover:bg-gray-50/50 transition-colors">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if($user->avatar)
                                                <img src="{{ Storage::url($user->avatar) }}" class="h-9 w-9 rounded-full object-cover border border-gray-200">
                                            @else
                                                <div class="h-9 w-9 flex items-center justify-center rounded-lg text-white font-bold text-xs shrink-0 shadow-sm" style="background-color: {{ $user->color ?? '#405189' }}">
                                                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <h6 class="mb-0 text-sm font-bold text-[#495057]">{{ $user->full_name }}</h6>
                                                <p class="text-[11px] text-[#878a99] font-medium uppercase tracking-tight">{{ $user->user_code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-[#405189] border border-gray-100 px-2 py-1 flex items-center gap-1 w-fit">
                                            <i class="ri-user-follow-line"></i> {{ $user->username }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <label class="relative inline-flex flex-col items-center cursor-pointer group">
                                                <input type="checkbox" class="sr-only peer" 
                                                    {{ $user->is_active ? 'checked' : '' }}
                                                    wire:change="toggleActive({{ $user->id }})">
                                                <div class="relative w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-[#0ab39c] mb-1"></div>
                                                <span class="text-[9px] font-extrabold {{ $user->is_active ? 'text-emerald-500' : 'text-red-400' }} uppercase tracking-widest group-hover:opacity-75 transition-opacity">
                                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                <i class="ri-mail-line text-indigo-400"></i> {{ $user->email }}
                                            </div>
                                            @if($user->phone)
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                <i class="ri-phone-line text-emerald-400"></i> {{ $user->phone }}
                                            </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex justify-center gap-2">
                                            <button wire:click="edit({{ $user->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-[#405189] hover:text-white transition-all shadow-sm" title="Edit User">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button @click="
                                                Swal.fire({
                                                    title: 'Hapus User?',
                                                    text: 'Tindakan ini tidak dapat dibatalkan!',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#f06548',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: 'Ya, Hapus!',
                                                    cancelButtonText: 'Batal',
                                                    background: '#fff',
                                                    customClass: {
                                                        title: 'font-bold text-[#495057]',
                                                        confirmButton: 'btn btn-danger px-4',
                                                        cancelButton: 'btn btn-light px-4'
                                                    }
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.delete({{ $user->id }})
                                                    }
                                                })
                                            " class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus User">
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
            @endif

            @if($activeTab === 'mapping')
            <!-- TAB: PEMETAAN (MAPPING) USER KE POLI -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up" x-data="{ poliSearch: '' }">
                <!-- Select User Side -->
                <div class="card border-t-2 border-[#f7b84b] relative" style="overflow: visible !important;">
                    <div class="p-5 border-b border-[#eff2f7] bg-[#f3f6f9]/50">
                        <h6 class="text-sm font-bold text-[#f7b84b]"><i class="ri-user-star-line mr-2"></i>Pilih User Target</h6>
                        <p class="text-xs text-gray-500 mt-1">Pilih user untuk mengatur akses poli.</p>
                    </div>
                    <div class="p-5">
                        <x-custom-dropdown 
                            model="selectedUserId" 
                            :options="collect($allUsers)->map(fn($u) => ['value' => $u->id, 'label' => $u->full_name . ' (' . $u->username . ')', 'icon' => 'ri-user-3-fill text-[#405189]'])->toArray()"
                            placeholder="Pilih User Target"
                            searchable="true"
                            icon="ri-user-search-line"
                            live="true"
                        />
                        
                        @if($selectedUserId)
                            @php $selU = collect($allUsers)->firstWhere('id', (int)$selectedUserId); @endphp
                            <div class="mt-4 p-4 rounded-xl bg-orange-50 border border-orange-100">
                                <div class="flex items-center gap-3 mb-3">
                                    @if($selU->avatar)
                                        <img src="{{ Storage::url($selU->avatar) }}" class="h-10 w-10 rounded-full object-cover border border-gray-200">
                                    @else
                                        <div class="h-10 w-10 flex items-center justify-center rounded-full text-white font-bold" style="background-color: {{ $selU->color ?? '#405189' }}">
                                            {{ strtoupper(substr($selU->full_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="font-bold text-[#495057] mb-0">{{ $selU->full_name ?? '' }}</h6>
                                        <p class="text-xs text-gray-500">{{ $selU->username ?? '' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="badge bg-[#f7b84b] text-white px-2 py-1"><i class="ri-hospital-line mr-1"></i> {{ count($mappedPolis) }} Poli Terpilih</span>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 p-6 rounded-xl border border-dashed border-gray-300 flex flex-col items-center justify-center text-center">
                                <i class="ri-focus-3-line text-3xl text-gray-300 mb-2"></i>
                                <span class="text-sm text-gray-500">Gunakan dropdown di atas untuk memilih user.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Select Polis Side -->
                <div class="card overflow-hidden lg:col-span-2 border-t-2 border-[#0ab39c] relative" style="overflow: visible !important;">
                    @if(!$selectedUserId)
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm z-50 flex flex-col items-center justify-center border border-gray-200 shadow-sm rounded-lg m-2">
                        <i class="ri-lock-2-line text-4xl text-gray-400 mb-3"></i>
                        <h5 class="text-gray-600 font-bold">Akses Pemetaan Terkunci</h5>
                        <p class="text-sm text-gray-500">Pilih User di panel sebelah kiri untuk membuka daftar poli.</p>
                    </div>
                    @endif

                    <div class="p-5 border-b border-[#eff2f7] flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-[#f3f6f9]/50">
                        <div>
                            <h6 class="text-sm font-bold text-[#0ab39c]"><i class="ri-hospital-line mr-2"></i>Daftar Poli (Beri Centang)</h6>
                            <p class="text-xs text-gray-500 mt-1">Satu user dapat memegang akses ke beberapa poli.</p>
                        </div>
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="poliSearch" class="h-9 w-full rounded-lg border border-[#e9ecef] pl-9 pr-3 text-xs outline-none focus:border-[#0ab39c] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari poli...">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-[#878a99] text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="p-0">
                        <div class="max-h-[500px] overflow-y-auto w-full grid grid-cols-1 sm:grid-cols-2 gap-0">
                            @foreach($allPolis as $poli)
                                <label x-show="poliSearch === '' || '{{ strtolower($poli->nama_poli) }}'.includes(poliSearch.toLowerCase()) || '{{ strtolower($poli->kode_poli) }}'.includes(poliSearch.toLowerCase())" class="border-b border-r border-[#eff2f7] p-4 flex items-center gap-4 cursor-pointer hover:bg-teal-50/30 transition-colors group">
                                    <div class="flex items-center justify-center w-6 h-6 shrink-0">
                                        <input type="checkbox" wire:model="mappedPolis" value="{{ $poli->id }}" class="w-5 h-5 text-[#0ab39c] bg-gray-100 border-gray-300 rounded focus:ring-[#0ab39c] transition-all cursor-pointer">
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 font-bold text-xs">
                                            <i class="ri-building-3-line"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-sm font-bold text-[#495057] group-hover:text-[#0ab39c] transition-colors">{{ $poli->nama_poli }}</h6>
                                            <p class="text-xs text-gray-500 mb-0 mt-0.5">{{ $poli->kode_poli }}</p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="p-5 border-t border-[#eff2f7] bg-gray-50 flex justify-end">
                        <button type="button" wire:click="saveMapping" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center gap-2 transition-all hover:bg-blue-600 hover:translate-y-[-2px] hover:shadow-lg">
                            <i class="ri-save-3-line text-lg"></i> <span class="font-bold">Simpan Pemetaan</span>
                        </button>
                    </div>
                </div>
            </div>
            @endif
            <!-- Modal Form -->
            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Data User' : 'Tambah User Baru' }}</h5>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button>
                    </div>

                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save">
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">User Code <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input type="text" wire:model="user_code" class="w-full rounded-lg border-gray-100 bg-gray-50/50 text-sm pl-10 pr-4 h-[42px] font-bold text-[#405189]" readonly tabindex="-1">
                                            <i class="ri-fingerprint-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input type="text" wire:model="full_name" x-ref="firstInput" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="John Doe">
                                            <i class="ri-user-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        @error('full_name') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Username <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input type="text" wire:model="username" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="johndoe">
                                            <i class="ri-links-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        @error('username') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Email <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input type="email" wire:model="email" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="john@example.com">
                                            <i class="ri-mail-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        @error('email') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Telepon</label>
                                        <div class="relative">
                                            <input type="text" wire:model="phone" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="081xxx">
                                            <i class="ri-phone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        @error('phone') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Password {{ $isEdit ? '(Isi jika ingin diubah)' : '*' }}</label>
                                        <div class="relative">
                                            <input type="password" wire:model="password" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="••••••••">
                                            <i class="ri-lock-password-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        @error('password') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 items-end">
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-tight mb-2">Pilih Warna Profil</label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach(['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#878a99', '#6559cc', '#f672a7'] as $c)
                                                <button type="button" 
                                                    wire:click="$set('color', '{{ $c }}')"
                                                    class="w-7 h-7 rounded-full border-2 transition-all hover:scale-110 {{ $color === $c ? 'border-gray-800 ring-2 ring-gray-200' : 'border-transparent' }}"
                                                    style="background-color: {{ $c }}">
                                                </button>
                                            @endforeach
                                            <input type="color" wire:model.live="color" class="w-7 h-7 rounded-full border-none p-0 cursor-pointer overflow-hidden bg-transparent">
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full {{ $is_active ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                                            <span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status Login</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-extrabold {{ $is_active ? 'text-green-600' : 'text-red-500' }}">
                                                {{ $is_active ? 'AKTIF' : 'NONAKTIF' }}
                                            </span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" class="sr-only peer" 
                                                    {{ $is_active ? 'checked' : '' }}
                                                    @click="$wire.set('is_active', !{{ $is_active ? 'true' : 'false' }})">
                                                <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0ab39c]"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Batal</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70">
                            <svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <i wire:loading.remove wire:target="save" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
