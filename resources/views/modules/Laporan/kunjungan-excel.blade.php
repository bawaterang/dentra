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
            <th style="font-weight: bold; border: 1px solid #000; width: 100px;">Lingkar Perut</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 200px;">Alergi</th>
            <th style="font-weight: bold; border: 1px solid #000; width: 300px;">Rekomendasi Diet</th>
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
            <td style="border: 1px solid #000;">{{ $item->lingkar_perut ?: '-' }}</td>
            <td style="border: 1px solid #000;">{{ $item->kode_alergi ? (\App\Models\MstAlergi::where('kdAlergi', $item->kode_alergi)->value('nmAlergi') . ' ') : '' }}{{ $item->alergi ?: '-' }}</td>
            <td style="border: 1px solid #000;">{{ \Illuminate\Support\Facades\DB::table('trx_pemeriksaan')->where('nomor_kunjungan', $item->nomor_kunjungan)->value('rekomendasi_diet') ?: '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
