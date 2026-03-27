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
        $dokterList = MstDokter::withTrashed()->get();
        
        $pdf = Pdf::loadView('modules.Master.dokter-pdf', compact('dokterList'))
            ->setPaper('a4', 'landscape');
            
        return $pdf->stream('Data_Dokter_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new DokterExport, 'Data_Dokter_' . date('Ymd_His') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
