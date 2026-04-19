<div>
    {{-- ══════════════════════════════════════════════════ --}}
    {{-- PAGE HEADER --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div class="page-header mb-8">
        <div class="page-header-title">
            <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                <i class="ri-dashboard-2-line"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Dashboard Utama</h1>
                <p class="text-xs text-[#878a99] font-medium mt-0.5">Ringkasan data operasional dan statistik klinik hari ini.</p>
            </div>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
            <span class="sep text-gray-300">/</span>
            <span class="text-gray-400 font-medium">Dashboard</span>
            <span class="sep text-gray-300">/</span>
            <span class="text-[#405189] font-bold">Ringkasan</span>
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
            <div class="stat-trend {{ $totalPatientsToday >= $totalPatientsYesterday ? 'up' : 'down' }}">
                @if($totalPatientsToday >= $totalPatientsYesterday)
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15" />
                </svg>
                @else
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
                @endif
                Real-time
                <span class="label">Kemarin: {{ $totalPatientsYesterday }} pasien</span>
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
                                <span class="badge {{ $item['status'] }}" style="text-transform: none;">{{ $item['statusText'] }}</span>
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
            @if($appointments->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $appointments->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- BOTTOM GRID: Revenue Trend + Recent Activity --}}
    {{-- ══════════════════════════════════════════════════ --}}
    <div class="bottom-grid" style="margin-top:24px;">

        {{-- Doughnut Chart: Insurance Status --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Statistik Jumlah Pasien Berdasarkan Penjamin</h2>
            </div>
            <div class="card-body">
                @if(empty($chartInsuranceLabels))
                <div class="text-center py-4 text-gray-400 text-sm italic">Belum ada data asuransi pada periode ini.</div>
                @else
                <div class="chart-wrap" style="height:220px;" wire:ignore>
                    <canvas id="insuranceChart"></canvas>
                </div>
                <div id="insuranceLegend" style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:20px;">
                    {{-- Legend items injected by JS --}}
                </div>
                @endif
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
            let insLabels = @json($chartInsuranceLabels);
            let insData = @json($chartInsuranceData);

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

            // -- Setup Insurance Chart --
            const insCtx = document.getElementById('insuranceChart') ? document.getElementById('insuranceChart').getContext('2d') : null;
            const insColors = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899', '#06b6d4', '#f43f5e', '#84cc16'];
            
            const renderInsLegend = (labels, data) => {
                const legend = document.getElementById('insuranceLegend');
                if(!legend) return;
                legend.innerHTML = '';
                labels.forEach((label, i) => {
                    const color = insColors[i % insColors.length];
                    const val = data[i];
                    legend.innerHTML += `
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="width:8px;height:8px;border-radius:2px;background:${color};flex-shrink:0;"></span>
                            <span style="font-size:11px;font-weight:600;">${label} (${val})</span>
                        </div>
                    `;
                });
            };

            if (insCtx) {
                if (window.insuranceChartInst) window.insuranceChartInst.destroy();
                window.insuranceChartInst = new Chart(insCtx, {
                    type: 'doughnut',
                    data: {
                        labels: insLabels,
                        datasets: [{
                            data: insData,
                            backgroundColor: insColors,
                            borderWidth: 4,
                            borderColor: '#fff',
                            hoverOffset: 10,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
                        }
                    }
                });
                renderInsLegend(insLabels, insData);
            }

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

                    // Update Insurance Chart
                    if (window.insuranceChartInst) {
                        window.insuranceChartInst.data.labels = res.insuranceLabels;
                        window.insuranceChartInst.data.datasets[0].data = res.insuranceData;
                        window.insuranceChartInst.update();
                        renderInsLegend(res.insuranceLabels, res.insuranceData);
                    }
                });
                window.chartListenerAttached = true;
            }
        });
    </script>
</div>