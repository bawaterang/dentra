<?php

namespace App\Modules\Laporan\Http\Livewire;

use App\Models\TrxMessage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class LaporanKritikSaranPage extends Component
{
    use WithPagination;

    public $periodType = 'DAILY'; // DAILY, MONTHLY, YEARLY

    public $selectedDate;
    public $selectedBulan;
    public $selectedTahun;
    public $search = '';

    public $availableYears = [];
    public $listBulan = [];

    public $listPeriodType = [
        ['value' => 'DAILY', 'label' => 'HARIAN', 'icon' => 'ri-calendar-event-line text-blue-500'],
        ['value' => 'MONTHLY', 'label' => 'BULANAN', 'icon' => 'ri-calendar-2-line text-indigo-500'],
        ['value' => 'YEARLY', 'label' => 'TAHUNAN', 'icon' => 'ri-calendar-todo-line text-purple-500'],
    ];

    public $showJawabModal = false;
    public $formJawab = [
        'id' => null,
        'nama' => '',
        'pesan' => '',
        'jawaban' => '',
    ];

    protected $queryString = ['periodType', 'selectedDate', 'selectedBulan', 'selectedTahun', 'search'];

    public function mount()
    {
        $this->selectedDate = date('Y-m-d');
        $this->selectedBulan = (int) date('n');
        $this->selectedTahun = (int) date('Y');
        $this->loadAvailableYears();
        $this->loadListBulan();
    }

    public function loadListBulan()
    {
        $this->listBulan = [
            ['value' => 1, 'label' => 'Januari', 'icon' => 'ri-calendar-line text-blue-500'],
            ['value' => 2, 'label' => 'Februari', 'icon' => 'ri-calendar-line text-indigo-500'],
            ['value' => 3, 'label' => 'Maret', 'icon' => 'ri-calendar-line text-purple-500'],
            ['value' => 4, 'label' => 'April', 'icon' => 'ri-calendar-line text-pink-500'],
            ['value' => 5, 'label' => 'Mei', 'icon' => 'ri-calendar-line text-cyan-500'],
            ['value' => 6, 'label' => 'Juni', 'icon' => 'ri-calendar-line text-teal-500'],
            ['value' => 7, 'label' => 'Juli', 'icon' => 'ri-calendar-line text-green-500'],
            ['value' => 8, 'label' => 'Agustus', 'icon' => 'ri-calendar-line text-lime-500'],
            ['value' => 9, 'label' => 'September', 'icon' => 'ri-calendar-line text-yellow-500'],
            ['value' => 10, 'label' => 'Oktober', 'icon' => 'ri-calendar-line text-orange-500'],
            ['value' => 11, 'label' => 'November', 'icon' => 'ri-calendar-line text-red-500'],
            ['value' => 12, 'label' => 'Desember', 'icon' => 'ri-calendar-line text-rose-500'],
        ];
    }

    public function loadAvailableYears()
    {
        $years = TrxMessage::selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $this->availableYears = $years;
    }

    #[Computed]
    public function laporanKritikSaran()
    {
        $query = TrxMessage::query()->whereNotNull('created_at');

        if ($this->periodType === 'DAILY') {
            $query->whereDate('created_at', $this->selectedDate);
        } elseif ($this->periodType === 'MONTHLY') {
            $query->whereMonth('created_at', $this->selectedBulan)
                ->whereYear('created_at', $this->selectedTahun);
        } elseif ($this->periodType === 'YEARLY') {
            $query->whereYear('created_at', $this->selectedTahun);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('nomor_hp', 'like', '%' . $this->search . '%')
                  ->orWhere('pesan', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function openJawabModal($id)
    {
        $message = TrxMessage::find($id);
        if ($message) {
            $this->formJawab = [
                'id' => $message->id,
                'nama' => $message->nama ?? 'Anonim',
                'pesan' => $message->pesan,
                'jawaban' => $message->jawaban ?? '',
            ];
            $this->showJawabModal = true;
        }
    }

    public function closeJawabModal()
    {
        $this->showJawabModal = false;
        $this->reset('formJawab');
    }

    public function simpanJawaban()
    {
        $this->validate([
            'formJawab.jawaban' => 'required',
        ], [
            'formJawab.jawaban.required' => 'Jawaban wajib diisi.',
        ]);

        $message = TrxMessage::find($this->formJawab['id']);
        if ($message) {
            $message->jawaban = $this->formJawab['jawaban'];
            $message->waktu_jawab = Carbon::now();
            $message->save();

            $this->closeJawabModal();
            $this->dispatch('refresh-component');
        }
    }

    public function deleteMessage($id)
    {
        $message = TrxMessage::find($id);
        if ($message) {
            $message->delete();
        }
    }

    public function updatedPeriodType()
    {
        $this->resetPage();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
    }

    public function updatedSelectedBulan()
    {
        $this->resetPage();
    }

    public function updatedSelectedTahun()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.modules.laporan.laporan-kritik-saran-page');
    }
}
