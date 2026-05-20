<?php

namespace App\Modules\Reservasi\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use App\Models\TrxReservasi;
use App\Models\MstPasien;
use App\Models\MstPoli;
use App\Models\MstDokter;
use App\Models\MstSettingAntrianDetail;

class ReservasiPage extends Component
{
    use WithPagination;

    // Calendar State
    public $currentMonth;
    public $currentYear;
    public $selectedDate;
    public $calendarView = 'month'; // 'month', 'week', 'day'
    
    // Modal & Form State
    public $showModal = false;
    public $modePasien = 'lama'; // 'lama' atau 'baru'
    public $searchPasien = '';
    public $pasienResults = [];
    public $selectedPasienId;
    public $selectedPasienData = null;
    
    // Form Inputs
    public $poli_id;
    public $dokter_id;
    public $time_slot;
    public $keterangan;
    
    // Pasien Baru Inputs
    public $nama_pasien;
    public $no_telepon;
    public $nik;
    
    // Available options
    public $availableTimeSlots = [];
    
    // Search for the list
    public $search = '';

    public function mount()
    {
        $now = Carbon::now();
        $this->currentMonth = (int) $now->format('m');
        $this->currentYear = (int) $now->format('Y');
        $this->selectedDate = $now->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function goToToday()
    {
        $now = Carbon::now();
        $this->currentMonth = (int) $now->format('m');
        $this->currentYear = (int) $now->format('Y');
        $this->selectedDate = $now->format('Y-m-d');
        $this->resetPage();
    }

    public function setView($view)
    {
        $this->calendarView = $view;
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $this->resetPage();
    }

    public function prevMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = (int) $date->format('m');
        $this->currentYear = (int) $date->format('Y');
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = (int) $date->format('m');
        $this->currentYear = (int) $date->format('Y');
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openModalForDate($date)
    {
        $this->selectDate($date);
        $this->openModal();
    }

    public function resetForm()
    {
        $this->modePasien = 'lama';
        $this->searchPasien = '';
        $this->pasienResults = [];
        $this->selectedPasienId = null;
        $this->selectedPasienData = null;
        $this->poli_id = null;
        $this->dokter_id = null;
        $this->time_slot = null;
        $this->keterangan = '';
        $this->nama_pasien = '';
        $this->no_telepon = '';
        $this->nik = '';
        $this->availableTimeSlots = [];
        
        $this->resetValidation();
    }

    public function updatedSearchPasien()
    {
        if (strlen($this->searchPasien) >= 2) {
            $this->pasienResults = MstPasien::where('status', 'Aktif')
                ->where(function ($q) {
                    $q->where('nama_pasien', 'like', '%' . $this->searchPasien . '%')
                        ->orWhere('nik', 'like', '%' . $this->searchPasien . '%')
                        ->orWhere('no_rm', 'like', '%' . $this->searchPasien . '%')
                        ->orWhere('no_telepon', 'like', '%' . $this->searchPasien . '%');
                })
                ->limit(10)
                ->get()
                ->toArray();
        } else {
            $this->pasienResults = [];
        }
    }

    public function pilihPasien($id)
    {
        $pasien = MstPasien::find($id);
        if ($pasien) {
            $this->selectedPasienId = $pasien->id;
            $this->selectedPasienData = $pasien->toArray();
            $this->searchPasien = '';
            $this->pasienResults = [];
        }
    }

    public function resetSelectedPasien()
    {
        $this->selectedPasienId = null;
        $this->selectedPasienData = null;
        $this->searchPasien = '';
    }

    public function updatedPoliId()
    {
        $this->dokter_id = null;
        $this->time_slot = null;
        $this->availableTimeSlots = [];
        $this->loadAvailableSlots();
    }

    public function updatedDokterId()
    {
        $this->time_slot = null;
        $this->loadAvailableSlots();
    }

    public function loadAvailableSlots()
    {
        if (!$this->poli_id || !$this->dokter_id || !$this->selectedDate) {
            $this->availableTimeSlots = [];
            return;
        }

        $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
        $hariNama = $hariMap[Carbon::parse($this->selectedDate)->format('l')];

        // Ambil slot reservasi yang sudah di-booking
        $bookedSlots = TrxReservasi::whereDate('tanggal_reservasi', $this->selectedDate)
            ->where('poli_id', $this->poli_id)
            ->where('dokter_id', $this->dokter_id)
            ->whereIn('status', ['aktif', 'hadir'])
            ->pluck('time_slot')
            ->map(function ($t) {
                return substr($t, 0, 5);
            })
            ->toArray();

        $this->availableTimeSlots = MstSettingAntrianDetail::where('hari', $hariNama)
            ->orderBy('waktu')
            ->get()
            ->filter(function ($slot) use ($bookedSlots) {
                return !in_array(substr($slot->waktu, 0, 5), $bookedSlots);
            })
            ->map(function ($slot) {
                return [
                    'value' => substr($slot->waktu, 0, 5) . ':00',
                    'label' => substr($slot->waktu, 0, 5) . ' (' . $slot->nomor_urut . ')'
                ];
            })->values()->toArray();
    }

    public function saveReservasi()
    {
        $rules = [
            'poli_id' => 'required',
            'dokter_id' => 'required',
            'time_slot' => 'required',
        ];

        if ($this->modePasien === 'lama') {
            $rules['selectedPasienId'] = 'required';
        } else {
            $rules['nama_pasien'] = 'required|string|max:100';
            $rules['no_telepon'] = 'required|string|max:20';
        }

        $messages = [
            'poli_id.required' => 'Poli tujuan wajib dipilih',
            'dokter_id.required' => 'Dokter tujuan wajib dipilih',
            'time_slot.required' => 'Slot waktu wajib dipilih',
            'selectedPasienId.required' => 'Pasien lama wajib dipilih',
            'nama_pasien.required' => 'Nama pasien wajib diisi',
            'no_telepon.required' => 'No Telepon wajib diisi',
        ];

        $this->validate($rules, $messages);

        TrxReservasi::create([
            'kode_reservasi' => TrxReservasi::generateKodeReservasi(),
            'tanggal_reservasi' => $this->selectedDate,
            'time_slot' => $this->time_slot,
            'pasien_id' => $this->modePasien === 'lama' ? $this->selectedPasienId : null,
            'nama_pasien_manual' => $this->modePasien === 'baru' ? $this->nama_pasien : null,
            'no_telepon_manual' => $this->modePasien === 'baru' ? $this->no_telepon : null,
            'nik_manual' => $this->modePasien === 'baru' ? $this->nik : null,
            'poli_id' => $this->poli_id,
            'dokter_id' => $this->dokter_id,
            'keterangan' => $this->keterangan,
            'status' => 'aktif',
            'created_by' => auth()->id(),
        ]);

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Reservasi berhasil disimpan!']);
        $this->dispatch('refresh-calendar');
    }

    public function batalReservasi($id)
    {
        $reservasi = TrxReservasi::find($id);
        if ($reservasi && $reservasi->status === 'aktif') {
            $reservasi->update(['status' => 'batal']);
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Reservasi dibatalkan.']);
        }
    }

    public function prosesKeAntrian($id)
    {
        $reservasi = TrxReservasi::find($id);
        if ($reservasi && $reservasi->status === 'aktif') {
            // Kita bisa arahkan langsung ke pendaftaran dengan parameter
            // Parameter antrian bisa dibuat saat submit form di PendaftaranPage,
            // untuk sekarang kita kirim ke route pendaftaran create
            
            return redirect()->route('antrian.ambil', [
                'reservasi_id' => $reservasi->id,
            ]);
        }
    }

    #[Computed]
    public function calendarDays()
    {
        $days = [];
        $selectedCarbon = Carbon::parse($this->selectedDate);
        
        if ($this->calendarView === 'month') {
            $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $startDay = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
            $endDay = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);
        } elseif ($this->calendarView === 'week') {
            $startDay = $selectedCarbon->copy()->startOfWeek(Carbon::SUNDAY);
            $endDay = $selectedCarbon->copy()->endOfWeek(Carbon::SATURDAY);
        } else {
            $startDay = $selectedCarbon->copy();
            $endDay = $selectedCarbon->copy();
        }
        
        $current = $startDay->copy();
        
        // Dapatkan jumlah reservasi per hari
        $reservasiCounts = TrxReservasi::selectRaw('DATE(tanggal_reservasi) as date, count(*) as total')
            ->whereBetween('tanggal_reservasi', [$startDay->format('Y-m-d'), $endDay->format('Y-m-d')])
            ->whereIn('status', ['aktif', 'hadir'])
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();
            
        while ($current <= $endDay) {
            $dateStr = $current->format('Y-m-d');
            $days[] = [
                'date' => $dateStr,
                'day' => $current->format('d'),
                'isCurrentMonth' => $current->month === $this->currentMonth,
                'isToday' => $current->isToday(),
                'isSelected' => $dateStr === $this->selectedDate,
                'reservasiCount' => $reservasiCounts[$dateStr] ?? 0,
            ];
            $current->addDay();
        }
        
        return $days;
    }

