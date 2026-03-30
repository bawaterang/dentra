<!DOCTYPE html>
<html>
<head>
    <title>Data User - SIGI Dental EMR</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #405189; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #405189; font-size: 20pt; }
        .header p { margin: 5px 0 0; color: #666; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f6f9; color: #405189; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
        tr:nth-child(even) { background-color: #fafafa; }
        .footer { margin-top: 30px; text-align: right; font-size: 8pt; color: #888; }
        .status-badge { padding: 3px 6px; border-radius: 3px; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
        .status-aktif { background-color: #def2d0; color: #3c763d; }
        .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI DENTAL EMR</h1>
        <p>Laporan Data Pengguna (User)</p>
        <p>Dicetak pada: {{ date('d F Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode User</th>
                <th width="20%">Username</th>
                <th width="25%">Nama Lengkap</th>
                <th width="20%">Email</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($userList as $index => $user)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $user->user_code }}</td>
                <td>{{ $user->username }}</td>
                <td>{{ $user->full_name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="status-badge {{ $user->is_active ? 'status-aktif' : 'status-tidak-aktif' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Copyright &copy; {{ date('Y') }} SIGI Dental EMR</p>
    </div>
</body>
</html>
