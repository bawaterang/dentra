@extends('layouts.pdf-base')

@section('title', 'Data Informasi - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f3f6f9; color: #405189; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; }
    td { padding: 8px; border: 1px solid #ddd; font-size: 8pt; vertical-align: top; }
    tr:nth-child(even) { background-color: #fafafa; }
    .status-badge { padding: 3px 6px; border-radius: 4px; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .status-aktif { background-color: #def2d0; color: #3c763d; }
    .status-expired { background-color: #f2dede; color: #a94442; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA INFORMASI</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Konten Informasi dan Pengumuman Website</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="35%">Deskripsi Informasi</th>
                <th width="15%">Mulai</th>
                <th width="15%">Berakhir</th>
                <th width="12%">Status</th>
                <th width="18%">Dibuat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @php $today = \Carbon\Carbon::today()->format('Y-m-d'); @endphp
            @foreach($informasiList as $index => $info)
            @php
                $isAktif = ($info->date_start <= $today && $info->date_expired >= $today);
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $info->description }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($info->date_start)->format('d/m/Y') }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($info->date_expired)->format('d/m/Y') }}</td>
                <td class="text-center">
                    <span class="status-badge {{ $isAktif ? 'status-aktif' : 'status-expired' }}">
                        {{ $isAktif ? 'Aktif' : 'Expired' }}
                    </span>
                </td>
                <td class="text-center">{{ $info->created_by ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
