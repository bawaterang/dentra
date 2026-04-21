@extends('layouts.pdf-base')

@section('title', 'Data Dokter - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f3f6f9; color: #405189; padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; }
    td { padding: 10px; border: 1px solid #ddd; font-size: 8pt; vertical-align: top; }
    tr:nth-child(even) { background-color: #fafafa; }
    .status-badge { padding: 3px 6px; border-radius: 4px; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .status-aktif { background-color: #def2d0; color: #3c763d; }
    .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
    .status-cuti { background-color: #fef5e1; color: #856404; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA DOKTER</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Praktisi Medis dan Tenaga Kesehatan</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Kode</th>
                <th width="20%">Nama Dokter</th>
                <th width="15%">Spesialisasi</th>
                <th width="12%">Gender</th>
                <th width="13%">No. SIP</th>
                <th width="12%">No. STR</th>
                <th width="13%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dokterList as $index => $dokter)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $dokter->kode_dokter }}</td>
                <td>{{ $dokter->nama_dokter }}</td>
                <td>{{ $dokter->spesialisasi ?? '-' }}</td>
                <td class="text-center">{{ $dokter->jenis_kelamin }}</td>
                <td>{{ $dokter->no_sip ?? '-' }}</td>
                <td>{{ $dokter->no_str ?? '-' }}</td>
                <td class="text-center">
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
@endsection