    #[Computed]
    public function reservasiList()
    {
        $query = TrxReservasi::with(['pasien', 'poli', 'dokter'])
            ->whereDate('tanggal_reservasi', $this->selectedDate)
            ->whereIn('status', ['aktif', 'hadir']);
            
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('kode_reservasi', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_pasien_manual', 'like', '%' . $this->search . '%')
                  ->orWhereHas('pasien', function($pq) {
                      $pq->where('nama_pasien', 'like', '%' . $this->search . '%')
                         ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        return $query->orderBy('time_slot')->paginate(10);
    }

    public function render()
    {
        if ($this->calendarView === 'week') {
            $startWeek = Carbon::parse($this->selectedDate)->startOfWeek(Carbon::SUNDAY)->translatedFormat('d M');
            $endWeek = Carbon::parse($this->selectedDate)->endOfWeek(Carbon::SATURDAY)->translatedFormat('d M Y');
            $monthName = "$startWeek - $endWeek";
        } elseif ($this->calendarView === 'day') {
            $monthName = Carbon::parse($this->selectedDate)->translatedFormat('d F Y');
        } else {
            $monthName = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->translatedFormat('F Y');
        }
        
        $poliList = MstPoli::where('status', 'Aktif')->get()->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli])->toArray();
        
        $docQuery = MstDokter::where('status', 'Aktif');
        if ($this->poli_id) {
            $poli = MstPoli::with('dokters')->find($this->poli_id);
            if ($poli) {
                $mappedIds = $poli->dokters->pluck('id')->toArray();
                $docQuery->whereIn('id', $mappedIds);
            }
        }
        $dokterList = $docQuery->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->nama_dokter])->toArray();

        return view('livewire.reservasi-page', [
            'monthName' => $monthName,
            'poliList' => $poliList,
            'dokterList' => $dokterList,
        ]);
    }
}
