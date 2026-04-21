@extends('layouts.pdf-base')

@section('title', 'Laporan Pendapatan - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    .period { background: #f3f6f9; padding: 8px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
    .period strong { color: #405189; }
    
    .summary-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: none; }
    .summary-col { text-align: center; padding: 10px; border: 1px solid #e2e8f0 !important; background: #fff; }
    .summary-label { font-size: 7.5pt; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .summary-val { font-size: 11pt; font-weight: bold; color: #405189; }
    
    .list-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .list-table th { background: #405189; color: white; border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; font-size: 8pt; font-weight: bold; }
    .list-table td { border: 1px solid #e2e8f0; padding: 6px 8px; font-size: 8pt; vertical-align: top; }
    .list-table tr:nth-child(even) { background-color: #f8fafc; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN PENDAPATAN & BHP</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Rekapitulasi Keuangan dan Penggunaan Bahan</p>
    </div>

    <div class="period">
        <strong>Periode:</strong> {{ $periode }}
    </div>

    <table class="summary-grid">
        <tr>
            <td class="summary-col">
                <div class="summary-label">Pendapatan</div>
                <div class="summary-val" style="color: #059669;">Rp {{ number_format($summary['pendapatan'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-col">
                <div class="summary-label">Biaya BHP</div>
                <div class="summary-val" style="color: #d97706;">Rp {{ number_format($summary['pengeluaran'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-col">
                <div class="summary-label">Total Piutang</div>
                <div class="summary-val" style="color: #e11d48;">Rp {{ number_format($summary['piutang'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-col" style="background-color: #f0f7ff;">
                <div class="summary-label">Laba Bersih</div>
                <div class="summary-val" style="color: #4338ca;">Rp {{ number_format($summary['laba_bersih'], 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <table class="list-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">No. Faktur</th>
                <th width="20%">Pasien</th>
                <th width="15%" class="text-right">Pendapatan</th>
                <th width="15%" class="text-right">BHP</th>
                <th width="15%" class="text-right">Piutang</th>
                <th width="15%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataList as $index => $item)
            @php
                $pengeluaran = $getPengeluaranBhp($item->nomor_kunjungan);
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->no_faktur }}</strong><br>
                    <span style="font-size: 7.5pt; color: #888;">#{{ $item->nomor_kunjungan }}</span>
                </td>
                <td>
                    <strong>{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</strong><br>
                    <span style="font-size: 7.5pt; color: #888;">RM: {{ $item->pasien ? $item->pasien->no_rm : '-' }}</span><br>
                    <span style="font-size: 7.5pt; color: #888;">Tgl: {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</span>
                </td>
                <td class="text-right font-bold">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($pengeluaran, 0, ',', '.') }}</td>
                <td class="text-right" style="color: {{ $item->hutang > 0 ? '#dc2626' : '#64748b' }}">Rp {{ number_format($item->hutang, 0, ',', '.') }}</td>
                <td class="text-center">
                    <span style="font-size: 7pt; font-weight: bold; text-transform: uppercase;">{{ $item->status }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">Tidak ada data transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
