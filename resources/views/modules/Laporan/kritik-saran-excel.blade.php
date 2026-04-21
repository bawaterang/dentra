<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 60px;">NO</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 150px;">TANGGAL</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 200px;">NAMA PENGIRIM</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 150px;">NO. HP</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 200px;">EMAIL</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 400px;">PESAN</th>
            <th style="font-weight: bold; background-color: #f2f2f2; width: 400px;">JAWABAN</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dataList as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-' }}</td>
            <td>{{ $item->nama ?: 'Anonim' }}</td>
            <td>{{ $item->nomor_hp ?: '-' }}</td>
            <td>{{ $item->email ?: '-' }}</td>
            <td>{{ $item->pesan }}</td>
            <td>{{ $item->jawaban ?: 'Menunggu Jawaban' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
