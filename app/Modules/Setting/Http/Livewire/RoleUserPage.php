<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\MstRoleUser;
use App\Models\User;
use Illuminate\Support\Facades\Storage;


class RoleUserPage extends Component
{
    use WithPagination;

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
    public $search = '';
    public $selectedStatus = 'all';

    protected $queryString = ['search', 'selectedStatus', 'activeTab'];

    #[Computed]
    public function roleList()
    {
        $query = MstRoleUser::withCount('users');

        if ($this->selectedStatus === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->selectedStatus === 'Tidak Aktif') {
            $query->where('is_active', false);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nama_role', 'like', '%'.$this->search.'%')
                    ->orWhere('deskripsi', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('nama_role')->paginate(10);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

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
        $this->resetPage();
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
        // For Mapping Tab Data
        $this->allRoles = MstRoleUser::where('is_active', true)->get();
        $this->listUsers = User::where('is_active', true)->orderBy('full_name')->get();

        // Stats
        $this->totalRoles = MstRoleUser::count();
        $this->activeRolesCount = MstRoleUser::where('is_active', true)->count();
        $this->inactiveRolesCount = MstRoleUser::where('is_active', false)->count();

        return <<<'HTML'
        <div x-data="{ showRoleModal: false, init(){this.$watch('showRoleModal',v=>{if(v){$nextTick(()=>{this.$refs.roleInput&&this.$refs.roleInput.focus()})}})} }" @open-role-modal.window="showRoleModal=true" @close-role-modal.window="showRoleModal=false" x-init="init()">
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .role-row:hover {
                    background-color: #d8dce1ff !important;
                    transition: all 0.3s ease;
                }
                .action-btn-soft {
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: all 0.2s ease;
                }
                .search-focus-glow:focus {
                    box-shadow: 0 0 0 4px rgba(64, 81, 137, 0.15);
                    border-color: #f6f7fbff;
                }
                .pagination-custom nav span.relative.z-0 { 
                    display: flex !important; 
                    gap: 4px !important; 
                    flex-wrap: wrap !important;
                    justify-content: center !important;
                }
                .pagination-custom nav a, 
                .pagination-custom nav span[aria-disabled="true"] span,
                .pagination-custom nav span[aria-current="page"] span {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    min-width: 38px !important;
                    height: 38px !important;
                    padding: 0 12px !important;
                    border-radius: 8px !important;
                    border: 1px solid #767070ff !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    transition: all 0.2s ease-in-out !important;
                    background-color: #ffffff !important;
                    color: #475569 !important;
                    text-decoration: none !important;
                }
                .pagination-custom nav a:hover {
                    background-color: #f1f5f9 !important;
                    border-color: #405189 !important;
                    color: #405189 !important;
                    transform: translateY(-1px) !important;
                }
                .pagination-custom nav p.text-sm {
                    display: none !important;
                }
                .pagination-custom nav > div:last-child > div:first-child {
                    display: none !important;
                }
                .pagination-custom [aria-current="page"], 
                .pagination-custom [aria-current="page"] *,
                .pagination-custom .active,
                .pagination-custom .active * {
                    background-color: #405189 !important;
                    color: #ffffff !important;
                    border-color: #405189 !important;
                    box-shadow: 0 4px 10px rgba(64, 81, 137, 0.3) !important;
                    z-index: 10 !important;
                }
            </style>

            
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-shield-user-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Role & Hak Akses</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Definisikan peran pengguna dan batasan hak akses fitur sistem.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Pengaturan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Role User</span>
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
                        
                        <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                            <div class="relative flex-grow min-w-[280px]">
                                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-[#405189]"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" 
                                       class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                       placeholder="Cari nama role atau deskripsi...">
                            </div>

                            <button wire:click="createRole" class="btn btn-primary h-10 px-6 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full lg:w-auto">
                                <i class="ri-add-line text-xl"></i>
                                <span class="font-bold text-xs uppercase tracking-wider">Tambah Role</span>
                            </button>
                        </div>

                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama Role</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Deskripsi</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Anggota</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->roleList as $role)
                            <tr wire:key="role-row-{{ $role->id }}" class="role-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shadow-inner">
                                            <i class="ri-shield-user-fill text-lg"></i>
                                        </div>
                                        <span class="font-bold text-[#405189] text-sm">{{ $role->nama_role }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs text-gray-500 whitespace-normal line-clamp-2 max-w-xs">{{ $role->deskripsi ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if($role->is_active)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase tracking-widest border border-emerald-100">
                                            <span class="h-1 w-1 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-600 text-[9px] font-black uppercase tracking-widest border border-rose-100">
                                            <span class="h-1 w-1 rounded-full bg-rose-500"></span> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 text-[#405189] text-[11px] font-black border border-indigo-100 uppercase tracking-tight">
                                        <i class="ri-group-line mr-1"></i> {{ $role->users_count }} Users
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="editRole({{ $role->id }})" class="action-btn-soft bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white shadow-sm" title="Edit">
                                            <i class="ri-edit-line text-sm"></i>
                                        </button>
                                        <button @click="Swal.fire({title:'Hapus Role?',text:'Tindakan ini tidak dapat dibatalkan!',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Hapus!',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.deleteRole({{ $role->id }})}})" 
                                                class="action-btn-soft bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white shadow-sm" title="Hapus">
                                            <i class="ri-delete-bin-line text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                            <i class="ri-shield-keyhole-line text-6xl text-gray-200"></i>
                                        </div>
                                        <p class="text-xl font-black text-gray-400">Role Tidak Ditemukan</p>
                                        <p class="text-xs text-gray-300 mt-1 uppercase tracking-widest font-bold">Cobalah menyesuaikan filter atau kata kunci pencarian Anda</p>
                                        <button @click="$wire.set('search', '')" class="mt-6 text-[#405189] font-bold text-xs uppercase tracking-wider hover:underline">
                                            <i class="ri-refresh-line"></i> Reset Pencarian
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($this->roleList->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->roleList->firstItem() }} - {{ $this->roleList->lastItem() }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->roleList->total()) }}</span> 
                            <span class="hidden sm:inline">role ditemukan</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->roleList->links() }}
                    </div>
                </div>
                @endif
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
