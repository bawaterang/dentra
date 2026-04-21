<?php

namespace App\Traits;

use App\Models\MstInstansi;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

trait HasExportHeader
{
    /**
     * Register events for the export.
     * Use this alongside Maatwebsite\Excel\Concerns\WithEvents
     * 
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $instansi = MstInstansi::first();
                $lastColumn = $event->sheet->getHighestColumn();
                
                // 1. Shift current content (headings & data) down by 6 rows
                $event->sheet->insertNewRowBefore(1, 6);
                
                // 2. Clinic Name (Centered, Bold, Large)
                $event->sheet->setCellValue('A1', strtoupper($instansi->nama_instansi ?? 'SIGI DENTAL CLINIC'));
                if ($lastColumn !== 'A') {
                    $event->sheet->mergeCells("A1:{$lastColumn}1");
                }
                $styleTitle = [
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '405189'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ];
                $event->sheet->getStyle('A1')->applyFromArray($styleTitle);
                $event->sheet->getRowDimension(1)->setRowHeight(30);

                // 3. Address (Centered)
                $event->sheet->setCellValue('A2', $instansi->alamat ?? 'Alamat Instansi Belum Diatur');
                if ($lastColumn !== 'A') {
                    $event->sheet->mergeCells("A2:{$lastColumn}2");
                }
                $event->sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 4. Contacts (Centered)
                $contactInfo = "Telp: " . ($instansi->telepon ?? '-') . " | Email: " . ($instansi->email ?? '-');
                if($instansi && $instansi->website) {
                    $contactInfo .= " | Website: " . $instansi->website;
                }
                $event->sheet->setCellValue('A3', $contactInfo);
                if ($lastColumn !== 'A') {
                    $event->sheet->mergeCells("A3:{$lastColumn}3");
                }
                $event->sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 5. Separator Line
                $event->sheet->getStyle("A4:{$lastColumn}4")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
                $event->sheet->getRowDimension(4)->setRowHeight(10);
                
                // 6. Spacing
                $event->sheet->getRowDimension(5)->setRowHeight(15);
                
                // 7. Auto-size columns (optional but nice)
                // foreach (range('A', $lastColumn) as $col) {
                //     $event->sheet->getColumnDimension($col)->setAutoSize(true);
                // }
            },
        ];
    }
}
