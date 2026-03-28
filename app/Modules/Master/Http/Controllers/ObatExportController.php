<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstObat;
use App\Modules\Master\Exports\ObatExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ObatExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstObat::withTrashed();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $dataList = $query->get();
        $pdf = Pdf::loadView('modules.Master.obat-pdf', compact('dataList'))->setPaper('a4', 'landscape');
        return $pdf->stream('Data_Obat_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new ObatExport($status), 'Data_Obat_' . date('Ymd_His') . '.xlsx');
    }
}
