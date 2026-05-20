<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use App\Models\MstSettingAntrian;
use App\Models\MstSettingAntrianDetail;
use App\Models\MstSettingAntrianHari;
use App\Models\MstSettingAntrianLibur;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SettingAntrianPage extends Component
{
    // Global Settings
    public $mode_antrian;
    public $format_nomor_antrian;
    public $running_text;
    public $is_active;

    // Per-Day Settings (Inputs)
    public $jam_buka;
    public $jam_tutup;
    public $durasi_slot;
    public $max_antrian;
    public $is_holiday = false;

    // Holiday Range Management
    public $libur_mulai;
    public $libur_selesai;
    public $libur_keterangan;
    public $listLibur = [];

    // State Props
    public $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    public $selectedDay = 'Senin';
    public $timeSlots = [];

    public function mount()
    {
        // 1. Load Global Settings
        $globalSetting = MstSettingAntrian::first();
        if ($globalSetting) {
            $this->mode_antrian = $globalSetting->mode_antrian;
            $this->format_nomor_antrian = $globalSetting->format_nomor_antrian ?? '[nomor]';
            $this->running_text = $globalSetting->running_text;
            $this->is_active = $globalSetting->is_active;
        } else {
            $this->mode_antrian = 'Nomor Urut';
            $this->format_nomor_antrian = '[nomor]';
            $this->is_active = true;
        }

        // 2. Ensure 7 Days Configuration Exists
        $this->initDayConfigs();

        // 3. Load Selected Day Config
        $this->loadDayConfig();

        // 4. Load Right Side Slots
        $this->loadTimeSlots();

        // 5. Load Holiday Ranges
        $this->loadHolidayRanges();
    }

    private function initDayConfigs()
    {
        $existing = MstSettingAntrianHari::count();
        if ($existing < 7) {
            foreach ($this->days as $day) {
                MstSettingAntrianHari::firstOrCreate(
                    ['hari' => $day],
                    [
                        'jam_buka' => '08:00',
                        'jam_tutup' => '17:00',
                        'durasi_slot' => 15,
                        'max_antrian' => 50,
                        'is_holiday' => false
                    ]
                );
            }
        }
    }

    private function loadDayConfig()
    {
        $config = MstSettingAntrianHari::where('hari', $this->selectedDay)->first();
        if ($config) {
            $this->jam_buka = substr($config->jam_buka, 0, 5);
            $this->jam_tutup = substr($config->jam_tutup, 0, 5);
            $this->durasi_slot = $config->durasi_slot;
            $this->max_antrian = $config->max_antrian;
            $this->is_holiday = (bool) $config->is_holiday;
        }
    }

    public function loadHolidayRanges()
    {
        $this->listLibur = MstSettingAntrianLibur::orderBy('tanggal_mulai', 'desc')->get();
    }

    public function rules()
    {
        return [
            'mode_antrian' => 'required|in:Nomor Urut,Waktu Periksa,Keduanya',
            'format_nomor_antrian' => ['required', 'string', 'max:50', 'regex:/[0-9]$/'],
            'jam_buka' => 'required',
            'jam_tutup' => 'required',
            'durasi_slot' => 'required|integer|min:5|max:120',
            'max_antrian' => 'required|integer|min:1|max:500',
            'running_text' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_holiday' => 'boolean',
        ];
    }

    public function loadTimeSlots()
    {
        $this->timeSlots = MstSettingAntrianDetail::where('hari', $this->selectedDay)
            ->orderBy('waktu')
            ->get();
    }

    public function updatedSelectedDay()
    {
        $this->loadDayConfig();
        $this->loadTimeSlots();
    }

    public function toggleHoliday()
    {
        $this->is_holiday = !$this->is_holiday;
        MstSettingAntrianHari::where('hari', $this->selectedDay)->update([
            'is_holiday' => $this->is_holiday
        ]);

        if ($this->is_holiday) {
            MstSettingAntrianDetail::where('hari', $this->selectedDay)->delete();
            $this->loadTimeSlots();
            $this->dispatch('alert', ['type' => 'info', 'message' => "Hari $this->selectedDay sekarang diatur sebagai Libur."]);
        } else {
            $this->dispatch('alert', ['type' => 'success', 'message' => "Hari $this->selectedDay diatur sebagai Hari Kerja."]);
        }
    }

    public function addLiburRange()
    {
        $this->validate([
            'libur_mulai' => 'required|date',
            'libur_selesai' => 'required|date|after_or_equal:libur_mulai',
            'libur_keterangan' => 'required|string|max:100',
        ]);

        MstSettingAntrianLibur::create([
            'tanggal_mulai' => $this->libur_mulai,
            'tanggal_selesai' => $this->libur_selesai,
            'keterangan' => $this->libur_keterangan,
        ]);

        $this->reset(['libur_mulai', 'libur_selesai', 'libur_keterangan']);
        $this->loadHolidayRanges();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Hari libur khusus berhasil ditambahkan!']);
    }

    public function deleteLiburRange($id)
    {
        MstSettingAntrianLibur::destroy($id);
        $this->loadHolidayRanges();
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Hari libur khusus berhasil dihapus.']);
    }

    public function applyToAllDays()
    {
        $this->validate([
            'jam_buka' => 'required',
            'jam_tutup' => 'required',
            'durasi_slot' => 'required|integer',
            'max_antrian' => 'required|integer',
        ]);

        MstSettingAntrianHari::query()->update([
            'jam_buka' => $this->jam_buka,
            'jam_tutup' => $this->jam_tutup,
            'durasi_slot' => $this->durasi_slot,
            'max_antrian' => $this->max_antrian,
        ]);

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pengaturan jam operasional berhasil disalin ke semua hari!']);
    }

    public function generateTimeSlots()
    {
        if ($this->mode_antrian === 'Nomor Urut') {
            $this->dispatch('alert', ['type' => 'warning', 'message' => 'Generate waktu hanya berlaku untuk mode Waktu Periksa atau Keduanya!']);
            return;
        }

        try {
            DB::beginTransaction();

            MstSettingAntrianDetail::query()->delete();

            $configs = MstSettingAntrianHari::where('is_holiday', false)->get();
            $data = [];
            $now = Carbon::now();
            $user = Auth::user()->username ?? 'System';

            foreach ($configs as $config) {
                if (empty($config->jam_buka) || empty($config->jam_tutup)) continue;

                $startTime = Carbon::createFromFormat('H:i', substr($config->jam_buka, 0, 5));
                $endTime = Carbon::createFromFormat('H:i', substr($config->jam_tutup, 0, 5));
                
                $currentTime = $startTime->copy();
                $count = 0;
                
                // Prepare formatting logic
                $prefix = '';
                $suffixTpl = '000';
                $base = 0;
                if (preg_match('/(.*?)([0-9]+)$/', $this->format_nomor_antrian, $matches)) {
                    $prefix = $matches[1];
                    $suffixTpl = $matches[2];
                    $len = strlen($suffixTpl);
                    $base = intval($suffixTpl);
                } else {
                    $len = 3;
                }
                
                while ($currentTime->lt($endTime) && $count < $config->max_antrian) {
                    $sequence = $count + 1;
                    $formattedNo = $prefix . str_pad($base + $sequence, $len, '0', STR_PAD_LEFT);
                    
                    $data[] = [
                        'hari' => $config->hari,
                        'waktu' => $currentTime->format('H:i:s'),
                        'nomor_urut' => $formattedNo,
                        'created_by' => $user,
                        'created_at' => $now,
                    ];
                    
                    $currentTime->addMinutes($config->durasi_slot);
                    $count++;
                }
            }

            foreach (array_chunk($data, 100) as $chunk) {
                MstSettingAntrianDetail::insert($chunk);
            }

            DB::commit();

            $this->loadTimeSlots();
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Semua slot waktu berhasil di-generate berdasarkan jadwal hari kerja!']);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal generate waktu: ' . $e->getMessage()]);
        }
    }

    public function resetSettings()
    {
        $this->mount();
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        // 1. Save Global Settings
        $global = MstSettingAntrian::first();
        if (!$global) {
            $global = new MstSettingAntrian();
        }
        $global->mode_antrian = $this->mode_antrian;
        $global->format_nomor_antrian = $this->format_nomor_antrian;
        $global->running_text = $this->running_text;
        $global->is_active = $this->is_active;
        $global->save();

        // 2. Save Per-Day Config
        MstSettingAntrianHari::where('hari', $this->selectedDay)->update([
            'jam_buka' => $this->jam_buka,
            'jam_tutup' => $this->jam_tutup,
            'durasi_slot' => $this->durasi_slot,
            'max_antrian' => $this->max_antrian,
            'is_holiday' => $this->is_holiday,
        ]);

        // 3. Re-generate slots
        if ($this->mode_antrian !== 'Nomor Urut') {
            $this->generateTimeSlots();
        } else {
            MstSettingAntrianDetail::truncate();
            $this->timeSlots = [];
        }

        $this->loadTimeSlots();
        $this->dispatch('alert', ['type' => 'success', 'message' => "Pengaturan hari $this->selectedDay & Global berhasil disimpan!"]);
    }

    public function render()
    {
        return view('livewire.modules.antrian.setting-antrian-page');
    }
}
