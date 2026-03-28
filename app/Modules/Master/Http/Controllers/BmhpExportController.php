<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstBmhp;
use App\Modules\Master\Exports\BmhpExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class BmhpExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstBmhp::withTrashed();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $dataList = $query->get();
        $pdf = Pdf::loadView('modules.Master.bmhp-pdf', compact('dataList'))->setPaper('a4', 'landscape');
        return $pdf->stream('Data_Bmhp_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new BmhpExport($status), 'Data_Bmhp_' . date('Ymd_His') . '.xlsx');
    }
}
