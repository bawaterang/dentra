<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstPoli;
use App\Modules\Master\Exports\PoliExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PoliExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstPoli::query();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $dataList = $query->get();
        $pdf = Pdf::loadView('modules.Master.poli-pdf', compact('dataList'))->setPaper('a4', 'potrait');

        return $pdf->stream('Data_Poli_'.date('Ymd_His').'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');

        return Excel::download(new PoliExport($status), 'Data_Poli_'.date('Ymd_His').'.xlsx');
    }
}
