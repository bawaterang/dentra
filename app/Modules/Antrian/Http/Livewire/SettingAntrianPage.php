<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use App\Models\MstSettingAntrian;

class SettingAntrianPage extends Component
{
    public $mode_antrian;
    public $jam_buka;
    public $jam_tutup;
    public $durasi_slot;
    public $max_antrian;
    public $running_text;
    public $is_active;

    public function mount()
    {
        $setting = MstSettingAntrian::first();
        if ($setting) {
            $this->mode_antrian = $setting->mode_antrian;
            $this->jam_buka = $setting->jam_buka;
            $this->jam_tutup = $setting->jam_tutup;
            $this->durasi_slot = $setting->durasi_slot;
            $this->max_antrian = $setting->max_antrian;
            $this->running_text = $setting->running_text;
            $this->is_active = $setting->is_active;
        } else {
            // Default values
            $this->mode_antrian = 'Nomor Urut';
            $this->jam_buka = '08:00';
            $this->jam_tutup = '21:00';
            $this->durasi_slot = 15;
            $this->max_antrian = 50;
            $this->is_active = true;
        }
    }

    public function rules()
    {
        return [
            'mode_antrian' => 'required|in:Nomor Urut,Waktu Periksa,Keduanya',
            'jam_buka' => 'required|string',
            'jam_tutup' => 'required|string',
            'durasi_slot' => 'required|integer|min:5|max:120',
            'max_antrian' => 'required|integer|min:1|max:500',
            'running_text' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function resetSettings()
    {
        $this->mount();
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $setting = MstSettingAntrian::first();
        if (!$setting) {
            $setting = new MstSettingAntrian();
        }

        $setting->mode_antrian = $this->mode_antrian;
        $setting->jam_buka = $this->jam_buka;
        $setting->jam_tutup = $this->jam_tutup;
        $setting->durasi_slot = $this->durasi_slot;
        $setting->max_antrian = $this->max_antrian;
        $setting->running_text = $this->running_text;
        $setting->is_active = $this->is_active;
        $setting->save();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pengaturan antrian berhasil disimpan!']);
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon"><i class="ri-settings-4-line"></i></div>
                    <h1>Setting Antrian</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
                    <span class="sep">/</span>
                    <a href="/antrian" wire:navigate>Antrian</a>
                    <span class="sep">/</span>
                    <span>Setting</span>
                </div>
            </div>

            <div class="max-w-4xl mx-auto mt-6">
                <form wire:submit.prevent="save">
                    <div class="card overflow-hidden border-t-4 border-[#405189] shadow-lg rounded-xl">
                        <div class="p-6 border-b border-[#eff2f7] bg-gray-50/50">
                            <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                                <i class="ri-equalizer-line text-[#405189]"></i> Konfigurasi Sistem Antrian
                            </h5>
                            <p class="text-sm text-[#878a99] mt-1">Atur parameter dan perilaku sistem antrian elektronik klinik.</p>
                        </div>
                        
                        <div class="p-6 space-y-8">
                            <!-- Section: General -->
                            <div>
                                <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-4 pb-2 border-b border-gray-100">Umum</h6>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mode Antrian</label>
                                        <div class="flex flex-col gap-3">
                                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-all {{ $mode_antrian === 'Nomor Urut' ? 'border-[#405189] bg-blue-50/30' : 'border-gray-200' }}">
                                                <input type="radio" wire:model="mode_antrian" value="Nomor Urut" class="w-4 h-4 text-[#405189] focus:ring-[#405189]">
                                                <div>
                                                    <p class="font-bold text-sm text-gray-800">Nomor Urut</p>
                                                    <p class="text-[11px] text-gray-500">Pasien dipanggil berdasarkan urutan kedatangan.</p>
                                                </div>
                                            </label>
                                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-all {{ $mode_antrian === 'Waktu Periksa' ? 'border-[#0ab39c] bg-green-50/30' : 'border-gray-200' }}">
                                                <input type="radio" wire:model="mode_antrian" value="Waktu Periksa" class="w-4 h-4 text-[#0ab39c] focus:ring-[#0ab39c]">
                                                <div>
                                                    <p class="font-bold text-sm text-gray-800">Waktu Periksa</p>
                                                    <p class="text-[11px] text-gray-500">Pasien datang sesuai dengan jam reservasi.</p>
                                                </div>
                                            </label>
                                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-all {{ $mode_antrian === 'Keduanya' ? 'border-[#a62ea2] bg-purple-50/30' : 'border-gray-200' }}">
                                                <input type="radio" wire:model="mode_antrian" value="Keduanya" class="w-4 h-4 text-[#a62ea2] focus:ring-[#a62ea2]">
                                                <div>
                                                    <p class="font-bold text-sm text-gray-800">Keduanya</p>
                                                    <p class="text-[11px] text-gray-500">Waktu periksa diatur berbanding lurus dengan nomor urut.</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status Sistem Kiosk</label>
                                            <div class="flex items-center justify-between p-4 border rounded-xl {{ $is_active ? 'border-green-200 bg-green-50/30' : 'border-red-200 bg-red-50/30' }}">
                                                <div>
                                                    <p class="font-bold text-sm text-gray-800">{{ $is_active ? 'Aktif' : 'Non-Aktif' }}</p>
                                                    <p class="text-[11px] text-gray-500">Mengontrol apakah pasien bisa mencetak tiket.</p>
                                                </div>
                                                <div class="form-check form-switch form-switch-lg form-switch-success" dir="ltr">
                                                    <input type="checkbox" wire:model="is_active" class="form-check-input" id="isActiveSwitch">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Teks Pengumuman Monitor (Marquee)</label>
                                            <textarea wire:model="running_text" class="w-full rounded-xl border-gray-200 text-sm p-4 h-24 focus:border-[#405189] transition-all resize-none shadow-sm" placeholder="Contoh: Selamat datang di SIGI Dental Clinic! Mohon menunggu antrian Anda dipanggil."></textarea>
                                            @error('running_text') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section: Waktu -->
                            <div>
                                <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-4 pb-2 border-b border-gray-100">Jam Operasional & Kuota</h6>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jam Buka Pendaftaran</label>
                                        <div class="relative">
                                            <input type="time" wire:model="jam_buka" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-mono font-bold text-[#405189]">
                                        </div>
                                        @error('jam_buka') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jam Tutup Pendaftaran</label>
                                        <div class="relative">
                                            <input type="time" wire:model="jam_tutup" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-mono font-bold text-orange-600">
                                        </div>
                                        @error('jam_tutup') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Durasi per Slot (Menit)</label>
                                        <div class="relative">
                                            <input type="number" wire:model="durasi_slot" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-bold pr-10">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">mnt</span>
                                        </div>
                                        @error('durasi_slot') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Maks. Antrian Harian</label>
                                        <div class="relative">
                                            <input type="number" wire:model="max_antrian" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-bold pr-12">
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">pasien</span>
                                        </div>
                                        @error('max_antrian') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-5 bg-gray-50 flex justify-end gap-3 border-t border-[#eff2f7]">
                            <button type="button" class="btn bg-gray-500 text-white font-bold text-sm px-6 hover:bg-gray-600 hover:-translate-y-0.5 transition-all" wire:click="resetSettings"><i class="ri-refresh-line mr-1"></i> Reset</button>
                            <button type="submit" class="btn bg-[#0d6efd] text-white font-bold text-sm px-8 shadow-md hover:bg-[#0b5ed7] hover:-translate-y-0.5 transition-all"><i class="ri-save-3-line mr-1"></i> Simpan Pengaturan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        HTML;
    }
}
