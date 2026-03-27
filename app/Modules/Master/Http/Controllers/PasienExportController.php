<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstPasien;
use App\Modules\Master\Exports\PasienExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class PasienExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstPasien::withTrashed();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $pasienList = $query->get();
        
        $pdf = Pdf::loadView('modules.Master.pasien-pdf', compact('pasienList'))
            ->setPaper('a4', 'landscape');
            
        return $pdf->stream('Data_Pasien_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new PasienExport($status), 'Data_Pasien_' . date('Ymd_His') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
