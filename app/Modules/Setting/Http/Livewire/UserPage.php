<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;


use App\Traits\HasAccessControl;

class UserPage extends Component
{
    use WithPagination, HasAccessControl;

    public function mount()
    {
        $this->authorizeAccess('/setting/user');
    }

    // Form Properties

    public $userId;
    public $username, $email, $password, $full_name, $phone;
    public $is_active = true;
    public $color = '#405189';
    public $user_code;
    public $isEdit = false;

    // View Properties
    public $activeTab = 'users'; // 'users' or 'mapping'
    public $search = '';
    public $selectedStatus = 'all';

    protected $queryString = ['search', 'selectedStatus', 'activeTab'];

    #[Computed]
    public function userList()
    {
        $query = User::query();

        if ($this->selectedStatus === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->selectedStatus === 'Tidak Aktif') {
            $query->where('is_active', false);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('username', 'like', '%'.$this->search.'%')
                    ->orWhere('full_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('user_code', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('full_name')->paginate(10);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    // Mapping Properties
    public $selectedUserId = '';
    public $mappedPolis = [];

    // View data as public properties
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
            $rules['password'] = 'required|string|min:8|regex:/[a-z]/|regex:/[0-9]/';
        }

        return $rules;
    }

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->resetPage();
    }


    public function switchTab($tab)
    {
        $this->activeTab = $tab;
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
        
        $this->dispatch('alert', ['type' => 'success', 'message' => 'User berhasil dihapus!']);
    }


    public function toggleActive($id)
    {
        if ($id == auth()->id()) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri!']);
            return;
        }

        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status user berhasil diubah!']);
    }


    public function render()
    {
        // For Mapping Tab
        $this->allUsers = User::where('is_active', true)->orderBy('full_name')->get();
        $this->allPolis = \App\Models\MstPoli::whereNull('deleted_at')->get();

        $this->totalUsers = User::count();
        $this->activeUsers = User::where('is_active', true)->count();
        $this->inactiveUsers = User::where('is_active', false)->count();

        return view('livewire.modules.setting.user-page');
    }
}
