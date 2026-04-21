@extends('layouts.pdf-base')

@section('title', 'Data Diagnosis - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f3f6f9; color: #405189; font-weight: bold; font-size: 9pt; }
    td { font-size: 8.5pt; }
    tr:nth-child(even) { background-color: #fafafa; }
    .status-badge { padding: 3px 6px; border-radius: 4px; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .status-aktif { background-color: #def2d0; color: #3c763d; }
    .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA DIAGNOSIS</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Kode Diagnosa ICD-10 Bedah Mulut & Gigi</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode ICD-10</th>
                <th width="50%">Nama Diagnosa</th>
                <th width="15%">Kategori</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $item->kode_diagnosa }}</td>
                <td>{{ $item->nama_diagnosa }}</td>
                <td class="text-center">{{ $item->kategori ?? '-' }}</td>
                <td class="text-center">
                    <span class="status-badge {{ strtolower($item->status) == 'aktif' ? 'status-aktif' : 'status-tidak-aktif' }}">
                        {{ $item->status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
