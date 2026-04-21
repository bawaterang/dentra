@extends('layouts.pdf-base')

@section('title', 'Hasil Screening - ' . $pendaftaran->nomor_kunjungan)

@section('styles')
<style>
    .patient-info { margin-bottom: 20px; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
    .patient-info table { width: 100%; border: none; }
    .patient-info td { padding: 4px 8px; font-size: 8.5pt; border: none !important; }
    .patient-info td.label { width: 20%; color: #64748b; font-weight: bold; }
    .patient-info td.value { width: 30%; font-weight: bold; color: #1e293b; }

    table.screening { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.screening th, table.screening td { border: 1px solid #e2e8f0; padding: 10px 12px; font-size: 9pt; }
    table.screening th { background-color: #f1f5f9; color: #405189; font-weight: bold; text-transform: uppercase; font-size: 8pt; }
    .ya { color: #dc2626; font-weight: bold; text-transform: uppercase; }
    .tidak { color: #059669; font-weight: bold; text-transform: uppercase; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">HASIL SCREENING PASIEN</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Formulir Deteksi Dini dan Skrining Kesehatan</p>
    </div>

    <div class="patient-info">
        <table>
            <tr>
                <td class="label">No. Kunjungan</td><td class="value">: {{ $pendaftaran->nomor_kunjungan }}</td>
                <td class="label">Tanggal</td><td class="value">: {{ $pendaftaran->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Nama Pasien</td><td class="value">: {{ $pendaftaran->pasien->nama_pasien ?? '-' }}</td>
                <td class="label">No. RM</td><td class="value">: {{ $pendaftaran->pasien->no_rm ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Unit Pelayanan</td><td class="value">: {{ $pendaftaran->poli->nama_poli ?? '-' }}</td>
                <td class="label">Dokter</td><td class="value">: {{ $pendaftaran->dokter->nama_dokter ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="screening">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="55%">Pertanyaan / Instrumen Skrining</th>
                <th width="15%" class="text-center">Jawaban</th>
                <th width="25%">Keterangan Tambahan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($screenings as $index => $scr)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $scr->survei->pertanyaan ?? '-' }}</td>
                <td class="text-center">
                    <span class="{{ $scr->jawaban }}">{{ ucfirst($scr->jawaban) }}</span>
                </td>
                <td>{{ $scr->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">Tidak ada data screening yang diisi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: center;">
        <p style="font-size: 8pt; color: #94a3b8; font-style: italic;">Dokumen ini dihasilkan secara otomatis oleh SIGI Dental EMR</p>
    </div>
@endsection
