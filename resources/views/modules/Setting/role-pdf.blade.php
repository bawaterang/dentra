@extends('layouts.pdf-base')

@section('title', 'Data Role - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

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
        <h2 style="margin:0; color: #405189;">LAPORAN DATA PERAN (ROLE)</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Kategori Kewenangan dan Hak Akses Sistem</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Nama Role</th>
                <th width="45%">Deskripsi Kewenangan</th>
                <th width="15%">Jml User</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roleList as $index => $role)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $role->nama_role }}</td>
                <td>{{ $role->deskripsi ?? '-' }}</td>
                <td class="text-center">{{ $role->users_count }} Pengguna</td>
                <td class="text-center">
                    <span class="status-badge {{ $role->is_active ? 'status-aktif' : 'status-tidak-aktif' }}">
                        {{ $role->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
