<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstAsuransi;
use App\Modules\Master\Exports\AsuransiExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class AsuransiExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstAsuransi::withTrashed();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $dataList = $query->get();
        $pdf = Pdf::loadView('modules.Master.asuransi-pdf', compact('dataList'))->setPaper('a4', 'landscape');
        return $pdf->stream('Data_Asuransi_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new AsuransiExport($status), 'Data_Asuransi_' . date('Ymd_His') . '.xlsx');
    }
}
