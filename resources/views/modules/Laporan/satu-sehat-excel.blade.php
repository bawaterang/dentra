<table>
    <thead>
        <tr>
            <th colspan="7" style="font-size: 14px; font-weight: bold; text-align: center;">LAPORAN SATU SEHAT</th>
        </tr>
        <tr>
            <th colspan="7" style="font-size: 12px; font-style: italic; text-align: center;">Periode: {{ $periode }}</th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 60px;">NO</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 150px;">NO. KUNJUNGAN</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 200px;">NAMA PASIEN</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 150px;">NO. RM</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 150px;">NIK</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 150px;">STATUS BRIDGING</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 150px;">TANGGAL KUNJUNGAN</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dataList as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->nomor_kunjungan }}</td>
            <td>{{ $item->pasien ? $item->pasien->nama_pasien : '-' }}</td>
            <td>{{ $item->pasien ? $item->pasien->no_rm : '-' }}</td>
            <td>{{ $item->pasien && $item->pasien->nik ? $item->pasien->nik : '-' }}</td>
            @php
                $statuses = \App\Models\TrxSatusehatLog::where('nomor_kunjungan', $item->nomor_kunjungan)->get();
                $successCount = $statuses->where('status', 'Success')->count();
                $failedCount = $statuses->where('status', 'Failed')->count();
                $bundleStatus = $statuses->isEmpty() ? 'Pending' : ($failedCount === 0 ? 'Success' : ($successCount === 0 ? 'Failed' : 'Partial'));
            @endphp
            <td>{{ $bundleStatus }}</td>
            <td>{{ $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
