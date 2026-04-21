@extends('layouts.pdf-base')

@section('title', 'Data Tindakan - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f3f6f9; color: #405189; padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
    td { padding: 10px; border: 1px solid #ddd; font-size: 8.5pt; vertical-align: top; }
    tr:nth-child(even) { background-color: #fafafa; }
    .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
    .status-aktif { background-color: #def2d0; color: #3c763d; }
    .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
    .text-right { text-align: right; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA TINDAKAN</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Prosedur Medis dan Tarif Layanan Dasar</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode</th>
                <th width="35%">Nama Tindakan</th>
                <th width="15%">Kategori</th>
                <th width="15%" class="text-right">Harga Default</th>
                <th width="15%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $item->kode_tindakan }}</td>
                <td>{{ $item->nama_tindakan }}</td>
                <td>{{ $item->kategori_tindakan ?? '-' }}</td>
                <td class="text-right">Rp {{ number_format($item->harga_default, 0, ',', '.') }}</td>
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
