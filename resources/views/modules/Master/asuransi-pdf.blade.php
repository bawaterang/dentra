@extends('layouts.pdf-base')

@section('title', 'Data Asuransi - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f3f6f9; color: #405189; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; }
    td { padding: 8px; border: 1px solid #ddd; font-size: 8pt; vertical-align: top; }
    tr:nth-child(even) { background-color: #fafafa; }
    .status-badge { padding: 3px 6px; border-radius: 4px; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .status-aktif { background-color: #def2d0; color: #3c763d; }
    .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA ASURANSI</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Rekanan Asuransi dan Tipe Jaminan</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Kode</th>
                <th width="25%">Nama Asuransi</th>
                <th width="15%">Tipe</th>
                <th width="10%">Diskon</th>
                <th width="20%">No. Telepon</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $item->kode_asuransi }}</td>
                <td>{{ $item->nama_asuransi }}</td>
                <td class="text-center">{{ $item->tipe_asuransi }}</td>
                <td class="text-center">{{ number_format($item->diskon, 2) }}%</td>
                <td>{{ $item->no_telepon ?? '-' }}</td>
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
