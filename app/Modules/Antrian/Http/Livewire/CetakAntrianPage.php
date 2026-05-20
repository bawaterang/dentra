<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\TrxAntrian;

#[Layout('components.layouts.blank')]
class CetakAntrianPage extends Component
{
    public $generatedAntrian;

    public function mount($id)
    {
        $this->generatedAntrian = TrxAntrian::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.modules.antrian.cetak-antrian-page');
    }
}
