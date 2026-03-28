<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstDiagnosis;
use App\Modules\Master\Exports\DiagnosisExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class DiagnosisExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstDiagnosis::withTrashed();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $dataList = $query->get();
        $pdf = Pdf::loadView('modules.Master.diagnosis-pdf', compact('dataList'))->setPaper('a4', 'landscape');
        return $pdf->stream('Data_Diagnosis_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new DiagnosisExport($status), 'Data_Diagnosis_' . date('Ymd_His') . '.xlsx');
    }
}
