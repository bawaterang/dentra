<!DOCTYPE html>
<html>
<head>
    <title>Bukti Pendaftaran - {{ $pendaftaran->nomor_kunjungan }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #405189; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #405189; font-size: 16pt; }
        .header p { margin: 3px 0; color: #666; font-size: 9pt; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; color: #405189; font-size: 10pt; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 8px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 4px 8px; font-size: 9pt; }
        table.info td.label { width: 40%; color: #666; }
        table.info td.value { font-weight: bold; }
        .no-kunjungan { text-align: center; margin: 15px 0; padding: 15px; background: #f3f6f9; border-radius: 8px; }
        .no-kunjungan h2 { margin: 0; color: #405189; font-size: 20pt; letter-spacing: 2px; }
        .footer { margin-top: 20px; text-align: center; font-size: 8pt; color: #888; border-top: 1px dashed #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI Dental Clinic</h1>
        <p>Bukti Pendaftaran Pasien</p>
    </div>

    <div class="no-kunjungan">
        <p style="font-size:8pt; color:#666; margin:0">Nomor Kunjungan</p>
        <h2>{{ $pendaftaran->nomor_kunjungan }}</h2>
        <p style="font-size:8pt; color:#666; margin:5px 0 0">{{ $pendaftaran->created_at->translatedFormat('l, d F Y - H:i') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Data Pasien</div>
        <table class="info">
            <tr><td class="label">No Rekam Medis</td><td class="value">{{ $pendaftaran->pasien->no_rm ?? '-' }}</td></tr>
            <tr><td class="label">Nama Pasien</td><td class="value">{{ $pendaftaran->pasien->nama_pasien ?? '-' }}</td></tr>
            <tr><td class="label">NIK</td><td class="value">{{ $pendaftaran->pasien->nik ?? '-' }}</td></tr>
            <tr><td class="label">Jenis Kelamin</td><td class="value">{{ $pendaftaran->pasien->jenis_kelamin ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Informasi Kunjungan</div>
        <table class="info">
            <tr><td class="label">Poli</td><td class="value">{{ $pendaftaran->poli->nama_poli ?? '-' }}</td></tr>
            <tr><td class="label">Dokter</td><td class="value">{{ $pendaftaran->dokter->nama_dokter ?? '-' }}</td></tr>
            <tr><td class="label">Asuransi</td><td class="value">{{ $pendaftaran->asuransi?->nama_asuransi ?? 'Umum' }}</td></tr>
            @if($pendaftaran->no_kartu_asuransi)<tr><td class="label">No Kartu</td><td class="value">{{ $pendaftaran->no_kartu_asuransi }}</td></tr>@endif
        </table>
    </div>

    <div class="footer">
        <p>Harap simpan bukti pendaftaran ini. Silakan menuju ruang tunggu.</p>
        <p>SIGI Dental EMR © {{ date('Y') }}</p>
    </div>
</body>
</html>
