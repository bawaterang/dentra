@extends('layouts.pdf-base')

@section('title', 'Laporan Kritik & Saran - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f8fafc; color: #405189; padding: 8px 5px; text-align: left; border: 1px solid #cbd5e1; font-weight: bold; text-transform: uppercase; font-size: 8.5pt; }
    td { padding: 8px 5px; border: 1px solid #cbd5e1; vertical-align: top; font-size: 8pt; }
    .sender-info { font-weight: bold; color: #1e293b; }
    .meta-text { font-size: 7.5pt; color: #64748b; margin-top: 3px; }
    .status-badge { padding: 2px 5px; border-radius: 4px; font-size: 7pt; font-weight: bold; }
    .status-terjawab { background: #dcfce7; color: #166534; }
    .status-pending { background: #fef9c3; color: #854d0e; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN KRITIK & SARAN</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Rekapitulasi Feedback dan Respon Layanan — Periode: {{ $periode }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="20%">PENGIRIM</th>
                <th width="15%">KONTAK</th>
                <th width="30%">PESAN</th>
                <th width="30%">STATUS & RESPONS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataList as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="sender-info">{{ $item->nama ?: 'Anonim' }}</div>
                    <div class="meta-text">{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</div>
                </td>
                <td>
                    <div>E: {{ $item->email ?: '-' }}</div>
                    <div>HP: {{ $item->nomor_hp ?: '-' }}</div>
                </td>
                <td>
                    <div style="font-style: italic; color: #334155;">"{{ $item->pesan }}"</div>
                    <div class="meta-text">Platform: {{ $item->platform ?: '-' }}</div>
                </td>
                <td>
                    @if($item->jawaban)
                        <div class="status-badge status-terjawab mb-1" style="display:inline-block;">Terjawab</div>
                        <div class="meta-text mb-1">Oleh: {{ $item->penjawab ?: 'Sistem' }} ({{ \Carbon\Carbon::parse($item->waktu_jawab)->format('d/m/Y H:i') }})</div>
                        <div style="color: #405189;">{{ $item->jawaban }}</div>
                    @else
                        <div class="status-badge status-pending">Menunggu Jawaban</div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="padding: 20px;">Tidak ada feedback pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
@endsection
