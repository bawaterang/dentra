<?php

namespace App\Modules\Dashboard\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TrxPendaftaran;
use App\Models\TrxAntrian;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;

class DashboardPage extends Component
{
    use WithPagination;

    // Summary stats
    public int $totalPatientsToday  = 0;
    public int $totalPatientsYesterday = 0;
    public int $totalVisitsMonth    = 0;
    public int $completedAppointmentsToday = 0;
    public int $pendingAppointments = 0;

    // Filter properties
    public $filterPeriod = 'monthly'; // daily, monthly, yearly
    public array $filterOptions = [];

    // Monthly visits chart data
    public array $chartLabels = [];
    public array $chartVisits = [];
    public array $chartInsuranceLabels = [];
    public array $chartInsuranceData = [];

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

        // Pasien Kemarin
        $this->totalPatientsYesterday = TrxPendaftaran::whereDate('created_at', Carbon::yesterday())
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
        $antrians = TrxAntrian::with('pasien')
            ->whereNotNull('created_at')
            ->latest('created_at')
            ->limit(5)
            ->get();
        foreach ($antrians as $a) {
             $timeStr = $a->getOriginal('created_at');
             if (empty($timeStr)) continue;
             $activities[] = [
                 'act_time' => $timeStr,
                 'initials' => strtoupper(substr($a->pasien?->nama_pasien ?? $a->nama_pasien_input_manual ?? 'P', 0, 2)),
                 'color' => '#10b981',
                 'msg' => ($a->pasien?->nama_pasien ?? $a->nama_pasien_input_manual ?? 'Pasien') . " mengambil antrian " . ($a->jenis_antrian == 'online' ? 'Online' : 'Klinik'),
             ];
        }

        // 2. Get newer Pendaftaran
        $pendaftrans = TrxPendaftaran::with('pasien', 'poli')
            ->whereNotNull('created_at')
            ->latest('created_at')
            ->limit(5)
            ->get();
        foreach ($pendaftrans as $p) {
             $timeStr = $p->getOriginal('created_at');
             if (empty($timeStr)) continue;
             $activities[] = [
                 'act_time' => $timeStr,
                 'initials' => strtoupper(substr($p->pasien?->nama_pasien ?? 'P', 0, 2)),
                 'color' => '#3b82f6',
                 'msg' => ($p->pasien?->nama_pasien ?? 'Pasien') . " diregistrasi di " . ($p->poli?->nama_poli ?? 'Poli'),
             ];
        }

        // 3. Get newer Billing (use tanggal_bayar since created_at is often null)
        $billings = DB::table('trx_billing')
            ->join('mst_pasien', 'trx_billing.pasien_id', '=', 'mst_pasien.id')
            ->select('trx_billing.*', 'mst_pasien.nama_pasien')
            ->whereNull('trx_billing.deleted_at')
            ->whereNotNull('trx_billing.tanggal_bayar')
            ->orderBy('trx_billing.tanggal_bayar', 'desc')
            ->limit(5)
            ->get();
            
        foreach ($billings as $b) {
             $timeStr = $b->tanggal_bayar;
             if (empty($timeStr)) continue;
             $verb = ($b->status === 'Lunas') ? 'menyelesaikan pembayaran' : 'memiliki tagihan aktif';
             $color = ($b->status === 'Lunas') ? '#8b5cf6' : '#f59e0b';
             $activities[] = [
                 'act_time' => $timeStr,
                 'initials' => strtoupper(substr($b->nama_pasien ?? 'P', 0, 2)),
                 'color' => $color,
                 'msg' => ($b->nama_pasien) . " " . $verb,
             ];
        }

        // Sort by raw timestamp string descending
        usort($activities, function($a, $b) {
            return strcmp($b['act_time'], $a['act_time']);
        });

