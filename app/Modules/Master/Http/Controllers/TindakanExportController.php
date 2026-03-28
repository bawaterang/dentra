<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstTindakan;
use App\Modules\Master\Exports\TindakanExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class TindakanExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstTindakan::withTrashed();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $dataList = $query->get();
        $pdf = Pdf::loadView('modules.Master.tindakan-pdf', compact('dataList'))->setPaper('a4', 'landscape');
        return $pdf->stream('Data_Tindakan_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new TindakanExport($status), 'Data_Tindakan_' . date('Ymd_His') . '.xlsx');
    }
}
