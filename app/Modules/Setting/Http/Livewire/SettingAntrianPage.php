<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;

class SettingAntrianPage extends Component
{
    public function render()
    {
        return <<<'HTML'
        <div>
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-settings-3-line"></i></div><h1>Setting Antrian</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a><span class="sep">/</span><a href="#">Setting</a><span class="sep">/</span><span>Antrian</span></div></div>

            <div class="card shadow-sm border-t-2 border-[#405189] p-8 text-center">
                <i class="ri-settings-4-line text-6xl text-gray-300 mb-4 block"></i>
                <h2 class="text-xl font-bold text-[#495057] mb-2">Konfigurasi Antrian</h2>
                <p class="text-[#878a99]">Halaman ini sedang dalam pengembangan (Coming Soon).</p>
            </div>
        </div>
        HTML;
    }
}
