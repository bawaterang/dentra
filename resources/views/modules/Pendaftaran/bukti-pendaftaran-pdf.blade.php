@extends('layouts.pdf-base')

@section('title', 'Bukti Pendaftaran - ' . $pendaftaran->nomor_kunjungan)

@section('styles')
<style>
    .section { margin-bottom: 20px; }
    .section-title { font-weight: bold; color: #405189; font-size: 10pt; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px; text-transform: uppercase; }
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 6px 8px; font-size: 9pt; }
    table.info td.label { width: 40%; color: #64748b; font-weight: bold; }
    table.info td.value { font-weight: bold; color: #1e293b; }
    .no-kunjungan-box { text-align: center; background: #f0f4ff; border: 2px dashed #405189; border-radius: 8px; padding: 10px; }
    .no-kunjungan-box p { font-size: 7.5pt; color: #405189; margin: 0; font-weight: bold; text-transform: uppercase; }
    .no-kunjungan-box h2 { margin: 3px 0; color: #405189; font-size: 18pt; letter-spacing: 1px; font-weight: 900; }
    .no-kunjungan-box .timestamp { font-size: 7.5pt; color: #64748b; margin-top: 2px; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189; font-size: 14pt;">BUKTI PENDAFTARAN PASIEN</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Silakan tunjukkan bukti ini kepada petugas administrasi</p>
    </div>

    <table style="width: 100%; border-spacing: 0; margin-bottom: 20px;">
        <tr>
            <td style="width: 45%; vertical-align: middle; padding-right: 15px;">
                <div class="no-kunjungan-box" style="margin: 0 auto; width: 85%;">
                    <p>Nomor Kunjungan</p>
                    <h2>{{ $pendaftaran->nomor_kunjungan }}</h2>
                    <div class="timestamp">{{ $pendaftaran->created_at->translatedFormat('l, d F Y — H:i') }}</div>
                </div>
            </td>
            <td style="width: 55%; vertical-align: top; padding-left: 15px;">
                <div class="section" style="margin-bottom: 0;">
                    <div class="section-title">Identitas Pasien</div>
                    <table class="info">
                        <tr><td class="label" style="width: 45%;">Nomor Rekam Medis</td><td class="value">: {{ $pendaftaran->pasien->no_rm ?? '-' }}</td></tr>
                        <tr><td class="label">Nama Lengkap</td><td class="value">: {{ $pendaftaran->pasien->nama_pasien ?? '-' }}</td></tr>
                        <tr><td class="label">Nomor Induk Kependudukan</td><td class="value">: {{ $pendaftaran->pasien->nik ?? '-' }}</td></tr>
                        <tr><td class="label">Jenis Kelamin</td><td class="value">: {{ $pendaftaran->pasien->jenis_kelamin ?? '-' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Detail Kunjungan</div>
        <table class="info">
            <tr><td class="label" style="width: 25%;">Unit Pelayanan (Poli)</td><td class="value">: {{ $pendaftaran->poli->nama_poli ?? '-' }}</td></tr>
            <tr><td class="label">Dokter Pemeriksa</td><td class="value">: {{ $pendaftaran->dokter->nama_dokter ?? '-' }}</td></tr>
            <tr><td class="label">Metode Pembayaran</td><td class="value">: {{ $pendaftaran->asuransi?->nama_asuransi ?? 'UMUM (PRIBADI)' }}</td></tr>
        </table>
    </div>

    <div style="text-align: center; margin-top: 20px; padding: 10px; background: #fffcf0; border: 1px solid #fde68a; border-radius: 8px;">
        <p style="font-size: 8.5pt; color: #92400e; margin: 0;"><strong>Catatan:</strong> Harap menunggu di ruang tunggu sesuai dengan nomor antrian Anda. <br> Terima kasih atas kepercayaan Anda kepada layanan kami.</p>
    </div>
@endsection
