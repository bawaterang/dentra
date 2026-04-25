<?php

namespace App\Observers;

use App\Models\TrxScreening;
use App\Models\User;
use App\Notifications\RealtimeNotification;
use Illuminate\Support\Facades\Notification;

class TrxScreeningObserver
{
    /**
     * Handle the TrxScreening "created" event.
     */
    public function created(TrxScreening $trxScreening): void
    {
        $pasienName = $trxScreening->pasien ? $trxScreening->pasien->nama_pasien : 'Pasien';

        Notification::send(User::all(), new RealtimeNotification(
            title: 'Screening Baru',
            message: "Hasil screening untuk pasien {$pasienName} telah diinput.",
            type: 'warning',
            icon: 'ri-heart-pulse-line',
            url: route('screening.index')
        ));
    }
}
