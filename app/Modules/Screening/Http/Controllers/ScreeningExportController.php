<?php

namespace App\Modules\Screening\Http\Controllers;

use App\Models\TrxPendaftaran;
use App\Models\TrxScreening;
use App\Modules\Screening\Exports\ScreeningExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ScreeningExportController
{
    public function print($pendaftaranId)
    {
        $pendaftaran = TrxPendaftaran::with(['pasien', 'poli', 'dokter'])->findOrFail($pendaftaranId);
        $screenings = TrxScreening::with('survei')
            ->where('pendaftaran_id', $pendaftaranId)
            ->get();

        $pdf = Pdf::loadView('modules.Screening.screening-pdf', compact('pendaftaran', 'screenings'));
        return $pdf->stream('screening-' . $pendaftaran->nomor_kunjungan . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        return Excel::download(new ScreeningExport($date), 'screening-' . $date . '.xlsx');
    }
}
