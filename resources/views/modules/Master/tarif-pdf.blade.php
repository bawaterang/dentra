<!DOCTYPE html>
<html>
<head>
    <title>Data Tarif - SIGI Dental EMR</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11pt; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #405189; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #405189; font-size: 22pt; }
        .header p { margin: 5px 0 0; color: #666; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f3f6f9; color: #405189; font-weight: bold; }
        tr:nth-child(even) { background-color: #fafafa; }
        .footer { margin-top: 30px; text-align: right; font-size: 9pt; color: #888; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
        .status-aktif { background-color: #def2d0; color: #3c763d; }
        .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI DENTAL EMR</h1>
        <p>Laporan Data Tarif</p>
        <p>Dicetak pada: {{ date('d F Y H:i') }}</p>
    </div>
    <table>
        <thead><tr><th width="5%">No</th><th>Kode Tindakan</th><th>Kode Asuransi</th><th>Tarif</th><th>Jasmed</th><th>BHP</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->kode_tindakan }}</td>
                <td>{{ $item->kode_asuransi }}</td>
                <td>Rp {{ number_format($item->tarif, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->jasmed, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->bhp, 0, ',', '.') }}</td>
                <td><span class="status-badge {{ strtolower($item->status) == 'aktif' ? 'status-aktif' : 'status-tidak-aktif' }}">{{ $item->status }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer"><p>Copyright &copy; {{ date('Y') }} SIGI Dental EMR</p></div>
</body>
</html>
