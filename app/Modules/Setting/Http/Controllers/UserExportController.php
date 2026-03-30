<?php

namespace App\Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Setting\Exports\UserExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class UserExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = User::query();
        
        if ($status === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($status === 'Tidak Aktif') {
            $query->where('is_active', false);
        }
        
        $userList = $query->orderBy('full_name')->get();
        
        $pdf = Pdf::loadView('modules.Setting.user-pdf', compact('userList'))
            ->setPaper('a4', 'landscape');
            
        return $pdf->stream('Data_User_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new UserExport($status), 'Data_User_' . date('Ymd_His') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
