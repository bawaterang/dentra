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
    public $phone;
    public $avatar;
    public $avatar_file;
    public $signature;
    public $signature_file;
    public $signature_draw; // Base64 from canvas
    public $login_terakhir;
    public $current_password;
    public $new_password;
    public $confirm_password;

    public function mount()
    {
        $user = Auth::user();
        if ($user) {
            $this->name = $user->full_name;
            $this->email = $user->email;
            $this->phone = $user->phone;
            $this->avatar = $user->avatar;
            $this->signature = $user->signature;
            $this->login_terakhir = $user->login_terakhir;
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar_file' => 'nullable|image|max:1024',
            'signature_file' => 'nullable|image|max:1024',
            'signature_draw' => 'nullable|string',
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
        $user->phone = $this->phone;

        if ($this->avatar_file) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $this->avatar = $this->avatar_file->store('avatars', 'public');
            $user->avatar = $this->avatar;
        }

        if ($this->signature_file) {
            if ($user->signature) Storage::disk('public')->delete($user->signature);
            $this->signature = $this->signature_file->store('signatures', 'public');
            $user->signature = $this->signature;
        } elseif ($this->signature_draw) {
            if ($user->signature) Storage::disk('public')->delete($user->signature);
            
            $imageData = $this->signature_draw;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $fileName = 'signatures/' . uniqid() . '.png';
            Storage::disk('public')->put($fileName, base64_decode($imageData));
            
            $this->signature = $fileName;
            $user->signature = $this->signature;
            $this->signature_draw = null;
        }

        if (!empty($this->new_password)) {
            if (!Hash::check($this->current_password, $user->password)) {
                $this->addError('current_password', 'Password saat ini tidak sesuai.');
                return;
            }
            $user->password = Hash::make($this->new_password);
        }

        $user->save();

        if (!empty($this->new_password)) {
            Auth::logout();
            session()->flash('success', 'Password berhasil diubah. Silakan login kembali dengan password baru Anda.');
            return redirect()->route('login');
        }

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
                                        <span class="font-semibold text-gray-800">{{ $login_terakhir ? $login_terakhir->translatedFormat('d F Y, H:i') : '-' }}</span>
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
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">No. Telepon</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><i class="ri-phone-line text-gray-400"></i></div>
                                                <input type="text" wire:model="phone" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all text-gray-800 shadow-sm">
                                            </div>
                                            @error('phone') <span class="text-[11px] text-red-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <!-- Signature Section -->
                                    <div class="pt-5 mt-4 border-t border-gray-100 relative">
                                        <div class="absolute -top-3 left-4 bg-white px-2">
                                            <h6 class="text-[11px] font-bold text-[#405189] uppercase tracking-widest"><i class="ri-quill-pen-line mr-1"></i> Tanda Tangan Digital</h6>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ 
                                        mode: 'draw', 
                                        canvas: null, 
                                        ctx: null, 
                                        drawing: false,
                                        initCanvas() {
                                            this.canvas = this.$refs.sigCanvas;
                                            this.ctx = this.canvas.getContext('2d');
                                            this.ctx.strokeStyle = '#000';
                                            this.ctx.lineWidth = 2;
                                            this.ctx.lineCap = 'round';
                                        },
                                        start(e) {
                                            this.drawing = true;
                                            const rect = this.canvas.getBoundingClientRect();
                                            this.ctx.beginPath();
                                            this.ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
                                        },
                                        draw(e) {
                                            if (!this.drawing) return;
                                            const rect = this.canvas.getBoundingClientRect();
                                            this.ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
                                            this.ctx.stroke();
                                        },
                                        stop() {
                                            this.drawing = false;
                                            @this.set('signature_draw', this.canvas.toDataURL());
                                        },
                                        clear() {
                                            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                                            @this.set('signature_draw', null);
                                        }
                                    }" x-init="initCanvas()">
                                        <div class="space-y-4">
                                            <div class="flex items-center gap-2 mb-2">
                                                <button type="button" @click="mode = 'draw'" :class="mode === 'draw' ? 'bg-[#405189] text-white shadow-md' : 'bg-gray-100 text-gray-600'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all">Tulis Langsung</button>
                                                <button type="button" @click="mode = 'upload'" :class="mode === 'upload' ? 'bg-[#405189] text-white shadow-md' : 'bg-gray-100 text-gray-600'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all">Upload Gambar</button>
                                            </div>

                                            <div x-show="mode === 'draw'" class="space-y-3">
                                                <div class="border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50 relative overflow-hidden h-48">
                                                    <canvas x-ref="sigCanvas" width="400" height="200" 
                                                        @mousedown="start" @mousemove="draw" @mouseup="stop" @mouseleave="stop"
                                                        class="w-full h-full cursor-crosshair"></canvas>
                                                    <button type="button" @click="clear" class="absolute top-2 right-2 h-8 w-8 bg-white rounded-full shadow-sm border border-gray-100 flex items-center justify-center text-red-500 hover:bg-red-50 transition-all">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                                <p class="text-[10px] text-gray-500"><i class="ri-information-line mr-1"></i>Gunakan mouse atau stylus untuk menulis tanda tangan di area kotak.</p>
                                            </div>

                                            <div x-show="mode === 'upload'" class="space-y-3">
                                                <div class="flex items-center justify-center w-full">
                                                    <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-all">
                                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                            <i class="ri-upload-cloud-2-line text-3xl text-gray-400 mb-2"></i>
                                                            <p class="mb-2 text-sm text-gray-500 font-semibold">Klik untuk upload</p>
                                                            <p class="text-xs text-gray-400">PNG, JPG atau JPEG (Maks. 1MB)</p>
                                                        </div>
                                                        <input type="file" wire:model="signature_file" class="hidden" accept="image/*" />
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center justify-center p-6 border rounded-xl bg-gray-50/30">
                                            <p class="text-xs font-bold text-gray-500 mb-4 uppercase tracking-widest">Tanda Tangan Saat Ini</p>
                                            <div class="h-40 w-full flex items-center justify-center border rounded-lg bg-white shadow-inner overflow-hidden">
                                                @if($signature_file)
                                                    <img src="{{ $signature_file->temporaryUrl() }}" class="max-h-full max-w-full object-contain">
                                                @elseif($signature)
                                                    <img src="{{ asset('storage/'.$signature) }}" class="max-h-full max-w-full object-contain">
                                                @else
                                                    <div class="text-center text-gray-300">
                                                        <i class="ri-quill-pen-line text-5xl opacity-20 block mb-2"></i>
                                                        <p class="text-[11px]">Belum ada tanda tangan</p>
                                                    </div>
                                                @endif
                                            </div>
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
                                    <button type="button" class="btn bg-gray-500 text-white font-bold text-sm px-6 hover:bg-gray-600 hover:-translate-y-0.5 transition-all" wire:click="mount">Batal</button>
                                    <button type="submit" class="btn bg-[#0d6efd] text-white font-bold text-sm px-8 shadow-md hover:bg-[#0b5ed7] hover:-translate-y-0.5 transition-all hover:shadow-lg"><i class="ri-save-3-line mr-1.5"></i> Simpan Perubahan</button>
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
