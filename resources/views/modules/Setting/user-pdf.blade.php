@extends('layouts.pdf-base')

@section('title', 'Data User - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

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
        <h2 style="margin:0; color: #405189;">LAPORAN DATA PENGGUNA</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Daftar Akun dan Akses Pengguna Aplikasi</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode User</th>
                <th width="20%">Username</th>
                <th width="25%">Nama Lengkap</th>
                <th width="20%">Email</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($userList as $index => $user)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $user->user_code }}</td>
                <td>{{ $user->username }}</td>
                <td>{{ $user->full_name }}</td>
                <td>{{ $user->email }}</td>
                <td class="text-center">
                    <span class="status-badge {{ $user->is_active ? 'status-aktif' : 'status-tidak-aktif' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
