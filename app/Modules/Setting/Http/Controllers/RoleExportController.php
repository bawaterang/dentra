<?php

namespace App\Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstRoleUser;
use App\Modules\Setting\Exports\RoleExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class RoleExportController extends Controller
{
    public function print(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = MstRoleUser::withCount('users');
        
        if ($status === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($status === 'Tidak Aktif') {
            $query->where('is_active', false);
        }
        
        $roleList = $query->get();
        
        $pdf = Pdf::loadView('modules.Setting.role-pdf', compact('roleList'))
            ->setPaper('a4', 'landscape');
            
        return $pdf->stream('Data_Role_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $status = $request->query('status', 'all');
        return Excel::download(new RoleExport($status), 'Data_Role_' . date('Ymd_His') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
