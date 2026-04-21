@extends('layouts.pdf-base')

@section('title', 'Data Poli - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f3f6f9; color: #405189; padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
    td { padding: 10px; border: 1px solid #ddd; font-size: 8.5pt; vertical-align: top; }
    tr:nth-child(even) { background-color: #fafafa; }
    .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
    .status-aktif { background-color: #def2d0; color: #3c763d; }
    .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA POLIKLINIK</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Unit Pelayanan dan Instalasi Klinik</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%" class="text-center">No</th>
                <th width="30%">Kode Poli</th>
                <th width="40%">Nama Poliklinik</th>
                <th width="20%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $item->kode_poli }}</td>
                <td>{{ $item->nama_poli }}</td>
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
