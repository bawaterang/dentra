@extends('layouts.pdf-base')

@section('title', 'Data Menu - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

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
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN DATA MENU</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Struktur Navigasi Aplikasi</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Nama Menu</th>
                <th width="20%">Link</th>
                <th width="15%">Icon</th>
                <th width="15%">Parent ID</th>
                <th width="10%">Urutan</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $item->menu_name }}</td>
                <td>{{ $item->menu_link ?? '-' }}</td>
                <td><i class="{{ $item->menu_icon }}"></i> {{ $item->menu_icon ?? '-' }}</td>
                <td class="text-center">{{ $item->parent_id ?? '-' }}</td>
                <td class="text-center">{{ $item->order_no }}</td>
                <td class="text-center">
                    <span class="status-badge {{ $item->is_active ? 'status-aktif' : 'status-tidak-aktif' }}">
                        {{ $item->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
