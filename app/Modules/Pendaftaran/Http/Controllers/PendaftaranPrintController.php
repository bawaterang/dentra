<?php

namespace App\Modules\Pendaftaran\Http\Controllers;

use App\Models\TrxPendaftaran;
use Barryvdh\DomPDF\Facade\Pdf;

class PendaftaranPrintController
{
    public function print($id)
    {
        $pendaftaran = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'asuransi'])->findOrFail($id);
        $pdf = Pdf::loadView('modules.Pendaftaran.bukti-pendaftaran-pdf', compact('pendaftaran'));
        $pdf->setPaper('a5', 'portrait');
        return $pdf->stream('bukti-pendaftaran-' . $pendaftaran->nomor_kunjungan . '.pdf');
    }
}
