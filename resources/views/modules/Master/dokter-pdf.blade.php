<!DOCTYPE html>
<html>
<head>
    <title>Data Dokter - SIGI Dental EMR</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #405189;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #405189;
            font-size: 22pt;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f3f6f9;
            color: #405189;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9pt;
            color: #888;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-aktif {
            background-color: #def2d0;
            color: #3c763d;
        }
        .status-tidak-aktif {
            background-color: #f2dede;
            color: #a94442;
        }
        .status-cuti {
            background-color: #fef5e1;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI DENTAL EMR</h1>
        <p>Laporan Data Dokter</p>
        <p>Dicetak pada: {{ date('d F Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Kode</th>
                <th width="20%">Nama Dokter</th>
                <th width="15%">Spesialisasi</th>
                <th width="12%">Jenis Kelamin</th>
                <th width="13%">No. SIP</th>
                <th width="12%">No. STR</th>
                <th width="13%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dokterList as $index => $dokter)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $dokter->kode_dokter }}</td>
                <td>{{ $dokter->nama_dokter }}</td>
                <td>{{ $dokter->spesialisasi ?? '-' }}</td>
                <td>{{ $dokter->jenis_kelamin }}</td>
                <td>{{ $dokter->no_sip ?? '-' }}</td>
                <td>{{ $dokter->no_str ?? '-' }}</td>
                <td>
                    @php
                        $statusClass = 'status-aktif';
                        if(strtolower($dokter->status) == 'tidak aktif') $statusClass = 'status-tidak-aktif';
                        if(strtolower($dokter->status) == 'cuti') $statusClass = 'status-cuti';
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $dokter->status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Copyright &copy; {{ date('Y') }} SIGI Dental EMR - Sistem Rekam Medis Elektronik Gigi</p>
    </div>
</body>
</html>
