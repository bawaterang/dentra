<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\MstRoleUser;
use App\Models\User;
use Illuminate\Support\Facades\Storage;


use App\Traits\HasAccessControl;

class RoleUserPage extends Component
{
    use WithPagination, HasAccessControl;

    public function mount()
    {
        $this->authorizeAccess('/setting/role-user');
    }

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

        return view('livewire.modules.setting.role-user-page');
    }
}
