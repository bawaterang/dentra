<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pendapatan - SIGI Dental EMR</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; margin: 0; padding: 10px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #405189; padding-bottom: 8px; }
        .header h1 { margin: 0; color: #405189; font-size: 16pt; }
        .header p { margin: 3px 0 0; color: #666; font-size: 9pt; }
        .period { background: #f3f6f9; padding: 8px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .period strong { color: #405189; }
        
        .summary-grid { display: table; width: 100%; margin-bottom: 15px; }
        .summary-col { display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #e2e8f0; }
        .summary-label { font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .summary-val { font-size: 12pt; font-weight: bold; color: #405189; }
        
        .list-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .list-table th { background: #405189; color: white; border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; font-size: 8pt; font-weight: bold; }
        .list-table td { border: 1px solid #e2e8f0; padding: 6px 8px; font-size: 8pt; vertical-align: top; }
        .list-table tr:nth-child(even) { background-color: #f8fafc; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 20px; text-align: right; font-size: 8pt; color: #888; }
        
        .detail-item { font-size: 7.5pt; color: #555; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI DENTAL EMR</h1>
        <p>Laporan Pendapatan dan Pengeluaran BHP</p>
    </div>

    <div class="period">
        <strong>Periode:</strong> {{ $periode }}
    </div>

    <div class="summary-grid">
        <div class="summary-col">
            <div class="summary-label">Total Pendapatan</div>
            <div class="summary-val" style="color: #059669;">Rp {{ number_format($summary['pendapatan'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-col">
            <div class="summary-label">Pengeluaran BHP</div>
            <div class="summary-val" style="color: #d97706;">Rp {{ number_format($summary['pengeluaran'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-col">
            <div class="summary-label">Total Piutang</div>
            <div class="summary-val" style="color: #e11d48;">Rp {{ number_format($summary['piutang'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-col" style="background-color: #e0e7ff;">
            <div class="summary-label">Laba Bersih</div>
            <div class="summary-val" style="color: #4338ca;">Rp {{ number_format($summary['laba_bersih'], 0, ',', '.') }}</div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">No. Faktur</th>
                <th width="20%">Pasien</th>
                <th width="15%" class="text-right">Pendapatan</th>
                <th width="15%" class="text-right">Pengeluaran BHP</th>
                <th width="15%" class="text-right">Piutang</th>
                <th width="15%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataList as $index => $item)
            @php
                $pengeluaran = $getPengeluaranBhp($item->nomor_kunjungan);
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->no_faktur }}</strong><br>
                    <span style="font-size: 7.5pt; color: #666;">Kunj: {{ $item->nomor_kunjungan }}</span>
                </td>
                <td>
                    <strong>{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</strong><br>
                    <span style="font-size: 7.5pt; color: #666;">RM: {{ $item->pasien ? $item->pasien->no_rm : '-' }}</span><br>
                    <span style="font-size: 7.5pt; color: #666;">Tgl: {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</span>
                </td>
                <td class="text-right">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($pengeluaran, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->hutang, 0, ',', '.') }}</td>
                <td class="text-center">{{ $item->status }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">Tidak ada data pendapatan pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ date('d F Y H:i') }} | Copyright &copy; {{ date('Y') }} SIGI Dental EMR</p>
    </div>
</body>
</html>
