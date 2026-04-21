<table>
    <thead>
        <tr>
            <th colspan="3" style="font-weight: bold;">Ringkasan</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <td colspan="2">Total Pendapatan</td>
            <td style="font-weight: bold;">Rp {{ $summary['pendapatan'] }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="2">Total Pengeluaran BHP</td>
            <td style="font-weight: bold;">Rp {{ $summary['pengeluaran'] }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="2">Total Piutang</td>
            <td style="font-weight: bold;">Rp {{ $summary['piutang'] }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="2">Laba Bersih</td>
            <td style="font-weight: bold;">Rp {{ $summary['laba_bersih'] }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr></tr>
        <tr style="background-color: #f3f6f9;">
            <th style="font-weight: bold; border: 1px solid #000; width: 50px;">No</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">No. Faktur</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">No. Kunjungan</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 100px;">No. RM</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 200px;">Nama Pasien</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 120px;">Tgl Transaksi</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">Pendapatan</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">Pengeluaran (BHP)</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">Piutang</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 120px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dataList as $index => $item)
        @php
            $pengeluaran = $getPengeluaranBhp($item->nomor_kunjungan);
        @endphp
        <tr>
            <td style="border: 1px solid #000; text-align: center;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000;">{{ $item->no_faktur }}</td>
            <td style="border: 1px solid #000;">{{ $item->nomor_kunjungan ? "'" . $item->nomor_kunjungan : '-' }}</td>
            <td style="border: 1px solid #000;">{{ $item->pasien ? $item->pasien->no_rm : '-' }}</td>
            <td style="border: 1px solid #000;">{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</td>
            <td style="border: 1px solid #000; text-align: right;">{{ $item->total_bayar }}</td>
            <td style="border: 1px solid #000; text-align: right;">{{ $pengeluaran }}</td>
            <td style="border: 1px solid #000; text-align: right;">{{ $item->hutang }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $item->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
