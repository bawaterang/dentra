<table>
    <thead>
        <tr>
            <th colspan="6" style="background: #405189; color: white; text-align: center; font-size: 16pt; font-weight: bold;">LAPORAN JASA MEDIS</th>
        </tr>
        <tr>
            <th colspan="6" style="background: #405189; color: white; text-align: center; font-size: 10pt;">Periode: {{ $bulan }} {{ $tahun }}</th>
        </tr>
        <tr>
            <th colspan="6" style="background: #f3f6f9;">&nbsp;</th>
        </tr>
        <tr style="background: #405189; color: white; font-weight: bold; text-align: center;">
            <th>No</th>
            <th>No. Kunjungan</th>
            <th>No. RM</th>
            <th>Nama Pasien</th>
            <th>Tgl Periksa</th>
            <th>Dokter</th>
            <th>Asuransi</th>
            <th>Tindakan</th>
            <th>Kode</th>
            <th>Biaya</th>
            <th>Satuan</th>
            <th>Jasa Medis</th>
            <th>BHP</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @php $grandTotalBiaya = 0; $grandTotalJasaMedis = 0; $grandTotalBhp = 0; @endphp
        
        @foreach($dataList as $index => $item)
        @php
            $tindakans = \App\Models\TrxTindakan::withoutTrashed()->where('nomor_kunjungan', $item->nomor_kunjungan)->get();
            $jasaMedisNominal = 0;
            $bhp = 0;
            
            foreach($tindakans as $t) {
                $satuanLower = strtolower($t->satuan ?? '');
                if (in_array($satuanLower, ['rp', 'rupiah'])) {
                    $jasmedNominal = (float) $t->jasa_medis;
                    $satuanDisplay = 'Rp ' . number_format($t->jasa_medis, 0, ',', '.');
                } else {
                    $jasmedNominal = (float) ($t->jasa_medis * $t->biaya / 100);
                    $satuanDisplay = $t->jasa_medis . '%';
                }
                $jasaMedisNominal += $jasmedNominal;
                $bhp += (float) $t->bhp;
            }
            
            $billing = \App\Models\TrxBilling::withoutTrashed()->where('nomor_kunjungan', $item->nomor_kunjungan)->first();
            $totalTagihan = $billing ? $billing->total_tagihan : 0;
            
            $grandTotalBiaya += $totalTagihan;
            $grandTotalJasaMedis += $jasaMedisNominal;
            $grandTotalBhp += $bhp;
        @endphp
        
        @forelse($tindakans as $tIndex => $t)
        @php
            $satuanLower = strtolower($t->satuan ?? '');
            if (in_array($satuanLower, ['rp', 'rupiah'])) {
                $jasmedNominal = (float) $t->jasa_medis;
                $satuanDisplay = 'Rp ' . number_format($t->jasa_medis, 0, ',', '.');
            } else {
                $jasmedNominal = (float) ($t->jasa_medis * $t->biaya / 100);
                $satuanDisplay = $t->jasa_medis . '%';
            }
            $subtotal = $jasmedNominal + (float) $t->bhp;
        @endphp
        <tr>
            <td style="text-align: center;">{{ $tIndex == 0 ? ($index + 1) : '' }}</td>
            <td>{{ $tIndex == 0 ? $item->nomor_kunjungan : '' }}</td>
            <td>{{ $tIndex == 0 ? ($item->pasien ? $item->pasien->no_rm : '-') : '' }}</td>
            <td>{{ $tIndex == 0 ? ($item->pasien ? $item->pasien->nama_pasien : '-') : '' }}</td>
            <td>{{ $tIndex == 0 ? ($item->created_at ? $item->created_at->format('d/m/Y') : '-') : '' }}</td>
            <td>{{ $tIndex == 0 ? ($item->dokter ? $item->dokter->nama_dokter : '-') : '' }}</td>
            <td>{{ $tIndex == 0 ? ($item->asuransi ? $item->asuransi->nama_asuransi : 'Pribadi') : '' }}</td>
            <td>{{ $t->tindakan ? $t->tindakan->nama_tindakan : '-' }}</td>
            <td>{{ $t->kode_tindakan }}</td>
            <td style="text-align: right;">{{ number_format($t->biaya, 0, ',', '.') }}</td>
            <td style="text-align: center;">{{ $satuanDisplay }}</td>
            <td style="text-align: right;">{{ number_format($jasmedNominal, 0, ',', '.') }}</td>
            <td style="text-align: right;">{{ number_format($t->bhp, 0, ',', '.') }}</td>
            <td style="text-align: right;">{{ number_format($subtotal, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
            <td style="text-align: center;">{{ $index + 1 }}</td>
            <td>{{ $item->nomor_kunjungan }}</td>
            <td>{{ $item->pasien ? $item->pasien->no_rm : '-' }}</td>
            <td>{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</td>
            <td>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</td>
            <td>{{ $item->dokter ? $item->dokter->nama_dokter : '-' }}</td>
            <td>{{ $item->asuransi ? $item->asuransi->nama_asuransi : 'Pribadi' }}</td>
            <td colspan="7" style="text-align: center; color: #888;">Tidak ada tindakan</td>
        </tr>
        @endforelse
        @endforeach
        
        <tr style="background: #E8F5E9; font-weight: bold;">
            <td colspan="10" style="text-align: right;">GRAND TOTAL:</td>
            <td style="text-align: right;">{{ number_format($grandTotalBiaya, 0, ',', '.') }}</td>
            <td style="text-align: right;">{{ number_format($grandTotalJasaMedis, 0, ',', '.') }}</td>
            <td style="text-align: right;">{{ number_format($grandTotalBhp, 0, ',', '.') }}</td>
            <td style="text-align: right;">{{ number_format($grandTotalJasaMedis + $grandTotalBhp, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
