<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kunjungan - SIGI Dental EMR</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; margin: 0; padding: 10px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #405189; padding-bottom: 8px; }
        .header h1 { margin: 0; color: #405189; font-size: 16pt; }
        .header p { margin: 3px 0 0; color: #666; font-size: 9pt; }
        .period { background: #f3f6f9; padding: 8px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .period strong { color: #405189; }
        .patient-card { margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; page-break-inside: avoid; }
        .patient-header { background: #405189; color: white; padding: 8px 12px; font-weight: bold; font-size: 10pt; }
        .patient-info { padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .patient-info table { width: 100%; border: none; }
        .patient-info td { padding: 2px 0; border: none !important; font-size: 8pt; }
        .patient-info .label { width: 80px; color: #64748b; font-weight: bold; }
        
        .section-title { font-size: 8pt; font-weight: bold; color: #405189; text-transform: uppercase; margin: 10px 12px 5px; border-left: 3px solid #405189; padding-left: 8px; }
        .detail-grid { padding: 0 12px 10px; }
        
        .vital-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .vital-table td { border: 1px solid #e2e8f0; padding: 4px 8px; font-size: 8pt; }
        .vital-label { background: #f1f5f9; font-weight: bold; width: 25%; }
        
        .soap-box { margin: 0 12px 10px; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; }
        .soap-item { margin-bottom: 4px; font-size: 8pt; }
        .soap-label { font-weight: bold; color: #405189; display: inline-block; width: 20px; }
        
        .list-table { width: 100%; border-collapse: collapse; margin: 0 12px 10px; width: calc(100% - 24px); }
        .list-table th { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 4px 8px; text-align: left; font-size: 7.5pt; color: #405189; }
        .list-table td { border: 1px solid #e2e8f0; padding: 4px 8px; font-size: 8pt; }
        
        .ohis-box { margin: 0 12px 12px; padding: 8px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 4px; }
        .ohis-grid { display: table; width: 100%; }
        .ohis-col { display: table-cell; width: 25%; text-align: center; }
        .ohis-label { font-size: 7pt; color: #7c3aed; font-weight: bold; }
        .ohis-val { font-size: 10pt; font-weight: bold; color: #5b21b6; }
        
        .footer { margin-top: 20px; text-align: right; font-size: 8pt; color: #888; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 7pt; font-weight: bold; }
        .badge-indigo { background: #e0e7ff; color: #4338ca; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI DENTAL EMR</h1>
        <p>Laporan Kunjungan Pasien dan Riwayat Pemeriksaan</p>
    </div>

    <div class="period">
        <strong>Periode:</strong> {{ $periode }}
    </div>

    @foreach($dataList as $index => $item)
        @php
            $details = $getClinicalDetails($item->nomor_kunjungan);
        @endphp

        <div class="patient-card">
            <div class="patient-header">
                {{ $index + 1 }}. {{ $item->pasien ? $item->pasien->nama_pasien : '-' }}
            </div>
            <div class="patient-info">
                <table>
                    <tr>
                        <td class="label">No. RM</td>
                        <td>: <strong>{{ $item->pasien ? $item->pasien->no_rm : '-' }}</strong></td>
                        <td class="label">No. Kunjungan</td>
                        <td>: <strong>{{ $item->nomor_kunjungan }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal</td>
                        <td>: <strong>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</strong></td>
                        <td class="label">Dokter</td>
                        <td>: <strong>{{ $item->dokter ? $item->dokter->nama_dokter : '-' }}</strong></td>
                    </tr>
                </table>
            </div>

            <div class="section-title">Pemeriksaan Awal</div>
            <div class="detail-grid">
                <table class="vital-table">
                    <tr>
                        <td class="vital-label">Kesadaran</td><td>{{ $details['pemeriksaan_awal']['kesadaran'] ?: '-' }}</td>
                        <td class="vital-label">TD (mmHg)</td><td>{{ $details['pemeriksaan_awal']['td'] ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="vital-label">Nadi (x/mnt)</td><td>{{ $details['pemeriksaan_awal']['nadi'] ?: '-' }}</td>
                        <td class="vital-label">Suhu (&deg;C)</td><td>{{ $details['pemeriksaan_awal']['suhu'] ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="vital-label">BB (kg)</td><td>{{ $details['pemeriksaan_awal']['bb'] ?: '-' }}</td>
                        <td class="vital-label">TB (cm)</td><td>{{ $details['pemeriksaan_awal']['tb'] ?: '-' }}</td>
                    </tr>
                </table>
                <div style="font-size:8pt; margin-top:5px;">
                    <strong>Alergi:</strong> <span style="color:red;">{{ $details['pemeriksaan_awal']['alergi'] ?: '-' }}</span> | 
                    <strong>Riwayat Penyakit:</strong> {{ $details['pemeriksaan_awal']['riwayat'] ?: '-' }}
                </div>
            </div>

            <div class="section-title">Clinical Notes (SOPA)</div>
            <div class="soap-box">
                <div class="soap-item"><span class="soap-label">S:</span> {{ $details['soap']->subjective ?? '-' }}</div>
                <div class="soap-item"><span class="soap-label">O:</span> {{ $details['soap']->objective ?? '-' }}</div>
                <div class="soap-item"><span class="soap-label">A:</span> {{ $details['soap']->assessment ?? '-' }}</div>
                <div class="soap-item"><span class="soap-label">P:</span> {{ $details['soap']->planning ?? '-' }}</div>
            </div>

            <div class="section-title">Diagnosis & Resep</div>
            <table class="list-table">
                <thead>
                    <tr>
                        <th width="15%">Kode</th>
                        <th width="50%">Diagnosis</th>
                        <th width="20%">Jenis</th>
                        <th width="15%">Kasus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($details['diagnoses'] as $diag)
                    <tr>
                        <td><strong>{{ $diag->kode_diagnosa }}</strong></td>
                        <td>{{ $diag->nama_diagnosa }}</td>
                        <td><span class="badge badge-indigo">{{ $diag->jenis_icd }}</span></td>
                        <td>{{ $diag->kasus_icd }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center">Tidak ada diagnosis</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="list-table">
                <thead>
                    <tr>
                        <th width="60%">Nama Obat</th>
                        <th width="15%">Dosis</th>
                        <th width="25%">Aturan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($details['obat'] as $o)
                    <tr>
                        <td><strong>{{ $o->nama_obat }}</strong></td>
                        <td>{{ $o->dosis }}</td>
                        <td>{{ $o->aturan }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center">Tidak ada resep</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if($details['ohis'])
            <div class="section-title">Nilai OHI-S</div>
            <div class="ohis-box">
                <div class="ohis-grid">
                    <div class="ohis-col"><div class="ohis-label">DI</div><div class="ohis-val">{{ $details['ohis']->di_total }}</div></div>
                    <div class="ohis-col"><div class="ohis-label">CI</div><div class="ohis-val">{{ $details['ohis']->ci_total }}</div></div>
                    <div class="ohis-col"><div class="ohis-label">OHI-S</div><div class="ohis-val">{{ $details['ohis']->ohis_total }}</div></div>
                    <div class="ohis-col"><div class="ohis-label">KATEGORI</div><div class="ohis-val" style="color:{{ $details['ohis']->kategori == 'Baik' ? '#059669' : ($details['ohis']->kategori == 'Sedang' ? '#d97706' : '#dc2626') }}">{{ strtoupper($details['ohis']->kategori) }}</div></div>
                </div>
            </div>
            @endif

            @if(count($details['odontogram_visit']) > 0)
            <div class="section-title">Gigi Diperiksa</div>
            <div style="margin: 0 12px 12px; padding: 8px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 4px;">
                <table width="100%" style="font-size: 8pt; border-collapse: collapse;">
                    @foreach($details['odontogram_visit']->chunk(2) as $row)
                    <tr>
                        @foreach($row as $gv)
                        <td width="50%" style="padding: 2px 0;">
                            <span style="display:inline-block; width:8px; height:8px; background:{{ $gv->warna ?: '#ccc' }}; border:1px solid #0002; vertical-align:middle; margin-right:4px;"></span>
                            <strong>Gigi {{ $gv->nomor_gigi }} ({{ $gv->bagian }})</strong>: {{ $gv->nama_kategori ?: '-' }}
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </table>
            </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>Dicetak pada: {{ date('d F Y H:i') }} | Copyright &copy; {{ date('Y') }} SIGI Dental EMR</p>
    </div>
</body>
</html>
