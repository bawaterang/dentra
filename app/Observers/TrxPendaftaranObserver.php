<?php

namespace App\Observers;

use App\Models\TrxPendaftaran;
use App\Models\User;
use App\Notifications\RealtimeNotification;
use Illuminate\Support\Facades\Notification;

class TrxPendaftaranObserver
{
    /**
     * Handle the TrxPendaftaran "created" event.
     */
    public function created(TrxPendaftaran $trxPendaftaran): void
    {
        $pasienName = $trxPendaftaran->pasien ? $trxPendaftaran->pasien->nama_pasien : 'Pasien';
        $poliName = $trxPendaftaran->poli ? $trxPendaftaran->poli->nama_poli : 'Poli';

        Notification::send(User::all(), new RealtimeNotification(
            title: 'Pendaftaran Baru',
            message: "Pasien {$pasienName} telah berhasil didaftarkan ke {$poliName}.",
            type: 'info',
            icon: 'ri-file-list-3-line',
            url: route('pendaftaran.index')
        ));
    }
}
