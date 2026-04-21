@extends('layouts.pdf-base')

@section('title', 'Riwayat Kunjungan - ' . $pasien->nama_pasien)

@section('styles')
<style>
    .pasien-box { background: #f3f6f9; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
    .pasien-box table { width: 100%; border: none; }
    .pasien-box td { border: none !important; padding: 2px 0; font-size: 8.5pt; }
    .label { width: 100px; color: #666; font-weight: bold; }

    .history-item { margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; page-break-inside: avoid; }
    .history-date { background: #405189; color: white; padding: 6px 12px; font-weight: bold; font-size: 9pt; }
    .content-section { padding: 8px 12px; }

    .section-title { font-size: 8pt; font-weight: bold; color: #405189; text-transform: uppercase; margin-bottom: 5px; border-left: 3px solid #405189; padding-left: 8px; }
    .grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .grid td { border: 1px solid #e2e8f0; padding: 4px 8px; font-size: 8pt; vertical-align: top; }
    .grid th { background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; text-align: left; font-size: 7.5pt; color: #405189; font-weight: bold; }
    .bg-gray { background: #f8fafc; font-weight: bold; width: 100px; }

    .soap-box { margin-bottom: 10px; padding: 6px 10px; background: #fff; border: 1px solid #eee; border-radius: 4px; }
    .soap-line { margin-bottom: 2px; font-size: 8pt; }
    .soap-label { font-weight: bold; color: #405189; width: 15px; display: inline-block; }

    .odontogram-section { margin-top: 30px; page-break-inside: avoid; }
    .odontogram-title { text-align: center; font-weight: bold; font-size: 10pt; color: #405189; margin-bottom: 15px; text-transform: uppercase; }
    .odontogram-layout { width: 100%; margin-top: 15px; border: none; }
    .odontogram-layout td { border: none !important; }
    .odontogram-main { width: 75%; vertical-align: top; }
    .odontogram-legend { width: 25%; vertical-align: top; padding-left: 15px; border-left: 1px dashed #e2e8f0 !important; }

    .legend-item { margin-bottom: 6px; line-height: 1.2; }
    .legend-color { display: inline-block; width: 10px; height: 10px; border: 1px solid #0002; vertical-align: middle; margin-right: 5px; }
    .legend-text { font-size: 7.5pt; color: #475569; vertical-align: middle; }

    .ohis-summary { margin-top: 25px; background: #ffffff; border: 2px solid #333; border-radius: 6px; padding: 15px; }
    .ohis-table { width: 100%; border-collapse: collapse; border: none; }
    .ohis-table td { border: none !important; text-align: center; }
    .ohis-item-label { font-size: 8pt; color: #666; font-weight: bold; text-transform: uppercase; display: block; }
    .ohis-item-val { font-size: 16pt; font-weight: bold; color: #000; display: block; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">RINGKASAN RIWAYAT REKAM MEDIS</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Laporan Lengkap Riwayat Kunjungan dan Odontogram</p>
    </div>

    <div class="pasien-box">
        <table>
            <tr>
                <td class="label">Nama Pasien</td>
                <td>: <strong>{{ $pasien->nama_pasien }}</strong></td>
                <td class="label">No. RM</td>
                <td>: <strong>{{ $pasien->no_rm }}</strong></td>
            </tr>
            <tr>
                <td class="label">Jenis Kelamin</td>
                <td>: {{ $pasien->jenis_kelamin }}</td>
                <td class="label">Tgl. Lahir / Usia</td>
                <td>: {{ $pasien->tanggal_lahir ? date('d/m/Y', strtotime($pasien->tanggal_lahir)) : '-' }}
                    ({{ $pasien->tanggal_lahir ? floor((time() - strtotime($pasien->tanggal_lahir)) / 31556926) : '-' }} thn)
                </td>
            </tr>
        </table>
    </div>

    @foreach($historyData as $idx => $data)
        <div class="history-item">
            <div class="history-date">
                <table width="100%" style="border:none; margin:0;">
                    <tr>
                        <td style="border:none; color:white; font-weight:bold; padding:0;">
                            Kunjungan #{{ count($historyData) - $idx }} - {{ date('d F Y', strtotime($data['pendaftaran']->created_at)) }}
                        </td>
                        <td style="border:none; color:white; text-align:right; font-weight:bold; padding:0;">
                            {{ $data['pendaftaran']->nomor_kunjungan }}
                        </td>
                    </tr>
                </table>
            </div>

            <div class="content-section">
                <table class="grid">
                    <tr>
                        <td class="bg-gray">Dokter</td>
                        <td width="35%">{{ $data['pendaftaran']->dokter ? $data['pendaftaran']->dokter->nama_dokter : '-' }}</td>
                        <td class="bg-gray">Pemeriksaan Awal</td>
                        <td>
                            <span style="color:#666; font-size:7pt; font-weight:bold;">TD:</span> {{ $data['clinical']['pemeriksaan_awal']['td'] ?: '-' }} |
                            <span style="color:#666; font-size:7pt; font-weight:bold;">N:</span> {{ $data['clinical']['pemeriksaan_awal']['nadi'] ?: '-' }} |
                            <span style="color:#666; font-size:7pt; font-weight:bold;">S:</span> {{ $data['clinical']['pemeriksaan_awal']['suhu'] ?: '-' }} |
                            <span style="color:#666; font-size:7pt; font-weight:bold;">BB:</span> {{ $data['clinical']['pemeriksaan_awal']['bb'] ?: '-' }}
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

                <div class="section-title">Diagnosis & Resep</div>
                <table class="grid">
                    <thead>
                        <tr>
                            <th width="50%">Diagnosis (ICD-10)</th>
                            <th width="50%">Obat / Resep</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                @forelse($data['clinical']['diagnoses'] as $diag)
                                    <div style="margin-bottom:2px;">• <strong>{{ $diag->kode_diagnosa }}</strong> - {{ $diag->nama_diagnosa }}</div>
                                @empty
                                    <span style="color:#999; font-style:italic;">Tidak ada diagnosis</span>
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
                                    <span style="color:#999; font-style:italic;">Tidak ada resep</span>
                                @endforelse
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="odontogram-section">
        <div class="odontogram-title">Status Odontogram Terkini</div>
        <table class="odontogram-layout">
            <tr>
                <td class="odontogram-main">
                    @php
                        $rows = [
                            'AdultTop' => [[18, 17, 16, 15, 14, 13, 12, 11], [21, 22, 23, 24, 25, 26, 27, 28]],
                            'ChildTop' => [[55, 54, 53, 52, 51], [61, 62, 63, 64, 65]],
                            'ChildBot' => [[85, 84, 83, 82, 81], [71, 72, 73, 74, 75]],
                            'AdultBot' => [[48, 47, 46, 45, 44, 43, 42, 41], [31, 32, 33, 34, 35, 36, 37, 38]]
                        ];
                    @endphp

                    @foreach($rows as $key => $halves)
                        <table style="width: 100%; margin-bottom: 8px; border-collapse: collapse;">
                            <tr>
                            @foreach($halves as $halfIdx => $teeth)
                                <td style="text-align: center; vertical-align: top; padding: 0 {{ $halfIdx == 0 ? '5px' : '0' }} 0 {{ $halfIdx == 1 ? '5px' : '0' }}; border:none !important;">
                                    <table style="border-collapse: collapse; margin: 0 auto; border:none !important;">
                                        <tr>
                                        @foreach($teeth as $t)
                                            <td style="padding: 0 1px; vertical-align: top; text-align: center; border:none !important;">
                                                @if(!str_contains($key, 'Bot'))
                                                    <div style="font-size: 6pt; font-weight: bold; color: #64748b; text-align: center; margin-bottom: 1px;">{{ $t }}</div>
                                                @endif
                                                <img src="data:image/png;base64,{{ $toothImages[$t] }}" width="20" height="20" style="display: block; margin: 0 auto;">
                                                @if(str_contains($key, 'Bot'))
                                                    <div style="font-size: 6pt; font-weight: bold; color: #64748b; text-align: center; margin-top: 1px;">{{ $t }}</div>
                                                @endif
                                            </td>
                                        @endforeach
                                        </tr>
                                    </table>
                                </td>
                            @endforeach
                            </tr>
                        </table>
                    @endforeach
                </td>
                <td class="odontogram-legend">
                    <div style="font-size: 8pt; font-weight: bold; color: #405189; margin-bottom: 8px; border-bottom: 1px solid #405189;">LEGENDA</div>
                    @foreach($odontogramCategories as $cat)
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: {{ $cat->warna }}"></span>
                            <span class="legend-text">{{ $cat->nama_kategori }}</span>
                        </div>
                    @endforeach
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #ffffff"></span>
                        <span class="legend-text">Normal</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="ohis-summary">
        <div class="section-title">Kesimpulan OHI-S (Kunjungan Terakhir)</div>
        @if($latestOhis)
            <table class="ohis-table">
                <tr>
                    <td>
                        <span class="ohis-item-label">debris index</span>
                        <span class="ohis-item-val">{{ $latestOhis->di_total }}</span>
                    </td>
                    <td>
                        <span class="ohis-item-label">calculus index</span>
                        <span class="ohis-item-val">{{ $latestOhis->ci_total }}</span>
                    </td>
                    <td>
                        <span class="ohis-item-label">skor ohi-s</span>
                        <span class="ohis-item-val">{{ $latestOhis->ohis_total }}</span>
                    </td>
                    <td>
                        <span class="ohis-item-label">kategori</span>
                        <span class="ohis-item-val" style="color:#000;">{{ strtoupper($latestOhis->kategori) }}</span>
                    </td>
                </tr>
            </table>
        @else
            <div style="text-align: center; padding: 10px; color: #666; font-style: italic; font-size: 8pt;">
                Tidak ada data OHI-S di kunjungan terakhir.
            </div>
        @endif
    </div>
@endsection