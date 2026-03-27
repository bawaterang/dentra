<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstDokter;
use App\Modules\Master\Exports\DokterExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class DokterExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstDokter::withTrashed();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $dokterList = $query->get();
        
        $pdf = Pdf::loadView('modules.Master.dokter-pdf', compact('dokterList'))
            ->setPaper('a4', 'landscape');
            
        return $pdf->stream('Data_Dokter_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new DokterExport($status), 'Data_Dokter_' . date('Ymd_His') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
