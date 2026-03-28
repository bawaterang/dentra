<?php

namespace App\Modules\Master\Http\Controllers;

use App\Models\MstSurvei;
use App\Modules\Master\Exports\SurveiExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SurveiExportController
{
    public function print(Request $request)
    {
        $status = $request->get('status', 'all');
        $query = MstSurvei::query();
        if ($status !== 'all') { $query->where('status', $status); }
        $dataList = $query->orderBy('id')->get();
        $pdf = Pdf::loadView('modules.Master.survei-pdf', compact('dataList', 'status'));
        return $pdf->stream('data-survei.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->get('status', 'all');
        return Excel::download(new SurveiExport($status), 'data-survei.xlsx');
    }
}
