@extends('layouts.pdf-base')

@section('title', 'Data Tarif - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    th { background-color: #f3f6f9; color: #405189; font-weight: bold; font-size: 8.5pt; }
    td { font-size: 8pt; }
    tr:nth-child(even) { background-color: #fafafa; }
    .status-badge { padding: 3px 6px; border-radius: 4px; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .status-aktif { background-color: #def2d0; color: #3c763d; }
    .status-tidak-aktif { background-color: #f2dede; color: #a94442; }
    .text-right { text-align: right; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA TARIF</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Tarif Layanan dan Jasa Medis</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tindakan</th>
                <th width="15%">Asuransi</th>
                <th width="15%" class="text-right">Tarif</th>
                <th width="15%" class="text-right">Jasmed</th>
                <th width="10%">Satuan</th>
                <th width="15%" class="text-right">BHP</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->kode_tindakan }}</td>
                <td>{{ $item->kode_asuransi }}</td>
                <td class="text-right">Rp {{ number_format($item->tarif, 0, ',', '.') }}</td>
                <td class="text-right">{{ ($item->satuan_jasmed ?? 'Rp') === '%' ? number_format($item->jasmed, 0, ',', '.') . '%' : 'Rp ' . number_format($item->jasmed, 0, ',', '.') }}</td>
                <td class="text-center">{{ $item->satuan_jasmed ?? 'Rp' }}</td>
                <td class="text-right">Rp {{ number_format($item->bhp, 0, ',', '.') }}</td>
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
