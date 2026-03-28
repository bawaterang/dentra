<!DOCTYPE html>
<html>
<head>
    <title>Hasil Screening - {{ $pendaftaran->nomor_kunjungan }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #405189; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #405189; font-size: 16pt; }
        .header p { margin: 3px 0; color: #666; font-size: 9pt; }
        .patient-info { margin-bottom: 15px; padding: 10px; background: #f3f6f9; border-radius: 5px; }
        .patient-info table { width: 100%; }
        .patient-info td { padding: 3px 8px; font-size: 9pt; }
        .patient-info td.label { width: 30%; color: #666; }
        .patient-info td.value { font-weight: bold; }
        table.screening { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.screening th, table.screening td { border: 1px solid #ddd; padding: 8px; font-size: 9pt; }
        table.screening th { background-color: #f3f6f9; color: #405189; font-weight: bold; }
        .ya { color: #dc3545; font-weight: bold; }
        .tidak { color: #198754; }
        .footer { margin-top: 20px; text-align: center; font-size: 8pt; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI Dental Clinic</h1>
        <p>Hasil Screening Pasien</p>
    </div>

    <div class="patient-info">
        <table>
            <tr><td class="label">No Kunjungan</td><td class="value">{{ $pendaftaran->nomor_kunjungan }}</td><td class="label">Tanggal</td><td class="value">{{ $pendaftaran->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr><td class="label">Nama Pasien</td><td class="value">{{ $pendaftaran->pasien->nama_pasien ?? '-' }}</td><td class="label">No RM</td><td class="value">{{ $pendaftaran->pasien->no_rm ?? '-' }}</td></tr>
            <tr><td class="label">Poli</td><td class="value">{{ $pendaftaran->poli->nama_poli ?? '-' }}</td><td class="label">Dokter</td><td class="value">{{ $pendaftaran->dokter->nama_dokter ?? '-' }}</td></tr>
        </table>
    </div>

    <table class="screening">
        <thead><tr><th width="5%">No</th><th>Pertanyaan</th><th width="10%">Jawaban</th><th width="25%">Keterangan</th></tr></thead>
        <tbody>
            @foreach($screenings as $index => $scr)
            <tr>
                <td style="text-align:center">{{ $index + 1 }}</td>
                <td>{{ $scr->survei->pertanyaan ?? '-' }}</td>
                <td style="text-align:center" class="{{ $scr->jawaban }}">{{ ucfirst($scr->jawaban) }}</td>
                <td>{{ $scr->keterangan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>SIGI Dental EMR © {{ date('Y') }} — Dicetak pada {{ date('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
