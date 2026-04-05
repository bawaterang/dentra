<div>
    {{-- ══════════════════════════════════════════════════ --}}
    {{-- PAGE HEADER --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon">
                <i class="ri-dashboard-line"></i>
            </div>
            <h1>Dashboard</h1>
        </div>
        <div class="page-header-breadcrumb">
            <a href="{{ route('dashboard.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
            </a>
            <span class="sep">/</span>
            <a href="#">SIGI Dental EMR</a>
            <span class="sep">/</span>
            <span>Dashboard</span>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- STAT CARDS --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div class="stat-grid">

        {{-- Pasien Hari Ini --}}
        <div class="card stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </div>
            <div>
                <div class="stat-label">Pasien Hari Ini</div>
                <div class="stat-value">{{ $totalPatientsToday }}</div>
            </div>
            <div class="stat-trend up">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15" />
                </svg>
                Real-time
                <span class="label">vs kemarin</span>
            </div>
        </div>

        {{-- Total Kunjungan Bulan Ini --}}
        <div class="card stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                </svg>
            </div>
            <div>
                <div class="stat-label">Kunjungan Bulan Ini</div>
                <div class="stat-value">{{ number_format($totalVisitsMonth, 0, ',', '.') }}</div>
            </div>
            <div class="stat-trend up">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15" />
                </svg>
                Real-time
                <span class="label">akumulasi bulan ini</span>
            </div>
        </div>

        {{-- Antrian Selesai Hari Ini --}}
        <div class="card stat-card relative group">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
            </div>
            <div>
                <div class="stat-label">Antrian Selesai</div>
                <div class="stat-value text-[1.1rem]">{{ $completedAppointmentsToday }}</div>
            </div>
            <div class="stat-trend up">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15" />
                </svg>
                Real-time
                <span class="label">hari ini</span>
            </div>
        </div>

        {{-- Antrian Pending --}}
        <div class="card stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                </svg>
            </div>
            <div>
                <div class="stat-label">Antrian Pending</div>
                <div class="stat-value">{{ $pendingAppointments }}</div>
            </div>
            <div class="stat-trend @if($pendingAppointments > 0) down @else up @endif">
                @if($pendingAppointments > 0)
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
                @else
                <i
                    class="ri-check-line mr-1 text-sm bg-emerald-100 text-emerald-600 rounded-full w-4 h-4 flex items-center justify-center"></i>
                @endif
                <span class="label" style="margin-left: 2px;">hari ini</span>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- CHARTS ROW --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div class="charts-grid">

        {{-- Bar Chart: Monthly Visits --}}
        <div class="card relative">
            <div class="card-header" style="padding-bottom: 8px;">
                <div class="flex justify-between items-center w-full">
                    <div>
                        <h2 class="card-title">Statistik Kunjungan</h2>
                        <p style="font-size:12px;color:var(--text-muted);margin:2px 0 0;">Data dinamis klinik</p>
                    </div>
                    <div style="width: 140px;">
                        <x-custom-dropdown model="filterPeriod" :options="$filterOptions" live="true" />
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-wrap" wire:ignore>
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Doughnut Chart: Appointment Status --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Status Antrian Hari Ini</h2>
            </div>
            <div class="card-body">
                <div class="chart-wrap" style="height:220px;" wire:ignore>
                    <canvas id="statusChart"></canvas>
                </div>
                <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:20px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#f59e0b;flex-shrink:0;"></span>
                        <span style="font-size:11px;font-weight:600;">Menunggu ({{ $statusChartData[0] ?? 0 }})</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#3b82f6;flex-shrink:0;"></span>
                        <span style="font-size:11px;font-weight:600;">Dipanggil ({{ $statusChartData[1] ?? 0 }})</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#10b981;flex-shrink:0;"></span>
                        <span style="font-size:11px;font-weight:600;">Hadir ({{ $statusChartData[2] ?? 0 }})</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#64748b;flex-shrink:0;"></span>
                        <span style="font-size:11px;font-weight:600;">Tidak Hadir ({{ $statusChartData[3] ?? 0 }})</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#ef4444;flex-shrink:0;"></span>
                        <span style="font-size:11px;font-weight:600;">Batal ({{ $statusChartData[4] ?? 0 }})</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#8b5cf6;flex-shrink:0;"></span>
                        <span style="font-size:11px;font-weight:600;">Selesai ({{ $statusChartData[5] ?? 0 }})</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- UPCOMING APPOINTMENTS TABLE --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Jadwal Hari Ini</h2>
            <button class="btn btn-light btn-sm">Lihat Semua</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Tindakan / Poli</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $item)
                        <tr>
                            <td class="font-bold text-[#405189]">{{ $item['time'] }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-subtle text-primary text-[11px] font-bold uppercase shrink-0">
                                        {{ strtoupper(substr($item['patient'], 0, 2)) }}
                                    </div>
                                    <span class="font-semibold">{{ $item['patient'] }}</span>
                                </div>
                            </td>
                            <td class="text-[#495057]">{{ $item['doctor'] }}</td>
                            <td>
                                <span class="text-xs text-[#878a99]">{{ $item['type'] }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $item['status'] }}">{{ ucfirst($item['status']) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-gray-400 italic">Belum ada pasien terdaftar
                                hari ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- BOTTOM GRID: Revenue Trend + Recent Activity --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div class="bottom-grid" style="margin-top:24px;">

        {{-- Line Chart: Revenue Trend --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tren Pendapatan Diterima</h2>
            </div>
            <div class="card-body">
                <div class="chart-wrap" style="height:250px;" wire:ignore>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Aktivitas Terbaru</h2>
            </div>
            <div class="card-body">
                @if(count($activities) == 0)
                <div class="text-center py-4 text-gray-400 text-sm italic">Belum ada aktivitas baru tercatat hari ini.
                </div>
                @else
                <div style="display:flex;flex-direction:column;gap:16px;">
                    @foreach($activities as $act)
                    <div style="display:flex;gap:12px;align-items:center;">
                        <div
                            style="width:36px;height:36px;border-radius:10px;background:{{ $act['color'] }};display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">
                            {{ $act['initials'] }}
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13.5px;font-weight:500;color:var(--text-heading); line-height: 1.3;">
                                {{ $act['msg'] }}</div>
                            <div style="font-size:12px;color:var(--text-muted); margin-top:2px;">{{
                                $act['time_formatted'] }} WIB</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- CHARTS INIT SCRIPT --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <script>
        document.addEventListener('livewire:navigated', () => {
            if (!document.getElementById('visitsChart')) return;

            let labels = @json($chartLabels);
            let visits = @json($chartVisits);
            let revenue = @json($chartRevenue);

            // -- Setup Visits Chart --
            const ctxVisits = document.getElementById('visitsChart').getContext('2d');
            const gradVisits = ctxVisits.createLinearGradient(0, 0, 0, 400);
            gradVisits.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            gradVisits.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

            if (window.visitsChartInst) window.visitsChartInst.destroy();
            window.visitsChartInst = new Chart(ctxVisits, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Kunjungan',
                        data: visits,
                        backgroundColor: gradVisits,
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 12
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b', padding: 12, cornerRadius: 8,
                            titleFont: { size: 13, weight: 'bold' }, bodyFont: { size: 12 }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
                        y: { grid: { borderDash: [4, 4], color: '#e2e8f0' }, ticks: { font: { size: 11 }, color: '#94a3b8' }, beginAtZero: true }
                    }
                }
            });

            // -- Setup Status Chart --
            let statusChartData = @json($statusChartData);
            if (!statusChartData || statusChartData.length === 0) statusChartData = [0, 0, 0, 0, 0, 0];

            if (window.statusChartInst) window.statusChartInst.destroy();
            window.statusChartInst = new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Menunggu', 'Dipanggil', 'Hadir', 'Tidak Hadir', 'Batal', 'Selesai'],
                    datasets: [{
                        data: statusChartData,
                        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#64748b', '#ef4444', '#8b5cf6'],
                        borderWidth: 4,
                        borderColor: '#fff',
                        hoverOffset: 10,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
                    }
                }
            });

            // -- Setup Revenue Chart --
            const ctxRev = document.getElementById('revenueChart').getContext('2d');
            const gradRev = ctxRev.createLinearGradient(0, 0, 0, 400);
            gradRev.addColorStop(0, 'rgba(139, 92, 246, 0.2)');
            gradRev.addColorStop(1, 'rgba(139, 92, 246, 0)');

            if (window.revenueChartInst) window.revenueChartInst.destroy();
            window.revenueChartInst = new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pendapatan (Jt)',
                        data: revenue,
                        borderColor: '#a855f7',
                        backgroundColor: 'rgba(168,85,247,0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#a855f7',
                        pointRadius: 4,
                        tension: 0.4,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#878a99' } },
                        y: {
                            grid: { color: '#f1f3f5' },
                            ticks: { font: { size: 11 }, color: '#878a99', callback: v => `${v}Jt` },
                            beginAtZero: true
                        }
                    }
                }
            });

            // -- Listen for Livewire update events --
            if (!window.chartListenerAttached) {
                Livewire.on('update-chart', (data) => {
                    let res = data[0]; // Object payload

                    // Update Visits Chart
                    if (window.visitsChartInst) {
                        window.visitsChartInst.data.labels = res.labels;
                        window.visitsChartInst.data.datasets[0].data = res.visits;
                        window.visitsChartInst.update();
                    }

                    // Update Revenue Chart
                    if (window.revenueChartInst) {
                        window.revenueChartInst.data.labels = res.labels;
                        window.revenueChartInst.data.datasets[0].data = res.revenue;
                        window.revenueChartInst.update();
                    }
                });
                window.chartListenerAttached = true;
            }
        });
    </script>
</div>