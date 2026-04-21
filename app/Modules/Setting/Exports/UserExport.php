<?php

namespace App\Modules\Setting\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Traits\HasExportHeader;

class UserExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    use HasExportHeader;
    protected $status;

    public function __construct($status = 'all')
    {
        $this->status = $status;
    }

    public function collection()
    {
        $query = User::query();
        
        if ($this->status === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->status === 'Tidak Aktif') {
            $query->where('is_active', false);
        }
        
        return $query->get();
    }

    public function map($user): array
    {
        return [
            $user->user_code,
            $user->username,
            $user->full_name,
            $user->email,
            $user->phone ?? '-',
            $user->color ?? '-',
            $user->is_active ? 'Aktif' : 'Tidak Aktif',
        ];
    }
    
    public function headings(): array
    {
        return [
            'Kode User',
            'Username',
            'Nama Lengkap',
            'Email',
            'No. Telepon',
            'Warna',
            'Status',
        ];
    }
}
