<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kritik & Saran</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .clinic-name { font-size: 16px; font-weight: bold; color: #2c3e50; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .periode { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f8fafc; color: #2c3e50; padding: 8px 5px; text-align: left; border: 1px solid #cbd5e1; font-weight: bold; text-transform: uppercase; font-size: 9px; }
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
        <div class="report-title">LAPORAN KRITIK & SARAN</div>
        <div class="periode">Periode: {{ $periode }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="15%">PENGIRIM</th>
                <th width="15%">KONTAK</th>
                <th width="35%">PESAN</th>
                <th width="30%">STATUS & JAWABAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="font-bold">{{ $item->nama ?: 'Anonim' }}</div>
                    <div class="text-xs text-gray mt-1">{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</div>
                </td>
                <td>
                    <div class="mb-2">Email: {{ $item->email ?: '-' }}</div>
                    <div>HP: {{ $item->nomor_hp ?: '-' }}</div>
                </td>
                <td>
                    <div style="font-style: italic;">"{{ $item->pesan }}"</div>
                    <div class="text-xs text-gray mt-1">
                        Platform: {{ $item->platform ?: '-' }} | IP: {{ $item->ip_address ?: '-' }}
                    </div>
                </td>
                <td>
                    @if($item->jawaban)
                        <div class="font-bold mb-2">Terjawab ({{ \Carbon\Carbon::parse($item->waktu_jawab)->format('d/m/Y H:i') }})</div>
                        <div>{{ $item->jawaban }}</div>
                    @else
                        <div class="text-gray italic">Menunggu Jawaban</div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="padding: 20px;">Tidak ada data kritik/saran pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i') }} oleh {{ auth()->user() ? auth()->user()->username : 'System' }}
    </div>
</body>
</html>
