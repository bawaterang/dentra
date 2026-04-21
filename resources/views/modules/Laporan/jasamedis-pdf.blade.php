@extends('layouts.pdf-base')

@section('title', 'Laporan Jasa Medis - ' . ($instansi->nama_instansi ?? 'SIGI Dental'))

@section('styles')
<style>
    .period { background: #f3f6f9; padding: 8px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
    .period strong { color: #405189; }
    .patient-card { margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; page-break-inside: avoid; }
    .patient-header { background: #405189; color: white; padding: 8px 12px; font-weight: bold; }
    .patient-info { padding: 8px 12px; background: #f9f9f9; border-bottom: 1px solid #eee; font-size: 8pt; }
    .patient-info table { width: 100%; border: none; }
    .patient-info td { padding: 2px 0; border: none !important; }
    .patient-info td:first-child { width: 80px; color: #666; }
    
    .grand-total-box { margin-top: 20px; padding: 12px; background: #405189; color: white; border-radius: 6px; }
    .grand-total-box table { border: none; }
    .grand-total-box td { border: none !important; color: white !important; font-size: 9pt; }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 8pt; }
    th, td { border: 1px solid #ddd; padding: 5px 6px; }
    th { background-color: #f3f6f9; color: #405189; font-weight: bold; font-size: 7.5pt; }
    .summary-row { background-color: #e8f5e9 !important; font-weight: bold; }
</style>
@endsection

@section('content')
    <div class="text-center mb-4">
        <h2 style="margin:0; color: #405189;">LAPORAN JASA MEDIS & BHP</h2>
        <p style="margin:5px 0; color: #666; font-size: 9pt;">Rincian Kompensasi Dokter dan Penggunaan Bahan</p>
    </div>

    <div class="period">
        <strong>Periode:</strong> {{ $bulan }} {{ $tahun }}
    </div>

    @php 
        $grandTotalBiaya = 0;
        $grandTotalJasaMedis = 0;
        $grandTotalBhp = 0; 
    @endphp

    @foreach($dataList as $index => $item)
        @php
            $tindakans = $getTindakanByKunjungan($item->nomor_kunjungan);
            $jasaMedisNominal = 0;
            $bhp = 0;
            foreach ($tindakans as $t) {
                $satuanLower = strtolower($t->satuan ?? '');
                if (in_array($satuanLower, ['rp', 'rupiah'])) {
                    $jasaMedisNominal += (float) $t->jasa_medis;
                } else {
                    $jasaMedisNominal += (float) ($t->jasa_medis * $t->biaya / 100);
                }
                $bhp += (float) $t->bhp;
            }
            $billing = \App\Models\TrxBilling::withoutTrashed()->where('nomor_kunjungan', $item->nomor_kunjungan)->first();
            $totalTagihan = $billing ? $billing->total_tagihan : 0;

            $grandTotalBiaya += $totalTagihan;
            $grandTotalJasaMedis += $jasaMedisNominal;
            $grandTotalBhp += $bhp;
        @endphp

        <div class="patient-card">
            <div class="patient-header">
                {{ $index + 1 }}. {{ $item->pasien ? $item->pasien->nama_pasien : '-' }} ({{ $item->nomor_kunjungan }})
            </div>
            <div class="patient-info">
                <table>
                    <tr>
                        <td>No. RM:</td>
                        <td><strong>{{ $item->pasien ? $item->pasien->no_rm : '-' }}</strong></td>
                        <td>Tanggal:</td>
                        <td class="text-right"><strong>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Dokter:</td>
                        <td><strong>{{ $item->dokter ? $item->dokter->nama_dokter : '-' }}</strong></td>
                        <td>Asuransi:</td>
                        <td class="text-right"><strong>{{ $item->asuransi ? $item->asuransi->nama_asuransi : 'Pribadi' }}</strong></td>
                    </tr>
                </table>
            </div>
            <table>
                <thead>
                    <tr>
                        <th width="35%">Tindakan</th>
                        <th width="12%" class="text-right">Biaya</th>
                        <th width="12%" class="text-center">Satuan</th>
                        <th width="13%" class="text-right">Jasmed</th>
                        <th width="13%" class="text-right">BHP</th>
                        <th width="15%" class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tindakans as $t)
                        @php
                            $satuanLower = strtolower($t->satuan ?? '');
                            if (in_array($satuanLower, ['rp', 'rupiah'])) {
                                $jasmedNom = (float) $t->jasa_medis;
                                $satuanDisplay = 'Rp ' . number_format($t->jasa_medis, 0, ',', '.');
                            } else {
                                $jasmedNom = (float) ($t->jasa_medis * $t->biaya / 100);
                                $satuanDisplay = $t->jasa_medis . '%';
                            }
                            $subtotal = $jasmedNom + (float) $t->bhp;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $t->tindakan ? $t->tindakan->nama_tindakan : '-' }}</strong><br>
                                <small style="color:#888;">{{ $t->kode_tindakan }}</small>
                            </td>
                            <td class="text-right">Rp {{ number_format($t->biaya, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $satuanDisplay }}</td>
                            <td class="text-right">Rp {{ number_format($jasmedNom, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($t->bhp, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center" style="color:#888;">Tidak ada rincian tindakan</td>
                        </tr>
                    @endforelse
                    <tr class="summary-row">
                        <td colspan="3" class="text-right">TOTAL KUNJUNGAN</td>
                        <td class="text-right">Rp {{ number_format($jasaMedisNominal, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($bhp, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($jasaMedisNominal + $bhp, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="grand-total-box">
        <table width="100%">
            <tr>
                <td width="40%"><strong>GRAND TOTAL KESELURUHAN</strong></td>
                <td class="text-right"><strong>Biaya: Rp {{ number_format($grandTotalBiaya, 0, ',', '.') }}</strong></td>
                <td class="text-right"><strong>Jasmed: Rp {{ number_format($grandTotalJasaMedis, 0, ',', '.') }}</strong></td>
                <td class="text-right"><strong>BHP: Rp {{ number_format($grandTotalBhp, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>
@endsection