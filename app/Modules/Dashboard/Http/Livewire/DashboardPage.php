<?php

namespace App\Modules\Dashboard\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use App\Models\TrxAntrian;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;

class DashboardPage extends Component
{
    // Summary stats
    public int $totalPatientsToday  = 0;
    public int $totalVisitsMonth    = 0;
    public int $completedAppointmentsToday = 0;
    public int $pendingAppointments = 0;

    // Filter properties
    public string $filterPeriod = 'monthly'; // daily, monthly, yearly
    public array $filterOptions = [];

    // Upcoming appointments
    public array $appointments = [];

    // Monthly visits chart data
    public array $chartLabels = [];
    public array $chartVisits = [];
    public array $chartRevenue = [];

    // Schedule Status Chart Data
    public array $statusChartData = []; // [confirmed, pending, completed]

    // Recent Activity
    public array $activities = [];

    public function mount()
    {
        $this->filterOptions = [
            ['value' => 'daily', 'label' => 'Harian', 'icon' => 'ri-calendar-event-line text-[#405189]'],
            ['value' => 'monthly', 'label' => 'Bulanan', 'icon' => 'ri-calendar-line text-[#0ab39c]'],
            ['value' => 'yearly', 'label' => 'Tahunan', 'icon' => 'ri-calendar-2-line text-[#f59e0b]'],
        ];

        $this->loadSummaryStats();
        $this->loadAppointments();
        $this->loadChartData();
        $this->loadStatusChart();
        $this->loadRecentActivity();
    }

    public function loadSummaryStats()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Pasien Hari Ini
        $this->totalPatientsToday = TrxPendaftaran::whereDate('created_at', $today)
            ->whereNull('deleted_at')
            ->count();

        // Kunjungan Bulan Ini
        $this->totalVisitsMonth = TrxPendaftaran::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNull('deleted_at')
            ->count();

        // Antrian Selesai Hari Ini
        $this->completedAppointmentsToday = TrxAntrian::where('status', 'selesai')
            ->whereDate('tanggal_antrian', $today)
            ->count();

