<?php

namespace App\Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxInformasi;
use App\Modules\Setting\Exports\InformasiExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InformasiExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = TrxInformasi::query();
        
        $today = Carbon::today()->format('Y-m-d');

        if ($status === 'Aktif') {
            $query->where('date_start', '<=', $today)
                  ->where('date_expired', '>=', $today);
        } elseif ($status === 'Expired') {
            $query->where(function ($q) use ($today) {
                $q->where('date_expired', '<', $today)
                  ->orWhere('date_start', '>', $today);
            });
        }
        
        $informasiList = $query->orderBy('date_start', 'desc')->get();
        
        $pdf = Pdf::loadView('modules.Setting.informasi-pdf', compact('informasiList'))
            ->setPaper('a4', 'landscape');
            
        return $pdf->stream('Data_Informasi_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new InformasiExport($status), 'Data_Informasi_' . date('Ymd_His') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
