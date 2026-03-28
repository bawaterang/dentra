<!DOCTYPE html>
<html>
<head>
    <title>Data Survei - SIGI Dental EMR</title>
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
        .status-badge { padding: 3px 10px; border-radius: 4px; font-size: 9pt; font-weight: bold; }
        .status-aktif { background-color: #d1fae5; color: #065f46; }
        .status-tidak-aktif { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI Dental EMR</h1>
        <p>Laporan Data Survei — Filter: {{ $status === 'all' ? 'Semua' : $status }}</p>
        <p>Dicetak pada: {{ date('d F Y H:i') }}</p>
    </div>
    <table>
        <thead><tr><th width="5%">No</th><th>Pertanyaan</th><th>Jenis Survei</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->pertanyaan }}</td>
                <td>{{ ucfirst($item->jenis_survei) }}</td>
                <td><span class="status-badge {{ strtolower($item->status) == 'aktif' ? 'status-aktif' : 'status-tidak-aktif' }}">{{ $item->status }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Total: {{ $dataList->count() }} pertanyaan</div>
</body>
</html>