        // Take top 6 and format time from the raw DB string using App timezone
        $this->activities = array_slice(array_map(function($act) {
            // explicitly set timezone to Asia/Jakarta before formatting
            $act['time_formatted'] = Carbon::parse($act['act_time'])->timezone('Asia/Jakarta')->format('H:i');
            unset($act['act_time']);
            return $act;
        }, $activities), 0, 6);
    }
    
    public function updatedFilterPeriod()
    {
        $this->loadChartData();
        $this->dispatch('update-chart', [
            'labels' => $this->chartLabels, 
            'visits' => $this->chartVisits, 
            'insuranceLabels' => $this->chartInsuranceLabels,
            'insuranceData' => $this->chartInsuranceData
        ]);
    }

    public function loadChartData()
    {
        if (empty($this->filterPeriod)) {
            $this->filterPeriod = 'daily';
        }

        $labels = [];
        $visits = [];

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

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $v = $visitsData[$i] ?? 0;
                
                if ($v > 0) {
                    $labels[] = $i . ' ' . $now->translatedFormat('M');
                    $visits[] = $v;
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

            for ($i = 1; $i <= 12; $i++) {
                $v = $visitsData[$i] ?? 0;
                
                if ($v > 0) {
                    $labels[] = Carbon::create()->month($i)->translatedFormat('M');
                    $visits[] = $v;
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

            for ($i = $startYear; $i <= $endYear; $i++) {
                $v = $visitsData[$i] ?? 0;
                
                if ($v > 0) {
                    $labels[] = (string) $i;
                    $visits[] = $v;
                }
            }
        }

        $this->chartLabels = $labels;
        $this->chartVisits = $visits;

        // --- Fetch Insurance Data according to filter period ---
        $insuranceDataQuery = DB::table('trx_pendaftaran')
            ->join('mst_asuransi', 'trx_pendaftaran.asuransi_id', '=', 'mst_asuransi.id')
            ->select('mst_asuransi.nama_asuransi as label', DB::raw('count(*) as count'))
            ->whereNull('trx_pendaftaran.deleted_at')
            ->groupBy('trx_pendaftaran.asuransi_id', 'mst_asuransi.nama_asuransi');

        if ($this->filterPeriod === 'daily') {
            $insuranceDataQuery->whereMonth('trx_pendaftaran.created_at', $now->month)
                               ->whereYear('trx_pendaftaran.created_at', $now->year);
        } elseif ($this->filterPeriod === 'monthly') {
            $insuranceDataQuery->whereYear('trx_pendaftaran.created_at', $now->year);
        } elseif ($this->filterPeriod === 'yearly') {
            $insuranceDataQuery->whereYear('trx_pendaftaran.created_at', '>=', $now->year - 4);
        }

        $insuranceResults = $insuranceDataQuery->get();
        $this->chartInsuranceLabels = $insuranceResults->pluck('label')->toArray();
        $this->chartInsuranceData = $insuranceResults->pluck('count')->toArray();
    }

    public function render()
    {
        $appointments = DB::table('trx_antrian')
            ->select(
                'trx_antrian.created_at as antrian_time',
                'trx_antrian.status as status_antrian',
                'trx_antrian.nama_pasien_input_manual',
                'mst_pasien.nama_pasien as nama_pasien_master',
                'mst_dokter.nama_dokter',
                'mst_poli.nama_poli',
                'trx_pendaftaran.id as id_pendaftaran',
                'trx_pemeriksaan.id as id_pemeriksaan',
                'trx_billing.id as id_billing',
                'trx_billing.status as status_billing',
                'trx_billing.total_bayar'
            )
            ->leftJoin('trx_pendaftaran', function($join) {
                $join->on('trx_antrian.id', '=', 'trx_pendaftaran.antrian_id')
                     ->whereNull('trx_pendaftaran.deleted_at');
            })
            ->leftJoin('trx_pemeriksaan', function($join) {
                $join->on('trx_pendaftaran.nomor_kunjungan', '=', 'trx_pemeriksaan.nomor_kunjungan')
                     ->whereNull('trx_pemeriksaan.deleted_at');
            })
            ->leftJoin('trx_billing', function($join) {
                $join->on('trx_pendaftaran.nomor_kunjungan', '=', 'trx_billing.nomor_kunjungan')
                     ->whereNull('trx_billing.deleted_at');
            })
            ->leftJoin('mst_pasien', 'trx_antrian.pasien_id', '=', 'mst_pasien.id')
            ->leftJoin('mst_dokter', 'trx_antrian.kode_dokter', '=', 'mst_dokter.kode_dokter')
            ->leftJoin('mst_poli', 'trx_antrian.kode_poli', '=', 'mst_poli.kode_poli')
            ->whereDate('trx_antrian.tanggal_antrian', Carbon::today())
            ->orderBy('trx_antrian.created_at', 'asc')
            ->paginate(10);

        $appointments->getCollection()->transform(function($row) {
            $patientName = $row->nama_pasien_master ?? $row->nama_pasien_input_manual ?? '-';
            
            $statusBadge = 'pending';
            $statusText = '';

            if ($row->status_antrian === 'batal') {
                $statusText = 'Dibatalkan';
                $statusBadge = 'cancelled';
            } elseif (empty($row->id_pendaftaran)) {
                $statusText = 'Antri dan Belum didaftarkan';
                $statusBadge = 'pending';
            } elseif (empty($row->id_pemeriksaan)) {
                $statusText = 'Belum diperiksa';
                $statusBadge = 'warning';
            } else {
                if (!empty($row->id_billing) && $row->status_billing === 'Lunas') {
                    $statusText = 'Sudah dilayani (Selesai)';
                    $statusBadge = 'completed';
                } elseif (!empty($row->id_billing) && floatval($row->total_bayar) > 0) {
                    $statusText = 'Selesai, belum lunas';
                    $statusBadge = 'confirmed';
                } else {
                    $statusText = 'Sedang diperiksa';
                    $statusBadge = 'confirmed';
                }
            }

            // Return associative array to mimic previous item structure for blade
            return [
                'time' => Carbon::parse($row->antrian_time)->format('H:i'),
                'patient' => $patientName,
                'doctor' => $row->nama_dokter ?? '-',
                'type' => $row->nama_poli ?? '-',
                'status' => $statusBadge,
                'statusText' => $statusText
            ];
        });

        return view('modules.dashboard.index', [
            'appointments' => $appointments
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
