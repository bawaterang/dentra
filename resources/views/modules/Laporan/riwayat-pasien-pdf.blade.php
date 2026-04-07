<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Kunjungan - {{ $pasien->nama_pasien }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 8.5pt; color: #333; margin: 0; padding: 10px; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #405189; padding-bottom: 8px; margin-bottom: 15px; }
        .header h1 { margin: 0; color: #405189; font-size: 15pt; }
        .header p { margin: 2px 0 0; color: #666; font-size: 8pt; }
        
        .pasien-box { background: #f3f6f9; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        .pasien-box table { width: 100%; border: none; }
        .pasien-box td { border: none !important; padding: 2px 0; }
        .label { width: 90px; color: #666; font-weight: bold; }
        
        .history-item { margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; page-break-inside: avoid; }
        .history-date { background: #405189; color: white; padding: 6px 12px; font-weight: bold; font-size: 9pt; display: flex; justify-content: space-between; }
        
        .content-section { padding: 8px 12px; }
        .section-title { font-size: 8pt; font-weight: bold; color: #405189; text-transform: uppercase; margin-bottom: 5px; border-left: 3px solid #405189; padding-left: 8px; }
        
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grid td { border: 1px solid #e2e8f0; padding: 4px 8px; font-size: 8pt; vertical-align: top; }
        .grid th { background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; text-align: left; font-size: 7.5pt; color: #405189; font-weight: bold; }
        .bg-gray { background: #f8fafc; font-weight: bold; width: 100px; }
        
        .soap-box { margin-bottom: 10px; padding: 6px 10px; background: #fff; border: 1px solid #eee; border-radius: 4px; }
        .soap-line { margin-bottom: 2px; }
        .soap-label { font-weight: bold; color: #405189; width: 15px; display: inline-block; }
        
        .odontogram-section { margin-top: 30px; page-break-inside: avoid; }
        .odontogram-title { text-align: center; font-weight: bold; font-size: 10pt; color: #405189; margin-bottom: 15px; text-transform: uppercase; }
        
        .tooth-container { text-align: center; margin-bottom: 20px; }
        .tooth-row { display: table; width: 100%; border-spacing: 5px; margin-bottom: 10px; }
        .tooth-group { display: table-cell; text-align: center; }
        .tooth-unit { display: inline-block; vertical-align: top; margin: 0 2px; }
        .tooth-num { font-size: 7pt; font-weight: bold; color: #94a3b8; display: block; margin-bottom: 2px; }
        .tooth-svg { width: 22px; height: 22px; }
        
        .ohis-summary { margin-top: 15px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; padding: 10px; }
        .ohis-grid { overflow: hidden; }
        .ohis-item { float: left; width: 25%; text-align: center; }
        .ohis-item-label { font-size: 7pt; color: #7c3aed; font-weight: bold; }
        .ohis-item-val { font-size: 11pt; font-weight: bold; color: #5b21b6; }
        
        .footer { margin-top: 30px; text-align: right; font-size: 8pt; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGI DENTAL EMR</h1>
        <p>Ringkasan Riwayat Kunjungan & Rekam Medis Pasien</p>
    </div>

    <div class="pasien-box">
        <table>
            <tr>
                <td class="label">Nama Pasien</td>
                <td>: <strong>{{ $pasien->nama_pasien }}</strong></td>
                <td class="label">No. Rekam Medis</td>
                <td>: <strong>{{ $pasien->no_rm }}</strong></td>
            </tr>
            <tr>
                <td class="label">Jenis Kelamin</td>
                <td>: {{ $pasien->jenis_kelamin }}</td>
                <td class="label">Tgl. Lahir / Usia</td>
                <td>: {{ $pasien->tanggal_lahir ? date('d/m/Y', strtotime($pasien->tanggal_lahir)) : '-' }} 
                      ({{ $pasien->tanggal_lahir ? floor((time() - strtotime($pasien->tanggal_lahir)) / 31556926) : '-' }} thn)</td>
            </tr>
        </table>
    </div>

    @foreach($historyData as $idx => $data)
        <div class="history-item">
            <div class="history-date">
                <span>Kunjungan #{{ count($historyData) - $idx }} - {{ date('d F Y', strtotime($data['pendaftaran']->created_at)) }}</span>
                <span>{{ $data['pendaftaran']->nomor_kunjungan }}</span>
            </div>
            
            <div class="content-section">
                <table class="grid">
                    <tr>
                        <td class="bg-gray">Dokter</td>
                        <td width="35%">{{ $data['pendaftaran']->dokter ? $data['pendaftaran']->dokter->nama_dokter : '-' }}</td>
                        <td class="bg-gray">Pemeriksaan Awal</td>
                        <td>
                            <span style="color:#666; font-size:7pt;">TD:</span> {{ $data['clinical']['pemeriksaan_awal']['td'] ?: '-' }} |
                            <span style="color:#666; font-size:7pt;">N:</span> {{ $data['clinical']['pemeriksaan_awal']['nadi'] ?: '-' }} |
                            <span style="color:#666; font-size:7pt;">S:</span> {{ $data['clinical']['pemeriksaan_awal']['suhu'] ?: '-' }} |
                            <span style="color:#666; font-size:7pt;">BB:</span> {{ $data['clinical']['pemeriksaan_awal']['bb'] ?: '-' }}
                        </td>
                    </tr>
                </table>

                <div class="section-title">Clinical Notes (SOPA)</div>
                <div class="soap-box">
                    <div class="soap-line"><span class="soap-label">S:</span> {{ $data['clinical']['soap']->subjective ?? '-' }}</div>
                    <div class="soap-line"><span class="soap-label">O:</span> {{ $data['clinical']['soap']->objective ?? '-' }}</div>
                    <div class="soap-line"><span class="soap-label">A:</span> {{ $data['clinical']['soap']->assessment ?? '-' }}</div>
                    <div class="soap-line"><span class="soap-label">P:</span> {{ $data['clinical']['soap']->planning ?? '-' }}</div>
                </div>

                <div class="section-title">Diagnosis & Tindakan</div>
                <table class="grid">
                    <thead>
                        <tr>
                            <th width="50%">Diagnosis (ICD-10)</th>
                            <th width="50%">Obat / Resep</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="diagnosis-cell">
                                @forelse($data['clinical']['diagnoses'] as $diag)
                                    <div style="margin-bottom:2px;">• <strong>{{ $diag->kode_diagnosa }}</strong> - {{ $diag->nama_diagnosa }}</div>
                                @empty
                                    -
                                @endforelse
                                
                                <div style="margin-top:8px; border-top:1px dashed #eee; padding-top:5px;">
                                    <div style="font-size:7pt; color:#405189; font-weight:bold; margin-bottom:3px;">GIGI DIPERIKSA:</div>
                                    @forelse($data['clinical']['odontogram_visit'] as $gv)
                                        <div style="font-size:7.5pt; margin-bottom:1px;">• Gigi <strong>{{ $gv->nomor_gigi }}</strong> ({{ $gv->bagian }}): {{ $gv->nama_kategori ?: 'Pemeriksaan' }}</div>
                                    @empty
                                        <span style="font-size:7pt; color:#999;">Tidak ada data gigi spesifik</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @forelse($data['clinical']['obat'] as $o)
                                    <div style="margin-bottom:2px;">• <strong>{{ $o->nama_obat }}</strong> ({{ $o->dosis }})</div>
                                @empty
                                    -
                                @endforelse
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(($idx + 1) % 3 == 0 && !$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="odontogram-section">
        <div class="odontogram-title">Status Odontogram Terkini</div>
        <div class="tooth-container">
            @php
                $rows = [
                    'AdultTop' => [[18,17,16,15,14,13,12,11], [21,22,23,24,25,26,27,28]],
                    'ChildTop' => [[55,54,53,52,51], [61,62,63,64,65]],
                    'ChildBot' => [[85,84,83,82,81], [71,72,73,74,75]],
                    'AdultBot' => [[48,47,46,45,44,43,42,41], [31,32,33,34,35,36,37,38]]
                ];
            @endphp

            @foreach($rows as $key => $halves)
                <div class="tooth-row" style="{{ str_contains($key, 'Child') ? 'opacity:0.8;' : '' }}">
                    @foreach($halves as $halfIdx => $teeth)
                        <div class="tooth-group">
                            @foreach($teeth as $t)
                                <div class="tooth-unit">
                                    @if(!str_contains($key, 'Bot')) <span class="tooth-num">{{ $t }}</span> @endif
                                    <svg viewBox="0 0 40 40" class="tooth-svg">
                                        <path d="M0,0 L40,0 L30,10 L10,10 Z" fill="{{ $odontogramState[$t.'-T']['color'] ?? 'white' }}" stroke="#ccc" stroke-width="1"></path>
                                        <path d="M40,0 L40,40 L30,30 L30,10 Z" fill="{{ $odontogramState[$t.'-R']['color'] ?? 'white' }}" stroke="#ccc" stroke-width="1"></path>
                                        <path d="M40,40 L0,40 L10,30 L30,30 Z" fill="{{ $odontogramState[$t.'-B']['color'] ?? 'white' }}" stroke="#ccc" stroke-width="1"></path>
                                        <path d="M0,0 L10,10 L10,30 L0,40 Z" fill="{{ $odontogramState[$t.'-L']['color'] ?? 'white' }}" stroke="#ccc" stroke-width="1"></path>
                                        <path d="M10,10 L30,10 L30,30 L10,30 Z" fill="{{ $odontogramState[$t.'-C']['color'] ?? 'white' }}" stroke="#ccc" stroke-width="1"></path>
                                    </svg>
                                    @if(str_contains($key, 'Bot')) <span class="tooth-num">{{ $t }}</span> @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        
        @php
            // Get latest OHI-S for overall summary
            $latestEntry = $historyData[0] ?? null;
            $ohis = $latestEntry['clinical']['ohis'] ?? null;
        @endphp
        
        @if($ohis)
        <div class="ohis-summary">
            <div class="section-title">Kesimpulan OHI-S Terakhir</div>
            <div class="ohis-grid">
                <div class="ohis-item"><div class="ohis-item-label">Total DI</div><div class="ohis-item-val">{{ $ohis->di_total }}</div></div>
                <div class="ohis-item"><div class="ohis-item-label">Total CI</div><div class="ohis-item-val">{{ $ohis->ci_total }}</div></div>
                <div class="ohis-item"><div class="ohis-item-label">Skor OHI-S</div><div class="ohis-item-val">{{ $ohis->ohis_total }}</div></div>
                <div class="ohis-item">
                    <div class="ohis-item-label">Kategori</div>
                    <div class="ohis-item-val" style="color:{{ $ohis->kategori == 'Baik' ? '#059669' : ($ohis->kategori == 'Sedang' ? '#d97706' : '#dc2626') }}">
                        {{ strtoupper($ohis->kategori) }}
                    </div>
                </div>
            </div>
            <div style="clear:both;"></div>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ date('d F Y H:i') }} | Rekam Medis Elektronik - SIGI Dental</p>
    </div>
</body>
</html>
