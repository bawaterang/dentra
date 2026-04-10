<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use App\Models\MstRoleUser;
use App\Models\User;

class RoleUserPage extends Component
{
    public $activeTab = 'roles'; // 'roles' or 'mapping'

    // Form Properties - Role
    public $roleId;
    public $nama_role, $deskripsi;
    public $is_active = true;

    // Form Properties - Mapping
    public $selectedRoleId = '';
    public $searchUser = '';
    public $mappedUsers = [];

    public $isEdit = false;

    // View Properties
    public $selectedStatus = 'all';

    // View data as public properties
    public $roles = [];
    public $allRoles = [];
    public $listUsers = [];

    // Stats
    public $totalRoles = 0;
    public $activeRolesCount = 0;
    public $inactiveRolesCount = 0;

    protected function rules()
    {
        return [
            'nama_role' => 'required|string|max:100|unique:mst_role_user,nama_role,' . $this->roleId,
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->dispatch('refresh-table');
    }

    public function updatedSelectedRoleId($value)
    {
        if ($value) {
            $role = MstRoleUser::with('users')->find($value);
            if ($role) {
                // Populate the checkboxes with currently assigned users
                $this->mappedUsers = $role->users->pluck('id')->toArray();
            } else {
                $this->mappedUsers = [];
            }
        } else {
            $this->mappedUsers = [];
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->dispatch('refresh-table');
    }

    // --- Role Management Methods ---
    
    public function resetRoleForm()
    {
        $this->reset(['roleId', 'nama_role', 'deskripsi']);
        $this->is_active = true;
        $this->isEdit = false;
        $this->resetErrorBag();
    }

    public function createRole()
    {
        $this->resetRoleForm();
        $this->dispatch('open-role-modal');
    }

    public function editRole($id)
    {
        $this->resetRoleForm();
        $role = MstRoleUser::findOrFail($id);
        
        $this->roleId = $role->id;
        $this->nama_role = $role->nama_role;
        $this->deskripsi = $role->deskripsi;
        $this->is_active = $role->is_active;
        
        $this->isEdit = true;
        $this->dispatch('open-role-modal');
    }

    public function saveRole()
    {
        try {
            $this->validate();

            $role = $this->roleId ? MstRoleUser::findOrFail($this->roleId) : new MstRoleUser();

            $role->fill([
                'nama_role' => $this->nama_role,
                'deskripsi' => $this->deskripsi,
                'is_active' => $this->is_active,
            ]);

            $role->save();

            $this->dispatch('close-role-modal');
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Role berhasil diperbarui!' : 'Role baru berhasil ditambahkan!']);
            $this->resetRoleForm();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]);
        }
    }

    public function deleteRole($id)
    {
        $role = MstRoleUser::findOrFail($id);
        
        // Cek jika role memiliki users
        if ($role->users()->count() > 0) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Role tidak dapat dihapus karena masih memilki user yang terhubung!']);
            return;
        }

