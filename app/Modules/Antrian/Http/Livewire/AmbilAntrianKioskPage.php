<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\TrxAntrian;
use App\Models\MstPoli;
use App\Models\MstSettingAntrianHari;
use App\Models\MstSettingAntrianLibur;
use App\Models\MstSettingAntrian;
use App\Models\MstSettingAntrianDetail;
use Carbon\Carbon;

#[Layout('components.layouts.blank')]
class AmbilAntrianKioskPage extends Component
{
    public $nama_pasien;
    public $kode_poli;
    public $kode_dokter;
    public $tanggal_antrian;
    public $time_slot;
    public $mode_antrian = 'Nomor Urut';
    public $format_nomor_antrian = '[nomor]';
    public $availableTimeSlots = [];

    public $poliList = [];
    public $dokterList = [];
    public $generatedAntrian = null;

    public $isHoliday = false;
    public $holidayMessage = '';

    public function mount()
    {
        $this->tanggal_antrian = now()->format('Y-m-d');
        $setting = MstSettingAntrian::first();
        if ($setting) {
            $this->mode_antrian = $setting->mode_antrian;
            $this->format_nomor_antrian = $setting->format_nomor_antrian ?? '[nomor]';
        }
        $this->checkHoliday();
        
        if (!$this->isHoliday) {
            $this->poliList = MstPoli::where('status', 'Aktif')->get();
            $this->loadAvailableSlots();
        }
    }

    public function loadAvailableSlots()
    {
        if ($this->mode_antrian === 'Nomor Urut') {
            $this->availableTimeSlots = [];
            return;
        }

        $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
        $hariNama = $hariMap[now()->format('l')];
        
        // Filter booked slots by Poli and Dokter
        $query = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)
            ->where('status', '!=', 'batal')
            ->whereNotNull('time_slot');
            
        if ($this->kode_poli) {
            $query->where('kode_poli', $this->kode_poli);
        }
        if ($this->kode_dokter) {
            $query->where('kode_dokter', $this->kode_dokter);
        }

        $bookedSlotsShort = $query->pluck('time_slot')
            ->map(function($t) { return substr($t, 0, 5); })
            ->toArray();

