<?php

namespace App\Modules\Laporan\Http\Livewire;

use App\Models\TrxBilling;
use App\Models\TrxPendaftaran;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\HasAccessControl;

class LaporanPendapatanPage extends Component
{
    use WithPagination, HasAccessControl;

    public $periodType = 'DAILY'; // DAILY, MONTHLY, YEARLY

    public $selectedDate;
    public $selectedBulan;
    public $selectedTahun;

    public $search = '';

    public $expandedRows = [];

    public $availableYears = [];
    public $listBulan = [];

    public $listPeriodType = [
        ['value' => 'DAILY', 'label' => 'HARIAN', 'icon' => 'ri-calendar-event-line text-blue-500'],
        ['value' => 'MONTHLY', 'label' => 'BULANAN', 'icon' => 'ri-calendar-2-line text-indigo-500'],
        ['value' => 'YEARLY', 'label' => 'TAHUNAN', 'icon' => 'ri-calendar-todo-line text-purple-500'],
    ];

    protected $queryString = ['periodType', 'selectedDate', 'selectedBulan', 'selectedTahun', 'search'];

    public function mount()
    {
        $this->authorizeAccess('/laporan/pendapatan');

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
        $years = TrxBilling::selectRaw('YEAR(created_at) as year')
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

    private function getBaseQuery()
    {
        $query = TrxBilling::with(['pasien', 'details'])
            ->whereNotNull('created_at');

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
                $q->where('no_faktur', 'like', '%' . $this->search . '%')
                  ->orWhere('nomor_kunjungan', 'like', '%' . $this->search . '%')
                  ->orWhereHas('pasien', function ($pq) {
                      $pq->where('nama_pasien', 'like', '%' . $this->search . '%')
                         ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query;
    }

    #[Computed]
    public function summaryData()
    {
        $query = $this->getBaseQuery();
        
        $pendapatan = (clone $query)->sum('total_bayar');
        $piutang = (clone $query)->sum('hutang');

        $nomorKunjungans = (clone $query)->pluck('nomor_kunjungan')->toArray();
        if (count($nomorKunjungans) > 0) {
            $pengeluaranBhp = DB::table('trx_tindakan')
                ->whereIn('nomor_kunjungan', $nomorKunjungans)
                ->whereNull('deleted_at')
                ->sum('bhp');
        } else {
            $pengeluaranBhp = 0;
        }

        return [
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaranBhp,
            'piutang' => $piutang,
            'laba_bersih' => $pendapatan - $pengeluaranBhp
        ];
    }

    #[Computed]
    public function laporanPendapatan()
    {
        return $this->getBaseQuery()->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getPengeluaranBhp($nomorKunjungan)
    {
        return DB::table('trx_tindakan')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->whereNull('deleted_at')
            ->sum('bhp');
    }

    public function getDetailBhp($nomorKunjungan)
    {
        return DB::table('trx_tindakan')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->whereNull('deleted_at')
            ->where('bhp', '>', 0)
            ->get();
    }

    public function toggleDetail($id)
    {
        if (isset($this->expandedRows[$id])) {
            unset($this->expandedRows[$id]);
        } else {
            $this->expandedRows[$id] = true;
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
        return view('livewire.modules.laporan.laporan-pendapatan-page');
    }
}
