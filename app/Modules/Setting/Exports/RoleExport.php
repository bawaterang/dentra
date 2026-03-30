<?php

namespace App\Modules\Setting\Exports;

use App\Models\MstRoleUser;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RoleExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;

    public function __construct($status = 'all')
    {
        $this->status = $status;
    }

    public function collection()
    {
        $query = MstRoleUser::withCount('users');
        
        if ($this->status === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->status === 'Tidak Aktif') {
            $query->where('is_active', false);
        }
        
        return $query->get();
    }

    public function map($role): array
    {
        return [
            $role->nama_role,
            $role->deskripsi ?? '-',
            $role->users_count . ' Users',
            $role->is_active ? 'Aktif' : 'Tidak Aktif',
        ];
    }
    
    public function headings(): array
    {
        return [
            'Nama Role',
            'Deskripsi',
            'Jumlah Pengguna',
            'Status',
        ];
    }
}