        $this->availableTimeSlots = MstSettingAntrianDetail::where('hari', $hariNama)
            ->orderBy('waktu')
            ->get()
            ->filter(function($slot) use ($bookedSlotsShort) {
                return !in_array(substr($slot->waktu, 0, 5), $bookedSlotsShort);
            })
            ->map(function($slot) {
                return [
                    'value' => substr($slot->waktu, 0, 5) . ':00',
                    'label' => substr($slot->waktu, 0, 5),
                    'nomor_urut' => $slot->nomor_urut
                ];
            })->values()->toArray();
    }

    private function checkHoliday()
    {
        $now = now();
        $hariMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        $hariIni = $hariMap[$now->format('l')];
        
        // 1. Check Global Status
        $global = MstSettingAntrian::first();
        if ($global && !$global->is_active) {
            $this->isHoliday = true;
            $this->holidayMessage = 'Sistem Kiosk saat ini sedang dinonaktifkan oleh Admin.';
            return;
        }

        // 2. Check Specific Date Holiday
        $liburKhusus = MstSettingAntrianLibur::where('tanggal_mulai', '<=', $now->format('Y-m-d'))
            ->where('tanggal_selesai', '>=', $now->format('Y-m-d'))
            ->first();
            
        if ($liburKhusus) {
            $this->isHoliday = true;
            $this->holidayMessage = $liburKhusus->keterangan ?: 'Klinik Sedang Libur';
            return;
        }

        // 3. Check Weekly Holiday
        $settingHari = MstSettingAntrianHari::where('hari', $hariIni)->first();
        if ($settingHari && $settingHari->is_holiday) {
            $this->isHoliday = true;
            $this->holidayMessage = "Maaf, hari $hariIni klinik tidak beroperasi (Libur Mingguan).";
            return;
        }
    }

    public function setPoli($kode)
    {
        $this->kode_poli = $kode;
        $this->kode_dokter = null;
        $this->time_slot = null;
        $this->availableTimeSlots = [];
        
        $poli = MstPoli::with('dokters')->where('kode_poli', $kode)->first();
        if ($poli) {
            $this->dokterList = $poli->dokters->where('status', 'Aktif')->toArray();
        } else {
            $this->dokterList = [];
        }
    }

    public function setDokter($kode)
    {
        $this->kode_dokter = $kode;
        $this->time_slot = null;
        $this->loadAvailableSlots();
    }

    public function setTimeSlot($waktu)
    {
        $this->time_slot = $waktu;
    }

    public function simpan()
    {
        $this->checkHoliday();
        if ($this->isHoliday) {
            $this->addError('general', 'Pendaftaran gagal: Klinik sedang libur.');
            return;
        }

        $this->validate([
            'nama_pasien' => 'required|string|max:100',
            'kode_poli' => 'required',
            'kode_dokter' => 'required',
        ], [
            'nama_pasien.required' => 'Silakan isi Nama Anda.',
            'kode_poli.required' => 'Silakan pilih Poli Tujuan.',
            'kode_dokter.required' => 'Silakan pilih Dokter.',
        ]);

        try {
            $duplicateCheck = TrxAntrian::query()
                ->where(fn($q) => $q->where('tanggal_antrian', $this->tanggal_antrian))
                ->where(fn($q) => $q->where('nama_pasien_input_manual', $this->nama_pasien))
                ->where(fn($q) => $q->where('kode_poli', $this->kode_poli))
                ->where(fn($q) => $q->where('kode_dokter', $this->kode_dokter))
                ->where(fn($q) => $q->where('status', '!=', 'batal'))
                ->exists();
                
            if ($duplicateCheck) {
                $this->addError('general', 'Anda sudah mengambil antrian untuk poli ini pada hari ini.');
                return;
            }

            if ($this->mode_antrian !== 'Nomor Urut') {
                if (!$this->time_slot) {
                    $this->addError('time_slot', 'Silakan pilih Slot Waktu terlebih dahulu.');
                    return;
                }
                
                $isBooked = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)
                    ->where('time_slot', 'like', substr($this->time_slot, 0, 5) . '%')
                    ->where('kode_poli', $this->kode_poli)
                    ->where('kode_dokter', $this->kode_dokter)
                    ->where('status', '!=', 'batal')
                    ->exists();
                    
                if ($isBooked) {
                    $this->loadAvailableSlots();
                    $this->time_slot = null;
                    $this->addError('time_slot', 'Maaf, slot waktu ini baru saja diambil orang lain.');
                    return;
                }

                $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
                $hariNama = $hariMap[now()->format('l')];
                $slotDetail = MstSettingAntrianDetail::where('hari', $hariNama)->where('waktu', 'like', substr($this->time_slot, 0, 5).'%')->first();
                if (!$slotDetail) {
                    $this->addError('time_slot', 'Slot waktu tidak valid.');
                    return;
                }
                
                $nomorAntrian = $slotDetail->nomor_urut;
            } else {
                $countToday = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)->count();
                $nextSequence = $countToday + 1;
                
                $base = 0;
                $len = 3;
                $prefix = '';
                if (preg_match('/(.*?)([0-9]+)$/', $this->format_nomor_antrian, $matches)) {
                    $prefix = $matches[1];
                    $suffixTpl = $matches[2];
                    $len = strlen($suffixTpl);
                    $base = intval($suffixTpl);
                }
                
                $nomorString = str_pad($base + $nextSequence, $len, '0', STR_PAD_LEFT);
                $nomorAntrian = $prefix . $nomorString;
            }

            $antrian = TrxAntrian::create([
                'nomor_antrian' => $nomorAntrian,
                'tanggal_antrian' => $this->tanggal_antrian,
                'jenis_antrian' => 'offline',
                'nama_pasien_input_manual' => $this->nama_pasien,
                'kode_poli' => $this->kode_poli,
                'kode_dokter' => $this->kode_dokter,
                'time_slot' => $this->time_slot,
                'status' => 'menunggu',
            ]);

            $this->generatedAntrian = $antrian;

        } catch (\Exception $e) {
            $this->addError('general', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    public function ambilLagi()
    {
        $this->reset(['nama_pasien', 'kode_poli', 'kode_dokter', 'dokterList', 'generatedAntrian', 'time_slot']);
        $this->tanggal_antrian = now()->format('Y-m-d');
        $this->checkHoliday();
        if (!$this->isHoliday) {
            $this->loadAvailableSlots();
        }
    }

    public function render()
    {
        return view('livewire.modules.antrian.ambil-antrian-kiosk-page');
        HTML;
    }

    public function layout()
    {
        return 'components.layouts.blank';
    }
}