        // Antrian Pending
        $this->pendingAppointments = TrxAntrian::where('status', 'menunggu')
            ->whereDate('tanggal_antrian', $today)
            ->count();
    }

    public function loadAppointments()
    {
        $records = TrxPendaftaran::with(['pasien', 'dokter', 'poli'])
            ->whereDate('created_at', Carbon::today())
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'asc')
            ->limit(15)
            ->get();

        $this->appointments = [];
        foreach ($records as $record) {
            $statusMap = [
                'terdaftar' => 'pending',
                'menunggu_screening' => 'confirmed',
                'pemeriksaan' => 'confirmed',
                'selesai' => 'completed',
                'batal' => 'cancelled'
            ];
            
            $statusLabel = $statusMap[$record->status] ?? 'pending';
            
            $this->appointments[] = [
                'time' => $record->created_at->format('H:i'),
                'patient' => $record->pasien?->nama_pasien ?? '-',
                'doctor' => $record->dokter?->nama_dokter ?? '-',
                'type' => $record->poli?->nama_poli ?? '-',
                'status' => $statusLabel
            ];
        }
    }

    public function loadStatusChart()
    {
        // For today's status (using trx_antrian as requested)
        $stats = DB::table('trx_antrian')
            ->whereDate('tanggal_antrian', Carbon::today())
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // ordered exactly as requested: menunggu, dipanggil, hadir, tidak hadir, batal, selesai
        $this->statusChartData = [
            $stats['menunggu'] ?? 0,
            $stats['dipanggil'] ?? 0,
            $stats['hadir'] ?? 0,
            $stats['tidak hadir'] ?? 0,
            $stats['batal'] ?? 0,
            $stats['selesai'] ?? 0
        ];
    }

    public function loadRecentActivity()
    {
        $activities = [];

        // 1. Get newer Antrian
        $antrians = TrxAntrian::with('pasien')->latest('created_at')->limit(5)->get();
        foreach ($antrians as $a) {
             $activities[] = [
                 'time' => $a->created_at,
                 'initials' => strtoupper(substr($a->pasien?->nama_pasien ?? $a->nama_pasien_input_manual ?? 'P', 0, 2)),
                 'color' => '#10b981',
                 'msg' => ($a->pasien?->nama_pasien ?? $a->nama_pasien_input_manual ?? 'Pasien') . " mengambil antrian " . ($a->jenis_antrian == 'online' ? 'Online' : 'Klinik'),
             ];
        }

        // 2. Get newer Pendaftaran
        $pendaftrans = TrxPendaftaran::with('pasien', 'poli')->latest('created_at')->limit(5)->get();
        foreach ($pendaftrans as $p) {
             $activities[] = [
                 'time' => $p->created_at,
                 'initials' => strtoupper(substr($p->pasien?->nama_pasien ?? 'P', 0, 2)),
                 'color' => '#3b82f6',
                 'msg' => ($p->pasien?->nama_pasien ?? 'Pasien') . " diregistrasi di " . ($p->poli?->nama_poli ?? 'Poli'),
             ];
        }

        // 3. Get newer Billing
        $billings = DB::table('trx_billing')
            ->join('mst_pasien', 'trx_billing.pasien_id', '=', 'mst_pasien.id')
            ->select('trx_billing.*', 'mst_pasien.nama_pasien')
            ->whereNull('trx_billing.deleted_at')
            ->orderBy('trx_billing.created_at', 'desc')
            ->limit(5)
            ->get();
            
        foreach ($billings as $b) {
             $verb = ($b->status === 'Lunas') ? 'menyelesaikan pembayaran' : 'memiliki tagihan aktif';
             $color = ($b->status === 'Lunas') ? '#8b5cf6' : '#f59e0b';
             $activities[] = [
                 'time' => Carbon::parse($b->created_at),
                 'initials' => strtoupper(substr($b->nama_pasien ?? 'P', 0, 2)),
                 'color' => $color,
                 'msg' => ($b->nama_pasien) . " " . $verb,
             ];
        }

        // Sort combined array
        usort($activities, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });

        // Take top 5 and format time
        $this->activities = array_slice(array_map(function($act) {
            $act['time_formatted'] = $act['time']->format('H:i');
            return $act;
        }, $activities), 0, 6);
    }
    
    public function updatedFilterPeriod()
    {
        $this->loadChartData();
        $this->dispatch('update-chart', [
            'labels' => $this->chartLabels, 
            'visits' => $this->chartVisits, 
            'revenue' => $this->chartRevenue
        ]);
    }

    public function loadChartData()
    {
        $labels = [];
        $visits = [];
        $revenue = [];

        $now = Carbon::now();

        if ($this->filterPeriod === 'daily') {
            // Daily for current month
            $daysInMonth = $now->daysInMonth;
            
            $visitsData = TrxPendaftaran::whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->whereNull('deleted_at')
                ->selectRaw('DAY(created_at) as day, count(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $revenueData = DB::table('trx_billing')
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->whereNull('deleted_at')
                ->selectRaw('DAY(created_at) as day, SUM(total_bayar) as sum')
                ->groupBy('day')
                ->pluck('sum', 'day')
                ->toArray();

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $v = $visitsData[$i] ?? 0;
                $r = $revenueData[$i] ?? 0;
                
                // Exclude 0 revenue / 0 visits days
                if ($v > 0 || $r > 0) {
                    $labels[] = $i . ' ' . $now->translatedFormat('M');
                    $visits[] = $v;
                    $revenue[] = round($r / 1000000, 2); // In millions
                }
            }
        } elseif ($this->filterPeriod === 'monthly') {
            // Monthly for current year
            $visitsData = TrxPendaftaran::whereYear('created_at', $now->year)
                ->whereNull('deleted_at')
                ->selectRaw('MONTH(created_at) as month, count(*) as count')
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray();

            $revenueData = DB::table('trx_billing')
                ->whereYear('created_at', $now->year)
                ->whereNull('deleted_at')
                ->selectRaw('MONTH(created_at) as month, SUM(total_bayar) as sum')
                ->groupBy('month')
                ->pluck('sum', 'month')
                ->toArray();

            for ($i = 1; $i <= 12; $i++) {
                $v = $visitsData[$i] ?? 0;
                $r = $revenueData[$i] ?? 0;
                
                if ($v > 0 || $r > 0) {
                    $labels[] = Carbon::create()->month($i)->translatedFormat('M');
                    $visits[] = $v;
                    $revenue[] = round($r / 1000000, 2);
                }
            }
        } elseif ($this->filterPeriod === 'yearly') {
            // Yearly for last 5 years
            $startYear = $now->year - 4;
            $endYear = $now->year;

            $visitsData = TrxPendaftaran::whereYear('created_at', '>=', $startYear)
                ->whereNull('deleted_at')
                ->selectRaw('YEAR(created_at) as year, count(*) as count')
                ->groupBy('year')
                ->pluck('count', 'year')
                ->toArray();

            $revenueData = DB::table('trx_billing')
                ->whereYear('created_at', '>=', $startYear)
                ->whereNull('deleted_at')
                ->selectRaw('YEAR(created_at) as year, SUM(total_bayar) as sum')
                ->groupBy('year')
                ->pluck('sum', 'year')
                ->toArray();

            for ($i = $startYear; $i <= $endYear; $i++) {
                $v = $visitsData[$i] ?? 0;
                $r = $revenueData[$i] ?? 0;
                
                if ($v > 0 || $r > 0) {
                    $labels[] = (string) $i;
                    $visits[] = $v;
                    $revenue[] = round($r / 1000000, 2);
                }
            }
        }

        $this->chartLabels = $labels;
        $this->chartVisits = $visits;
        $this->chartRevenue = $revenue;
    }

    public function render()
    {
        return view('modules.dashboard.index')
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
