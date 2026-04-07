<?php

namespace App\Modules\Laporan\Exports;

use App\Models\TrxBilling;
use App\Models\TrxPendaftaran;
use App\Models\TrxTindakan;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanJasaMedisExport implements FromView, WithTitle
{
    protected $bulan;

    protected $tahun;

    protected $dokter;

    public function __construct($bulan, $tahun, $dokter = 'all')
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->dokter = $dokter;
    }

    public function calculateNominalJasaMedis($jasaMedis, $biaya, $satuan)
    {
        $satuanLower = strtolower($satuan ?? '');
        if (in_array($satuanLower, ['rp', 'rupiah'])) {
            return (float) $jasaMedis;
        }

        return (float) ($jasaMedis * $biaya / 100);
    }

    public function view(): View
    {
        $query = TrxPendaftaran::with(['pasien', 'dokter', 'asuransi'])
            ->whereNotNull('created_at')
            ->whereMonth('created_at', $this->bulan)
            ->whereYear('created_at', $this->tahun);

        if ($this->dokter !== 'all') {
            $query->where('dokter_id', $this->dokter);
        }

        $dataList = $query->orderBy('created_at', 'desc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $grandTotalBiaya = 0;
        $grandTotalJasaMedis = 0;
        $grandTotalBhp = 0;

        foreach ($dataList as $item) {
            $tindakans = TrxTindakan::withoutTrashed()->where('nomor_kunjungan', $item->nomor_kunjungan)->get();
            $jasaMedisNominal = 0;
            $bhp = 0;

            foreach ($tindakans as $t) {
                $jasaMedisNominal += $this->calculateNominalJasaMedis($t->jasa_medis, $t->biaya, $t->satuan);
                $bhp += (float) $t->bhp;
            }

            $billing = TrxBilling::withoutTrashed()->where('nomor_kunjungan', $item->nomor_kunjungan)->first();
            $totalTagihan = $billing ? $billing->total_tagihan : 0;

            $grandTotalBiaya += $totalTagihan;
            $grandTotalJasaMedis += $jasaMedisNominal;
            $grandTotalBhp += $bhp;
        }

        return view('modules.Laporan.jasamedis-excel', [
            'dataList' => $dataList,
            'bulan' => $namaBulan[(int) $this->bulan],
            'tahun' => $this->tahun,
            'grandTotalBiaya' => $grandTotalBiaya,
            'grandTotalJasaMedis' => $grandTotalJasaMedis,
            'grandTotalBhp' => $grandTotalBhp,
        ]);
    }

    public function title(): string
    {
        return 'Laporan Jasa Medis';
    }
}
