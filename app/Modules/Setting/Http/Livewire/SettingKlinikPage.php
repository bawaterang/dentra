<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use App\Models\MstInstansi;

class SettingKlinikPage extends Component
{
    public $nama_klinik;
    public $alamat;
    public $telepon;
    public $email;
    public $website;
    public $pimpinan;

    public function mount()
    {
        $instansi = MstInstansi::first();
        if ($instansi) {
            $this->nama_klinik = $instansi->nama_instansi;
            $this->alamat = $instansi->alamat;
            $this->telepon = $instansi->telepon;
            $this->email = $instansi->email;
            $this->website = $instansi->website;
            $this->pimpinan = $instansi->pimpinan;
        } else {
            // Defaults
            $this->nama_klinik = 'SIGI Dental Clinic';
            $this->email = 'info@sigidental.id';
        }
    }

    public function rules()
    {
        return [
            'nama_klinik' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'website' => 'nullable|string|max:255',
            'pimpinan' => 'nullable|string|max:255',
        ];
    }

    public function save()
    {
        $this->validate();
        
        $instansi = MstInstansi::first() ?? new MstInstansi();
        $instansi->nama_instansi = $this->nama_klinik;
        $instansi->alamat = $this->alamat;
        $instansi->telepon = $this->telepon;
        $instansi->email = $this->email;
        $instansi->website = $this->website;
        $instansi->pimpinan = $this->pimpinan;
        $instansi->save();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Informasi klinik berhasil diperbarui!']);
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon"><i class="ri-hospital-line"></i></div>
                    <h1>Informasi Klinik</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
                    <span class="sep">/</span>
                    <span>Pengaturan</span>
                    <span class="sep">/</span>
                    <span>Klinik</span>
                </div>
            </div>

            <div class="max-w-4xl mx-auto mt-6">
                <form wire:submit.prevent="save">
                    <div class="card overflow-hidden border-t-4 border-[#405189] shadow-lg rounded-xl">
                        <div class="p-6 border-b border-[#eff2f7] bg-gray-50/50">
                            <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                                <i class="ri-hospital-fill text-[#405189]"></i> Profil Instansi
                            </h5>
                            <p class="text-sm text-[#878a99] mt-1">Kelola identitas dan informasi kontak rekam medis klinik Anda.</p>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Klinik</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-hospital-line text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="nama_klinik" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all font-semibold">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Pimpinan / Penanggung Jawab</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-user-star-line text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="pimpinan" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Telepon Utama</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-phone-fill text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="telepon" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Resmi</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-mail-fill text-gray-400"></i>
                                            </div>
                                            <input type="email" wire:model="email" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Website URL</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-global-line text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="website" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4 flex flex-col justify-between">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap</label>
                                        <textarea wire:model="alamat" class="w-full rounded-lg border-gray-200 text-sm p-4 h-32 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all resize-none shadow-sm"></textarea>
                                    </div>
                                    <div class="mt-4 p-4 border border-blue-100 bg-blue-50/50 rounded-xl">
                                        <div class="flex items-start gap-3">
                                            <i class="ri-information-fill text-[#405189] mt-0.5"></i>
                                            <div>
                                                <h6 class="text-sm font-bold text-[#405189]">Info Update</h6>
                                                <p class="text-xs text-[#878a99] mt-1">Informasi ini akan ditampilkan pada kop surat, struk apotek, dan tiket antrian.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-5 bg-gray-50 flex justify-end gap-3 border-t border-[#eff2f7]">
                            <button type="button" wire:click="mount" class="btn btn-soft-secondary font-bold text-sm px-6 hover:-translate-y-0.5 transition-transform">Reset</button>
                            <button type="submit" class="btn btn-primary font-bold text-sm px-8 shadow-md hover:-translate-y-0.5 transition-transform"><i class="ri-check-double-line mr-1"></i> Simpan Pendaftaran Klinik</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        HTML;
    }
}
