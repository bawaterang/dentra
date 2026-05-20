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
        return view('livewire.modules.setting.profil-page');
    }
}
