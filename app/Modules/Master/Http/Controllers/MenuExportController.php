<?php

namespace App\Modules\Master\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstMenu;
use App\Modules\Master\Exports\MenuExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class MenuExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstMenu::query();
        if ($status === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($status === 'Tidak Aktif') {
            $query->where('is_active', false);
        }
        $dataList = $query->orderBy('order_no')->get();
        $pdf = Pdf::loadView('modules.Master.menu-pdf', compact('dataList'))->setPaper('a4', 'landscape');
        return $pdf->stream('Data_Menu_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new MenuExport($status), 'Data_Menu_' . date('Ymd_His') . '.xlsx');
    }
}
