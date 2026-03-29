<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfilPage extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $avatar;
    public $avatar_file;
    public $current_password;
    public $new_password;
    public $confirm_password;

    public function mount()
    {
        $user = Auth::user();
        if ($user) {
            $this->name = $user->full_name;
            $this->email = $user->email;
            $this->avatar = $user->avatar;
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'avatar_file' => 'nullable|image|max:1024',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|same:confirm_password',
        ];
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();
        if (!$user) return;

        $user->full_name = $this->name;
        $user->email = $this->email;

        if ($this->avatar_file) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $this->avatar_file->store('avatars', 'public');
            $user->avatar = $path;
            $this->avatar = $path;
        }

        if (!empty($this->new_password)) {
            if (!Hash::check($this->current_password, $user->password)) {
                $this->addError('current_password', 'Password saat ini tidak sesuai.');
                return;
            }
            $user->password = Hash::make($this->new_password);
        }

        $user->save();

        $this->current_password = '';
        $this->new_password = '';
        $this->confirm_password = '';

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Profil pengguna berhasil diperbarui!']);
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon"><i class="ri-user-settings-line"></i></div>
                    <h1>Profil Pengguna</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
                    <span class="sep">/</span>
                    <span>Pengaturan</span>
                    <span class="sep">/</span>
                    <span>Profil Saya</span>
                </div>
            </div>

            <div class="max-w-5xl mx-auto mt-6">
                <!-- Cover and Profile Header -->
                <div class="card overflow-hidden shadow-sm rounded-xl mb-6 border-0">
                    <div class="h-32 bg-gradient-to-r from-[#405189] to-[#0ab39c] relative">
                         <!-- Decorative Banner overlay -->
                         <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                    </div>
                    <div class="px-8 pb-6 bg-white relative">
                        <div class="flex flex-col md:flex-row items-center md:items-end gap-6 -mt-12">
                            <div class="h-28 w-28 rounded-full bg-white p-1 shadow-lg relative shrink-0">
                                @if($avatar_file)
                                    <img src="{{ $avatar_file->temporaryUrl() }}" class="w-full h-full rounded-full object-cover">
                                @elseif($avatar)
                                    <img src="{{ asset('storage/'.$avatar) }}" class="w-full h-full rounded-full object-cover">
                                @else
                                    <div class="w-full h-full rounded-full bg-gradient-to-br from-blue-100 to-teal-50 flex items-center justify-center text-[#405189] text-4xl font-black border border-gray-100">
                                        {{ strtoupper(substr($name, 0, 2)) }}
                                    </div>
                                @endif
                                
                                <label class="absolute bottom-1 right-1 h-8 w-8 bg-white rounded-full shadow-md border border-gray-100 flex items-center justify-center text-[#405189] cursor-pointer hover:bg-gray-50 transition-all">
                                    <i class="ri-camera-fill"></i>
                                    <input type="file" wire:model="avatar_file" class="hidden" accept="image/*">
                                </label>
                            </div>
                            <div class="flex-1 text-center md:text-left mb-2 md:mb-0">
                                <h4 class="text-2xl font-bold text-gray-800">{{ $name }}</h4>
                                <p class="text-gray-500 font-medium">{{ $email }}</p>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-primary-subtle px-4 py-2 text-sm rounded-lg shadow-sm border border-blue-100"><i class="ri-shield-user-fill mr-1.5 text-[#405189]"></i>Administrator</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Sidebar (Stats/Info) -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="card shadow-sm rounded-xl border-t-2 border-[#0ab39c]">
                            <div class="p-5 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                                <h6 class="text-sm font-bold text-gray-700 m-0"><i class="ri-information-line mr-1 text-[#0ab39c]"></i> Informasi Akun</h6>
                            </div>
                            <div class="p-5">
                                <ul class="space-y-4 text-sm">
                                    <li class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Status Keanggotaan</span>
                                        <span class="font-bold text-green-600 flex items-center gap-1.5"><i class="ri-checkbox-circle-fill"></i> Aktif</span>
                                    </li>
                                    <li class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Bergabung Sejak</span>
                                        <span class="font-semibold text-gray-800">{{ \Illuminate\Support\Facades\Auth::user()->created_at->translatedFormat('d F Y') }}</span>
                                    </li>
                                    <li class="flex flex-col">
                                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Terakhir Login</span>
                                        <span class="font-semibold text-gray-800">{{ now()->translatedFormat('d F Y') }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Right Form -->
                    <div class="lg:col-span-2">
                        <form wire:submit.prevent="save">
                            <div class="card shadow-sm rounded-xl border border-gray-100">
                                <div class="p-6 border-b border-gray-50 bg-gray-50/30">
                                    <h5 class="text-md font-bold text-gray-800 flex items-center gap-2">
                                        <i class="ri-user-settings-fill text-[#405189]"></i> Edit Profil & Keamanan
                                    </h5>
                                    <p class="text-xs text-gray-500 mt-1">Perbarui informasi personal dan kata sandi untuk keamanan akun Anda.</p>
                                </div>
                                
                                <div class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><i class="ri-user-line text-gray-400"></i></div>
                                                <input type="text" wire:model="name" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all font-semibold shadow-sm text-gray-800">
                                            </div>
                                            @error('name') <span class="text-[11px] text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Alamat Email</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><i class="ri-mail-line text-gray-400"></i></div>
                                                <input type="email" wire:model="email" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all text-gray-800 shadow-sm">
                                            </div>
                                            @error('email') <span class="text-[11px] text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="pt-5 mt-4 border-t border-gray-100 relative">
                                        <div class="absolute -top-3 left-4 bg-white px-2">
                                            <h6 class="text-[11px] font-bold text-[#0ab39c] uppercase tracking-widest"><i class="ri-lock-password-line mr-1"></i> Ubah Kata Sandi</h6>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-orange-50/30 p-5 rounded-xl border border-orange-100/50 space-y-5">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kata Sandi Saat Ini</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><i class="ri-lock-unlock-line text-gray-400"></i></div>
                                                <input type="password" wire:model="current_password" autocomplete="off" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-orange-400 focus:ring focus:ring-orange-400/20 transition-all shadow-sm" placeholder="Kosongkan jika tidak ada perubahan">
                                            </div>
                                            @error('current_password') <span class="text-[11px] text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kata Sandi Baru</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><i class="ri-lock-password-line text-gray-400"></i></div>
                                                    <input type="password" wire:model="new_password" autocomplete="off" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all shadow-sm" placeholder="Minimal 8 karakter">
                                                </div>
                                                @error('new_password') <span class="text-[11px] text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi Sandi Baru</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><i class="ri-checkbox-circle-line text-gray-400"></i></div>
                                                    <input type="password" wire:model="confirm_password" autocomplete="off" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all shadow-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="p-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100 rounded-b-xl">
                                    <button type="button" class="btn btn-soft-secondary font-bold text-sm px-6 hover:-translate-y-0.5 transition-transform" wire:click="mount">Batal</button>
                                    <button type="submit" class="btn btn-primary font-bold text-sm px-8 shadow-md hover:-translate-y-0.5 transition-transform hover:shadow-lg"><i class="ri-save-3-line mr-1.5"></i> Simpan Perubahan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
