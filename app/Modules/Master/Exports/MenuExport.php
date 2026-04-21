<?php
namespace App\Modules\Master\Exports;
use App\Models\MstMenu;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Traits\HasExportHeader;

class MenuExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    use HasExportHeader;
    protected $status;
    public function __construct($status = 'all') { $this->status = $status; }
    public function collection() { $q = MstMenu::query(); if ($this->status === 'Aktif') { $q->where('is_active', true); } elseif ($this->status === 'Tidak Aktif') { $q->where('is_active', false); } return $q->orderBy('order_no')->get(); }
    public function map($item): array { return [$item->menu_name, $item->menu_link ?? '-', $item->menu_icon ?? '-', $item->parent_id ?? '-', $item->order_no, $item->is_active ? 'Aktif' : 'Tidak Aktif']; }
    public function headings(): array { return ['Nama Menu', 'Link', 'Icon', 'Parent ID', 'Urutan', 'Status']; }
}
