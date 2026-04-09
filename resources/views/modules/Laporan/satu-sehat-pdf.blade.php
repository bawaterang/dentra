<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Satu Sehat</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #10b981; padding-bottom: 10px; }
        .clinic-name { font-size: 16px; font-weight: bold; color: #047857; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .periode { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f8fafc; color: #047857; padding: 8px 5px; text-align: left; border: 1px solid #cbd5e1; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        td { padding: 6px 5px; border: 1px solid #cbd5e1; vertical-align: top; }
        .footer { text-align: right; margin-top: 30px; font-size: 9px; color: #666; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mb-2 { margin-bottom: 5px; }
        .font-bold { font-weight: bold; }
        .text-gray { color: #64748b; }
        .text-xs { font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="clinic-name">SIGI DENTAL EMR</div>
        <div class="report-title">LAPORAN SATU SEHAT</div>
        <div class="periode">Periode: {{ $periode }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="15%">KUNJUNGAN</th>
                <th width="25%">NAMA PASIEN & INFO</th>
                <th width="20%">NIK</th>
                <th width="20%">STATUS BRIDGING</th>
                <th width="15%">TGL KUNJUNGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="font-bold">{{ $item->nomor_kunjungan }}</div>
                </td>
                <td>
                    <div class="font-bold mb-2">{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</div>
                    <div class="text-xs">
                        RM: {{ $item->pasien ? $item->pasien->no_rm : '-' }} | 
                        {{ $item->poli ? $item->poli->nama_poli : '-' }} | 
                        {{ $item->asuransi ? $item->asuransi->nama_asuransi : 'Pribadi' }}
                    </div>
                </td>
                <td>
                    <div class="font-bold">{{ $item->pasien && $item->pasien->nik ? $item->pasien->nik : '-' }}</div>
                </td>
                <td>
                    <div class="font-bold">{{ $item->status_bundle ?: 'Pending' }}</div>
                </td>
                <td>
                    {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px;">Tidak ada data pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i') }} oleh {{ auth()->user() ? auth()->user()->username : 'System' }}
    </div>
</body>
</html>