        $role->delete();
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Role berhasil dihapus!']);
    }

    // --- Mapping Methods ---
    
    public function saveMapping()
    {
        if (!$this->selectedRoleId) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Silakan pilih Role terlebih dahulu!']);
            return;
        }

        try {
            $role = MstRoleUser::findOrFail($this->selectedRoleId);
            
            // Sync uses an array of IDs to perfectly map the many-to-many relationship
            $role->users()->sync($this->mappedUsers);
            
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Pemetaan User ke Role berhasil disimpan!']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Pemetaan Gagal: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        // For Roles Tab Data
        $query = MstRoleUser::withCount('users');
        if ($this->selectedStatus === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->selectedStatus === 'Tidak Aktif') {
            $query->where('is_active', false);
        }

        $this->roles = $query->get();

        // For Mapping Tab Data
        $this->allRoles = MstRoleUser::where('is_active', true)->get();
        $this->listUsers = User::where('is_active', true)->orderBy('full_name')->get();

        // Stats
        $this->totalRoles = MstRoleUser::count();
        $this->activeRolesCount = MstRoleUser::where('is_active', true)->count();
        $this->inactiveRolesCount = MstRoleUser::where('is_active', false)->count();

        return <<<'HTML'
        <div x-data="{ 
            showRoleModal: false,
            initDataTable() {
                const t='#roleTable';
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()}
                if($(t).length) {
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
                    $('#customSearchRole').off('keyup').on('keyup',function(){tb.search(this.value).draw()})
                }
            },
            init(){
                this.$watch('showRoleModal',v=>{ if(v){$nextTick(()=>{this.$refs.roleInput&&this.$refs.roleInput.focus()})} $nextTick(()=>this.initDataTable())}); 
                $nextTick(()=>this.initDataTable())
            } 
        }" 
        @open-role-modal.window="showRoleModal = true" 
        @close-role-modal.window="showRoleModal = false"
        @refresh-table.window="$nextTick(()=>initDataTable())"
        x-init="initDataTable()">
            
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon">
                        <i class="ri-shield-user-line"></i>
                    </div>
                    <h1>Role User & Akses</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a>
                    <span class="sep">/</span><a href="#">Setting</a>
                    <span class="sep">/</span><span>Role User</span>
                </div>
            </div>

            <!-- Infographic Cards (Consistent with UserPage) -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 text-[#405189] shrink-0">
                            <i class="ri-shield-user-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Role</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalRoles) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-500 shrink-0">
                            <i class="ri-shield-check-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Role Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($activeRolesCount) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;">
                    <div class="flex items-center p-5 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 text-red-500 shrink-0">
                            <i class="ri-shield-cross-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Role Tak Aktif</p>
                            <h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($inactiveRolesCount) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="card mb-6">
                <div class="card-body p-0">
                    <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 border-b border-gray-200">
                        <li class="me-2">
                            <button wire:click="switchTab('roles')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg transition-all {{ $activeTab === 'roles' ? 'text-[#405189] border-[#405189] font-bold bg-[#405189]/5' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <i class="ri-shield-keyhole-line mr-2 text-lg"></i>
                                Manajemen Role
                            </button>
                        </li>
                        <li class="me-2">
                            <button wire:click="switchTab('mapping')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg transition-all {{ $activeTab === 'mapping' ? 'text-[#f7b84b] border-[#f7b84b] font-bold bg-[#f7b84b]/5' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <i class="ri-user-shared-line mr-2 text-lg"></i>
                                Pemetaan User ke Role
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            @if($activeTab === 'roles')
            <!-- TAB 1: MANAJEMEN ROLE -->
            <div class="card overflow-hidden border-t-2 border-[#405189] animate-fade-in-up">
                <div class="p-4 border-b border-[#eff2f7]">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Left: Filter Tabs -->
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom">
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button">
                                        <i class="ri-layout-grid-line"></i>
                                        <span>Semua Role</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button">
                                        <i class="ri-shield-check-line"></i>
                                        <span>Aktif</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button">
                                        <i class="ri-shield-cross-line"></i>
                                        <span>Tidak Aktif</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                            <div class="relative flex-grow md:flex-none">
                                <input type="text" id="customSearchRole" class="h-10 w-full md:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari nama role...">
                                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i>
                            </div>

                            <!-- Export Group -->
                            <div class="flex items-center gap-1.5 p-1 rounded-lg border border-[#e9ecef]">
                                <a href="{{ route('setting.role_user.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-indigo-50 hover:shadow-sm transition-all" title="Cetak PDF">
                                    <i class="ri-printer-line text-lg"></i>
                                </a>
                                <div class="w-[1px] h-4 bg-[#e9ecef]"></div>
                                <a href="{{ route('setting.role_user.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-emerald-50 hover:shadow-sm transition-all" title="Unduh Excel">
                                    <i class="ri-file-excel-2-line text-lg"></i>
                                </a>
                            </div>

                            <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>

                            <button wire:click="createRole" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto">
                                <i class="ri-add-line text-lg"></i><span class="font-semibold text-xs uppercase tracking-wider">Tambah Role</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="roleTable" class="table align-middle table-nowrap mb-0 w-full">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th width="5%">No</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Nama Role</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Deskripsi</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Status</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Pengguna Terhubung</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider !text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $index => $role)
                                <tr wire:key="role-row-{{ $role->id }}" class="hover:bg-gray-50/50 transition-colors">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="h-8 w-8 rounded-lg bg-[#405189]/10 text-[#405189] flex items-center justify-center"><i class="ri-shield-user-fill"></i></div>
                                            <span class="text-sm font-bold text-[#495057]">{{ $role->nama_role }}</span>
                                        </div>
                                    </td>
                                    <td><span class="text-sm text-gray-500 whitespace-normal line-clamp-2 max-w-xs">{{ $role->deskripsi ?? '-' }}</span></td>
                                    <td>
                                        @if($role->is_active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 text-xs">
                                            <i class="ri-group-line mr-1"></i> {{ $role->users_count }} Users
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-2">
                                            <button wire:click="editRole({{ $role->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button @click="
                                                Swal.fire({
                                                    title: 'Hapus Role?',
                                                    text: 'Tindakan ini permanen. Pastikan tidak ada user yang terhubung.',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#f06548',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: 'Ya, Hapus!',
                                                    cancelButtonText: 'Batal',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.deleteRole({{ $role->id }})
                                                    }
                                                })
                                            " class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
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
            <!-- TAB 2: PEMETAAN (MAPPING) USER KE ROLE -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up" x-data="{ userSearch: '' }">
                <!-- Select Role Side -->
                <div class="card border-t-2 border-[#f7b84b] relative" style="overflow: visible !important;">
                    <div class="p-5 border-b border-[#eff2f7] bg-[#f3f6f9]/50">
                        <h6 class="text-sm font-bold text-[#f7b84b]"><i class="ri-shield-flash-line mr-2"></i>Pilih Role Target</h6>
                        <p class="text-xs text-gray-500 mt-1">Pilih role untuk mengatur anggota user.</p>
                    </div>
                    <div class="p-5">
                        <x-custom-dropdown 
                            model="selectedRoleId" 
                            :options="collect($allRoles)->map(fn($r) => ['value' => $r->id, 'label' => $r->nama_role, 'icon' => 'ri-shield-user-fill text-[#405189]'])->toArray()"
                            placeholder="Pilih Role Target"
                            searchable="true"
                            icon="ri-shield-star-line"
                            live="true"
                        />
                        
                        @if($selectedRoleId)
                            @php $selR = collect($allRoles)->firstWhere('id', (int)$selectedRoleId); @endphp
                            <div class="mt-4 p-4 rounded-xl bg-orange-50 border border-orange-100">
                                <h6 class="font-bold text-[#495057] mb-1">{{ $selR->nama_role ?? '' }}</h6>
                                <p class="text-xs text-gray-600 mb-3">{{ ($selR->deskripsi ?? '') ?: 'Tidak ada deskripsi' }}</p>
                                <div class="flex items-center gap-2">
                                    <span class="badge bg-[#f7b84b] text-white px-2 py-1"><i class="ri-user-follow-line mr-1"></i> {{ count($mappedUsers) }} User Terpilih</span>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 p-6 rounded-xl border border-dashed border-gray-300 flex flex-col items-center justify-center text-center">
                                <i class="ri-focus-3-line text-3xl text-gray-300 mb-2"></i>
                                <span class="text-sm text-gray-500">Gunakan dropdown di atas untuk memilih role.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Select Users Side -->
                <div class="card overflow-hidden lg:col-span-2 border-t-2 border-[#0ab39c] relative" style="overflow: visible !important;">
                    <!-- Overlay if no role selected -->
                    @if(!$selectedRoleId)
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm z-50 flex flex-col items-center justify-center border border-gray-200 shadow-sm rounded-lg m-2">
                        <i class="ri-lock-2-line text-4xl text-gray-400 mb-3"></i>
                        <h5 class="text-gray-600 font-bold">Akses Pemetaan Terkunci</h5>
                        <p class="text-sm text-gray-500">Pilih Role di panel sebelah kiri untuk membuka daftar user.</p>
                    </div>
                    @endif

                    <div class="p-5 border-b border-[#eff2f7] flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-[#f3f6f9]/50">
                        <div>
                            <h6 class="text-sm font-bold text-[#0ab39c]"><i class="ri-group-line mr-2"></i>Daftar User (Beri Centang)</h6>
                            <p class="text-xs text-gray-500 mt-1">Satu user dapat memegang beberapa role sekaligus.</p>
                        </div>
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="userSearch" class="h-9 w-full rounded-lg border border-[#e9ecef] pl-9 pr-3 text-xs outline-none focus:border-[#0ab39c] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari user (Filter UI)...">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-[#878a99] text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="p-0">
                        <div class="max-h-[500px] overflow-y-auto w-full grid grid-cols-1 sm:grid-cols-2 gap-0">
                            @foreach($listUsers as $user)
                                <label x-show="userSearch === '' || '{{ strtolower($user->full_name) }}'.includes(userSearch.toLowerCase()) || '{{ strtolower($user->username) }}'.includes(userSearch.toLowerCase())" class="border-b border-r border-[#eff2f7] p-4 flex items-center gap-4 cursor-pointer hover:bg-teal-50/30 transition-colors group">
                                    <div class="flex items-center justify-center w-6 h-6 shrink-0">
                                        <input type="checkbox" wire:model="mappedUsers" value="{{ $user->id }}" class="w-5 h-5 text-[#0ab39c] bg-gray-100 border-gray-300 rounded focus:ring-[#0ab39c] transition-all cursor-pointer">
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @if($user->avatar)
                                            <img src="{{ Storage::url($user->avatar) }}" class="h-9 w-9 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="h-9 w-9 flex items-center justify-center rounded-full text-white font-bold text-xs" style="background-color: {{ $user->color ?? '#0ab39c' }}">
                                                {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0 text-sm font-bold text-[#495057] group-hover:text-[#0ab39c] transition-colors">{{ $user->full_name }}</h6>
                                            <p class="text-xs text-gray-500 mb-0 mt-0.5">{{ $user->username }}</p>
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

            <!-- Modal Form Role -->
            <div x-show="showRoleModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showRoleModal" x-transition.scale.95 class="w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Role' : 'Tambah Role Baru' }}</h5>
                        <button @click="showRoleModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button>
                    </div>

                    <div class="px-8 py-6">
                        <form wire:submit.prevent="saveRole">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Role <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" wire:model="nama_role" x-ref="roleInput" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Contoh: Admin Pendaftaran">
                                        <i class="ri-shield-star-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    @error('nama_role') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Deskripsi</label>
                                    <textarea wire:model="deskripsi" rows="3" class="w-full rounded-lg border-gray-200 text-sm p-3 focus:border-[#405189] transition-all" placeholder="Penjelasan mengenai hak akses role ini..."></textarea>
                                    @error('deskripsi') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-2 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full {{ $is_active ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                                        <span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status Role</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-extrabold {{ $is_active ? 'text-green-600' : 'text-red-500' }}">
                                            {{ $is_active ? 'AKTIF' : 'OFF' }}
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
                        </form>
                    </div>

                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showRoleModal = false" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Batal</button>
                        <button type="button" wire:click="saveRole" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70">
                            <i wire:loading.remove wire:target="saveRole" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="saveRole">Simpan</span>
                            <span wire:loading wire:target="saveRole">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
