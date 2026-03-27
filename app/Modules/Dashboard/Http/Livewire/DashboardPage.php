<?php

namespace App\Modules\Dashboard\Http\Livewire;

use Livewire\Component;

class DashboardPage extends Component
{
    // Summary stats
    public int $totalPatientsToday   = 24;
    public int $totalVisitsMonth     = 312;
    public string $revenueMonth      = 'Rp 28.450.000';
    public int $pendingAppointments  = 8;

    // Upcoming appointments (mock)
    public array $appointments = [
        ['time' => '08:00', 'patient' => 'Ahmad Fauzi',       'doctor' => 'drg. Rina S.',   'type' => 'Scaling',          'status' => 'confirmed'],
        ['time' => '09:30', 'patient' => 'Siti Rahayu',       'doctor' => 'drg. Budi H.',   'type' => 'Pencabutan',       'status' => 'confirmed'],
        ['time' => '10:00', 'patient' => 'Budi Santoso',      'doctor' => 'drg. Rina S.',   'type' => 'Konsultasi',       'status' => 'pending'],
        ['time' => '11:00', 'patient' => 'Dewi Kusuma',       'doctor' => 'drg. Ahmad T.',  'type' => 'Tambal Gigi',      'status' => 'confirmed'],
        ['time' => '13:00', 'patient' => 'Rizky Pratama',     'doctor' => 'drg. Budi H.',   'type' => 'Scaling',          'status' => 'pending'],
        ['time' => '14:30', 'patient' => 'Nurul Hidayah',     'doctor' => 'drg. Ahmad T.',  'type' => 'Cabut Gigi Susu',  'status' => 'confirmed'],
        ['time' => '15:00', 'patient' => 'Hendra Wijaya',     'doctor' => 'drg. Rina S.',   'type' => 'Kontrol',          'status' => 'completed'],
        ['time' => '16:00', 'patient' => 'Ratna Dewi',        'doctor' => 'drg. Budi H.',   'type' => 'Tambal Gigi',      'status' => 'pending'],
    ];

    // Monthly visits chart data (Jan–Dec mock)
    public array $chartLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    public array $chartVisits = [210, 185, 240, 198, 275, 312, 290, 328, 301, 350, 280, 312];
    public array $chartRevenue = [18.2, 15.5, 21.3, 17.8, 24.1, 28.4, 25.6, 30.1, 27.3, 32.8, 24.5, 28.4];

    public function render()
    {
        return view('modules.dashboard.index')
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
