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
            $user = Auth::user()->name ?? 'System';

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

            <div class="mt-6 px-4 pb-12">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Left Column: Settings Configuration -->
                    <div class="lg:col-span-3 space-y-6">
                        <form wire:submit.prevent="save">
                            <div class="card overflow-hidden border-t-4 {{ $is_holiday ? 'border-red-500' : 'border-[#405189]' }} shadow-lg rounded-xl flex flex-col transition-all duration-300">
                                <div class="p-6 border-b border-[#eff2f7] {{ $is_holiday ? 'bg-red-50/50' : 'bg-gray-50/50' }} transition-colors duration-300">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                                            <i class="ri-equalizer-line {{ $is_holiday ? 'text-red-500' : 'text-[#405189]' }}"></i> Konfigurasi Sistem Antrian
                                        </h5>
                                        @if($is_holiday)
                                        <span class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold animate-pulse">
                                            <i class="ri-calendar-close-line"></i> HARI LIBUR
                                        </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-[#878a99] mt-1">Atur parameter global dan jadwal operasional harian.</p>
                                </div>
                                
                                <div class="p-6 space-y-8 flex-grow">
                                    <!-- Section: Global -->
                                    <div>
                                        <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                                            <i class="ri-global-line"></i> Pengaturan Global (Berlaku Semua Hari)
                                        </h6>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Mode Antrian</label>
                                                <div class="flex flex-col gap-3">
                                                    @foreach(['Nomor Urut' => 'blue', 'Waktu Periksa' => 'green', 'Keduanya' => 'purple'] as $mode => $color)
                                                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-all {{ $mode_antrian === $mode ? "border-{$color}-500 bg-{$color}-50/30" : 'border-gray-200' }}">
                                                        <input type="radio" wire:model="mode_antrian" value="{{ $mode }}" class="w-4 h-4 text-{{ $color }}-600">
                                                        <div>
                                                            <p class="font-bold text-sm text-gray-800">{{ $mode }}</p>
                                                            <p class="text-[11px] text-gray-500">
                                                                {{ $mode === 'Nomor Urut' ? 'Pasien dipanggil berdasarkan urutan kedatangan.' : ($mode === 'Waktu Periksa' ? 'Pasien datang sesuai dengan jam reservasi.' : 'Waktu periksa diatur berbanding lurus dengan nomor urut.') }}
                                                            </p>
                                                        </div>
                                                    </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            
                                            <div class="space-y-6">
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Format Nomor Antrian</label>
                                                    <input type="text" wire:model.lazy="format_nomor_antrian" class="w-full rounded-xl border-gray-200 text-sm px-4 h-11 focus:border-[#405189] transition-all shadow-sm font-mono" placeholder="Misal: A000">
                                                    <p class="text-[10px] text-gray-500 mt-1">Harus diakhiri angka. Contoh: <b>A000</b> (Berarti nomor mulai dari A001).</p>
                                                    @error('format_nomor_antrian') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status Sistem Kiosk</label>
                                                    <div class="flex items-center justify-between p-4 border rounded-xl {{ $is_active ? 'border-green-200 bg-green-50/30' : 'border-red-200 bg-red-50/30' }}">
                                                        <div>
                                                            <p class="font-bold text-sm text-gray-800">{{ $is_active ? 'Aktif' : 'Non-Aktif' }}</p>
                                                            <p class="text-[11px] text-gray-500">Mengontrol apakah pasien bisa mengambil tiket.</p>
                                                        </div>
                                                        <div class="form-check form-switch form-switch-lg form-switch-success">
                                                            <input type="checkbox" wire:model="is_active" class="form-check-input" id="isActiveSwitch">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Teks Monitor (Marquee)</label>
                                                    <textarea wire:model="running_text" class="w-full rounded-xl border-gray-200 text-sm p-4 h-24 focus:border-[#405189] transition-all resize-none shadow-sm" placeholder="Contoh: Selamat datang di SIGI Dental Clinic! Mohon menunggu antrian Anda dipanggil."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Section: Per-Day -->
                                    <div class="{{ $is_holiday ? 'bg-red-50/30 border-red-200' : 'bg-gray-50/50 border-indigo-200' }} p-6 rounded-2xl border border-dashed relative transition-all duration-300">
                                        <div class="absolute -top-3 left-6 px-3 bg-white border {{ $is_holiday ? 'border-red-200' : 'border-gray-200' }} rounded-full text-xs font-bold transition-all duration-300">
                                            Pengaturan Khusus Hari: <span class="{{ $is_holiday ? 'text-red-600' : 'text-[#405189]' }} uppercase">{{ $selectedDay }}</span>
                                        </div>

                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 mt-2 gap-3">
                                            <h6 class="text-xs font-bold text-gray-600 uppercase tracking-widest">Jam Operasional & Kuota</h6>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" wire:click="toggleHoliday" class="text-[10px] font-bold {{ $is_holiday ? 'bg-red-600 text-white border-red-700' : 'bg-white text-red-600 border-red-100' }} hover:shadow-md px-3 py-1 rounded-full border transition-all flex items-center gap-1">
                                                    <i class="{{ $is_holiday ? 'ri-sun-line' : 'ri-calendar-close-line' }}"></i> {{ $is_holiday ? 'Atur Jadi Hari Kerja' : 'Atur Hari Libur' }}
                                                </button>
                                                <button type="button" wire:click="applyToAllDays" class="text-[10px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 bg-white px-3 py-1 rounded-full border border-blue-100 shadow-sm hover:-translate-y-0.5 transition-all">
                                                    <i class="ri-file-copy-line"></i> Samakan ke Semua Hari
                                                </button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 {{ $is_holiday ? 'opacity-30 pointer-events-none' : '' }} transition-opacity duration-300">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Jam Buka</label>
                                                <input type="time" wire:model="jam_buka" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-mono font-bold text-[#405189]">
                                                @error('jam_buka') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Jam Tutup</label>
                                                <input type="time" wire:model="jam_tutup" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-mono font-bold text-orange-600">
                                                @error('jam_tutup') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Durasi Slot</label>
                                                <div class="relative">
                                                    <input type="number" wire:model="durasi_slot" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-bold pr-10">
                                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">mnt</span>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Maks. Antrian</label>
                                                <div class="relative">
                                                    <input type="number" wire:model="max_antrian" class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] shadow-sm font-bold pr-12">
                                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">pasien</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="p-5 bg-gray-50 flex flex-col sm:flex-row justify-end gap-3 border-t border-[#eff2f7]">
                                    <button type="button" class="btn bg-gray-500 text-white font-bold text-sm px-6 hover:bg-gray-600 transition-all justify-center" wire:click="resetSettings">
                                        <i class="ri-refresh-line"></i> Reset
                                    </button>
                                    <button type="submit" class="btn bg-[#0d6efd] text-white font-bold text-sm px-8 shadow-md hover:bg-[#0b5ed7] transition-all justify-center">
                                        <i class="ri-save-3-line"></i> Simpan Pengaturan
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Holiday Range Management -->
                        <div class="card border-t-4 border-orange-500 shadow-lg rounded-xl flex flex-col overflow-hidden">
                            <div class="p-5 border-b border-[#eff2f7] bg-gray-50/50">
                                <h5 class="text-sm font-bold text-[#495057] flex items-center gap-2">
                                    <i class="ri-calendar-event-line text-orange-500"></i> Manajemen Hari Libur Khusus (Tanggal Tertentu)
                                </h5>
                                <p class="text-[11px] text-[#878a99] mt-1">Blokir antrian & pendaftaran untuk rentang tanggal libur nasional atau cuti.</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Form Add Holiday -->
                                <div class="md:col-span-1 space-y-4 md:border-r md:pr-6 border-gray-100">
                                    <h6 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Tambah Hari Libur</h6>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Tanggal Mulai</label>
                                        <input type="date" wire:model="libur_mulai" class="w-full rounded-lg border-gray-200 text-xs px-4 h-10 focus:border-orange-500 shadow-sm">
                                        @error('libur_mulai') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Tanggal Selesai</label>
                                        <input type="date" wire:model="libur_selesai" class="w-full rounded-lg border-gray-200 text-xs px-4 h-10 focus:border-orange-500 shadow-sm">
                                        @error('libur_selesai') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Keterangan</label>
                                        <input type="text" wire:model="libur_keterangan" class="w-full rounded-lg border-gray-200 text-xs px-4 h-10 focus:border-orange-500 shadow-sm" placeholder="Contoh: Libur Idul Fitri">
                                        @error('libur_keterangan') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <button type="button" wire:click="addLiburRange" class="w-full btn bg-[#0d6efd] text-white font-bold text-xs h-10 rounded-lg hover:bg-[#0b5ed7] transition-all flex items-center justify-center gap-2">
                                        <i class="ri-save-3-line"></i> Simpan Libur
                                    </button>
                                </div>

                                <!-- List Holidays -->
                                <div class="md:col-span-2">
                                    <h6 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Daftar Hari Libur Aktif</h6>
                                    <div class="overflow-auto max-h-[300px] border rounded-xl bg-gray-50/30">
                                        <table class="w-full text-left border-collapse text-xs">
                                            <thead>
                                                <tr class="bg-gray-100/80">
                                                    <th class="px-4 py-2 font-bold text-gray-600 border-b">Rentang Tanggal</th>
                                                    <th class="px-4 py-2 font-bold text-gray-600 border-b">Keterangan</th>
                                                    <th class="px-4 py-2 font-bold text-gray-600 border-b text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @forelse($listLibur as $libur)
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-4 py-3 font-medium text-[#405189]">
                                                        {{ \Carbon\Carbon::parse($libur->tanggal_mulai)->format('d M Y') }} - {{ \Carbon\Carbon::parse($libur->tanggal_selesai)->format('d M Y') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-500 italic">{{ $libur->keterangan }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        <button type="button" wire:click="deleteLiburRange({{ $libur->id }})" class="text-red-400 hover:text-red-600 transition-all">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="px-4 py-8 text-center text-gray-400 italic">Belum ada hari libur khusus yang ditambahkan.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Time Selection / Detail Slots -->
                    <div class="lg:col-span-2">
                        <div class="card overflow-hidden border-t-4 border-[#0ab39c] shadow-lg rounded-xl h-full flex flex-col">
                            <div class="p-6 border-b border-[#eff2f7] bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                                        <i class="ri-time-line text-[#0ab39c]"></i> Preview Jadwal
                                    </h5>
                                    <p class="text-sm text-[#878a99] mt-1">Slot waktu otomatis yang dihasilkan.</p>
                                </div>
                                @if($mode_antrian !== 'Nomor Urut')
                                <button type="button" wire:click="generateTimeSlots" class="btn bg-[#0ab39c] text-white font-bold text-xs px-4 py-2 rounded-lg hover:bg-[#099885] transition-all flex items-center justify-center gap-2 shadow-sm">
                                    <i class="ri-flashlight-line"></i> Update Semua Hari
                                </button>
                                @endif
                            </div>

                            <div class="p-6 flex-grow flex flex-col overflow-hidden">
                                @if($mode_antrian === 'Nomor Urut')
                                <div class="flex flex-col items-center justify-center p-12 text-center h-full">
                                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="ri-ghost-line text-4xl text-gray-300"></i>
                                    </div>
                                    <h6 class="font-bold text-gray-500">Tidak Perlu Pengaturan Waktu</h6>
                                    <p class="text-xs text-gray-400 mt-2">Mode Nomor Urut tidak menggunakan slot waktu.</p>
                                </div>
                                @else
                                <div class="flex flex-col h-full">
                                    <div class="flex items-center gap-2 overflow-x-auto pb-3 no-scrollbar">
                                        @foreach($days as $day)
                                        <button wire:click="$set('selectedDay', '{{ $day }}')" 
                                            class="flex-shrink-0 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $selectedDay === $day ? 'bg-[#0ab39c] text-white shadow-md' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                            {{ $day }}
                                        </button>
                                        @endforeach
                                    </div>

                                    <div class="mt-4 flex-grow overflow-y-auto border rounded-xl relative min-h-[300px]">
                                        @if($is_holiday)
                                        <div class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-red-50/20">
                                            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mb-4 border border-red-100">
                                                <i class="ri-calendar-event-line text-4xl text-red-300"></i>
                                            </div>
                                            <h6 class="font-bold text-red-600">Hari Libur Terdeteksi</h6>
                                            <p class="text-[10px] text-red-400 mt-2 max-w-[200px]">Tidak ada slot waktu yang dihasilkan karena hari <b>{{ $selectedDay }}</b> diatur sebagai hari libur mingguan.</p>
                                        </div>
                                        @else
                                        <table class="w-full text-left border-collapse">
                                            <thead class="bg-gray-50 sticky top-0 z-10">
                                                <tr>
                                                    <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider border-b text-center">No</th>
                                                    <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider border-b">Jam Periksa</th>
                                                    <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider border-b">No. Urut</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white shadow-sm">
                                                @forelse($timeSlots as $index => $slot)
                                                <tr class="hover:bg-gray-50/50 transition-colors" wire:key="slot-{{ $slot->id }}">
                                                    <td class="px-4 py-3 text-xs font-bold text-gray-400 text-center">{{ $index + 1 }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex items-center px-4 py-1 rounded-lg text-xs font-bold bg-[#0ab39c15] text-[#0ab39c]">
                                                            <i class="ri-time-fill mr-1 opacity-50"></i> {{ substr($slot->waktu, 0, 5) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-xs font-bold text-[#405189]">
                                                        {{ $slot->nomor_urut }}
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="px-4 py-12 text-center">
                                                        <div class="flex flex-col items-center">
                                                            <i class="ri-calendar-todo-line text-3xl text-gray-200 mb-2"></i>
                                                            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Belum ada data untuk hari ini</p>
                                                            <button type="button" wire:click="generateTimeSlots" class="mt-4 text-[10px] font-bold text-[#0ab39c] uppercase flex items-center gap-1 hover:underline">
                                                                <i class="ri-magic-line"></i> Generate Sekarang
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                        @endif
                                    </div>
                                    <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                                        <div class="flex gap-3">
                                            <i class="ri-information-line text-blue-600 text-lg"></i>
                                            <div class="text-[10px] text-blue-800 leading-relaxed font-medium">
                                                Jadwal di atas bersifat dinamis. Pilih hari pada tab untuk melihat konfigurasi spesifik masing-masing hari.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
