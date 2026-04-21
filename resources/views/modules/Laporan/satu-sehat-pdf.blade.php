@extends('layouts.pdf-base')

@section('title', 'Laporan Satu Sehat - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f8fafc; color: #047857; padding: 8px 5px; text-align: left; border: 1px solid #cbd5e1; font-weight: bold; text-transform: uppercase; font-size: 8.5pt; }
    td { padding: 6px 5px; border: 1px solid #cbd5e1; vertical-align: top; font-size: 8pt; }
    .status-badge { padding: 2px 5px; border-radius: 4px; font-size: 7pt; font-weight: bold; }
    .status-success { background: #dcfce7; color: #166534; }
    .status-failed { background: #fee2e2; color: #991b1b; }
    .status-pending { background: #fef9c3; color: #854d0e; }
    .status-partial { background: #ffedd5; color: #9a3412; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #047857;">LAPORAN SATU SEHAT (KEMENKES)</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Monitoring Bridging Data Rekam Medis — Periode: {{ $periode }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="15%">KUNJUNGAN</th>
                <th width="30%">NAMA PASIEN & INFO</th>
                <th width="15%">NIK</th>
                <th width="20%">STATUS BRIDGING</th>
                <th width="15%">TGL KUNJUNGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="font-bold">#{{ $item->nomor_kunjungan }}</div>
                </td>
                <td>
                    <div class="font-bold">{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</div>
                    <div style="font-size: 7pt; color: #64748b; margin-top: 2px;">
                        RM: {{ $item->pasien ? $item->pasien->no_rm : '-' }} | 
                        {{ $item->poli ? $item->poli->nama_poli : '-' }} | 
                        {{ $item->asuransi ? $item->asuransi->nama_asuransi : 'Pribadi' }}
                    </div>
                </td>
                <td class="text-center">
                    <div>{{ $item->pasien && $item->pasien->nik ? $item->pasien->nik : '-' }}</div>
                </td>
                <td class="text-center">
                    @php
                        $statuses = \App\Models\TrxSatusehatLog::where('nomor_kunjungan', $item->nomor_kunjungan)->get();
                        $successCount = $statuses->where('status', 'Success')->count();
                        $failedCount = $statuses->where('status', 'Failed')->count();
                        $bundleStatus = $statuses->isEmpty() ? 'Pending' : ($failedCount === 0 ? 'Success' : ($successCount === 0 ? 'Failed' : 'Partial'));
                    @endphp
                    <span class="status-badge status-{{ strtolower($bundleStatus) }}">
                        {{ strtoupper($bundleStatus) }}
                    </span>
                    @if(!$statuses->isEmpty())
                        <div style="font-size: 6.5pt; color: #64748b; margin-top: 2px;">
                            ({{ $successCount }} OK, {{ $failedCount }} Fail)
                        </div>
                    @endif
                </td>
                <td class="text-center">
                    {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px;">Tidak ada data bridging pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
@endsection
