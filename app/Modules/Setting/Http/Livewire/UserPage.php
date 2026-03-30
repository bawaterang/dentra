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

    // View data as public properties
    public $users = [];
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
        $query = User::query();
        
        if ($this->selectedStatus === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->selectedStatus === 'Tidak Aktif') {
            $query->where('is_active', false);
        }

        $this->users = $query->orderBy('full_name')->get();
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

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <!-- Total User -->
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

                <!-- Active User -->
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

                <!-- Inactive User -->
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

            <div class="card overflow-hidden border-t-2 border-[#405189]">
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
                                    <th class="font-semibold text-xs uppercase tracking-wider">User</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Username</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Email</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Telepon</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Status</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider !text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr wire:key="user-{{ $user->id }}" class="hover:bg-gray-50/50 transition-colors">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if($user->avatar)
                                                <img src="{{ Storage::url($user->avatar) }}" class="h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm" alt="avatar">
                                            @else
                                                <div class="h-10 w-10 flex items-center justify-center rounded-full text-white font-bold shadow-sm" style="background-color: {{ $user->color ?? '#405189' }}">
                                                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <h6 class="mb-0 text-sm font-bold text-[#495057]">{{ $user->full_name }}</h6>
                                                <p class="text-xs text-gray-500 mb-0 mt-0.5">{{ $user->user_code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-sm text-gray-700 font-medium">{{ $user->username }}</span></td>
                                    <td><span class="text-sm text-gray-700"><i class="ri-mail-line text-gray-400 mr-1"></i> {{ $user->email }}</span></td>
                                    <td><span class="text-sm text-gray-700"><i class="ri-phone-line text-gray-400 mr-1"></i> {{ $user->phone ?? '-' }}</span></td>
                                    <td>
                                        <button wire:click="toggleActive({{ $user->id }})" class="group flex items-center gap-1.5 transition-all">
                                            <div class="w-8 h-4 bg-gray-200 rounded-full relative transition-colors duration-200 ease-in-out {{ $user->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                                <div class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform duration-200 ease-in-out {{ $user->is_active ? 'translate-x-4' : 'translate-x-0' }}"></div>
                                            </div>
                                            <span class="text-xs font-semibold {{ $user->is_active ? 'text-emerald-600' : 'text-gray-500' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-2">
                                            <button wire:click="edit({{ $user->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button @click="
                                                Swal.fire({
                                                    title: 'Hapus User?',
                                                    text: 'Tindakan ini akan menghapus user secara permanen.',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#f06548',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: 'Ya, Hapus!',
                                                    cancelButtonText: 'Batal',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.delete({{ $user->id }})
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
