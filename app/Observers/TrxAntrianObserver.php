<?php

namespace App\Observers;

use App\Models\TrxAntrian;
use App\Models\User;
use App\Notifications\RealtimeNotification;
use Illuminate\Support\Facades\Notification;

class TrxAntrianObserver
{
    /**
     * Handle the TrxAntrian "created" event.
     */
    public function created(TrxAntrian $trxAntrian): void
    {
        $pasienName = $trxAntrian->pasien ? $trxAntrian->pasien->nama_pasien : ($trxAntrian->nama_pasien_input_manual ?? 'Pasien');
        $nomor = $trxAntrian->nomor_antrian;

        Notification::send(User::all(), new RealtimeNotification(
            title: 'Antrian Baru',
            message: "Pasien {$pasienName} telah mengambil antrian ({$nomor}).",
            type: 'success',
            icon: 'ri-user-add-line',
            url: route('antrian.index')
        ));
    }

    /**
     * Handle the TrxAntrian "updated" event.
     */
    public function updated(TrxAntrian $trxAntrian): void
    {
        // Check if status has changed to 'dipanggil'
        if ($trxAntrian->isDirty('status') && $trxAntrian->status === 'dipanggil') {
            $pasienName = $trxAntrian->pasien ? $trxAntrian->pasien->nama_pasien : ($trxAntrian->nama_pasien_input_manual ?? 'Pasien');
            $nomor = $trxAntrian->nomor_antrian;
            
            Notification::send(User::all(), new RealtimeNotification(
                title: 'Panggilan Administrasi',
                message: "Administrasi memanggil nomor antrian {$nomor} ({$pasienName}).",
                type: 'primary',
                icon: 'ri-megaphone-line',
                url: route('antrian.index')
            ));
        }
    }
}
