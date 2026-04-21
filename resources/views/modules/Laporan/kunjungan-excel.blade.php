<table>
    <thead>
        <tr>
            <th style="font-weight: bold; border: 1px solid #000; width: 50px;">No</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">No. Kunjungan</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 100px;">No. RM</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 200px;">Nama Pasien</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 120px;">Tanggal Periksa</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 200px;">Dokter</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">Asuransi</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 150px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dataList as $index => $item)
        <tr>
            <td style="border: 1px solid #000; text-align: center;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000;">{{ $item->nomor_kunjungan ? "'" . $item->nomor_kunjungan : '-' }}</td>
            <td style="border: 1px solid #000;">{{ $item->pasien ? $item->pasien->no_rm : '-' }}</td>
            <td style="border: 1px solid #000;">{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</td>
            <td style="border: 1px solid #000;">{{ $item->dokter ? $item->dokter->nama_dokter : '-' }}</td>
            <td style="border: 1px solid #000;">{{ $item->asuransi ? $item->asuransi->nama_asuransi : 'Pribadi' }}</td>
            <td style="border: 1px solid #000;">{{ ucfirst($item->status) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
