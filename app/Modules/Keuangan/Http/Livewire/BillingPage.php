<?php

namespace App\Modules\Keuangan\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;

class BillingPage extends Component
{
    #[Title('Billing Pasien - EMR Klinik Gigi')]

    public $searchQuery = '';
    public $pasienList = [];
    public $selectedPasien = null;
    public $tindakanList = [];
    
    // Billing properties
    public $totalTagihan = 0;
    public $totalBayar = '';
    public $kembalian = 0;
    public $hutang = 0;
    public $status = 'Belum Lunas';

    public $selectedDate;

    public function mount()
    {
        $this->selectedDate = today()->format('Y-m-d');
        $this->loadPasien();
    }

    public function updatedSelectedDate()
    {
        $this->loadPasien();
    }

    public function loadPasien()
    {
        $this->pasienList = DB::table('trx_pendaftaran')
            ->join('mst_pasien', 'trx_pendaftaran.pasien_id', '=', 'mst_pasien.id')
            ->leftJoin('trx_billing', 'trx_pendaftaran.nomor_kunjungan', '=', 'trx_billing.nomor_kunjungan')
            ->whereDate('trx_pendaftaran.created_at', $this->selectedDate)
            ->select(
                'trx_pendaftaran.*', 
                'mst_pasien.nama_pasien', 
                'mst_pasien.no_rm', 
                'mst_pasien.tanggal_lahir',
                DB::raw("COALESCE(trx_billing.status, 'Belum Lunas') as billing_status")
            )
            ->orderBy('trx_pendaftaran.created_at', 'desc')
            ->get();
    }

    public function selectPasien($nomor_kunjungan)
    {
        $this->selectedPasien = DB::table('trx_pendaftaran')
            ->join('mst_pasien', 'trx_pendaftaran.pasien_id', '=', 'mst_pasien.id')
            ->where('trx_pendaftaran.nomor_kunjungan', $nomor_kunjungan)
            ->select('trx_pendaftaran.*', 'mst_pasien.nama_pasien', 'mst_pasien.no_rm', 'mst_pasien.tanggal_lahir')
            ->first();

        if ($this->selectedPasien) {
            $this->loadTindakan();
            $this->totalBayar = '';
            $this->kembalian = 0;
            $this->hutang = 0;
            $this->status = 'Belum Lunas';
        }
    }

    public function loadTindakan()
    {
        if (!$this->selectedPasien) return;

        $this->tindakanList = DB::table('trx_tindakan')
            ->join('mst_tindakan', 'trx_tindakan.kode_tindakan', '=', 'mst_tindakan.kode_tindakan')
            ->where('trx_tindakan.nomor_kunjungan', $this->selectedPasien->nomor_kunjungan)
            ->select('trx_tindakan.*', 'mst_tindakan.nama_tindakan')
            ->get();

        $this->totalTagihan = $this->tindakanList->sum('biaya');
        
        // Also add total recipe cost if available in future
        
        $this->calculatePayment();
    }

    public function updatedTotalBayar()
    {
        $this->calculatePayment();
    }

    public function calculatePayment()
    {
        $bayar = floatval(str_replace(['.', ','], '', (string)$this->totalBayar));
        
        if ($bayar >= $this->totalTagihan) {
            $this->kembalian = $bayar - $this->totalTagihan;
            $this->hutang = 0;
            $this->status = 'Lunas';
        } else {
            $this->kembalian = 0;
            $this->hutang = $this->totalTagihan - $bayar;
            $this->status = 'Belum Lunas';
        }
    }

    public function saveBilling()
    {
        $this->validate([
            'totalBayar' => 'required',
        ]);

        $bayar = floatval(str_replace(['.', ','], '', (string)$this->totalBayar));

        if (!$this->selectedPasien) return;

        DB::beginTransaction();
        try {
            // Check if billing already exists
            $billing = DB::table('trx_billing')
                ->where('nomor_kunjungan', $this->selectedPasien->nomor_kunjungan)
                ->first();

            $no_faktur = $billing ? $billing->no_faktur : 'INV-' . date('YmdHis') . str_pad($this->selectedPasien->pasien_id, 4, '0', STR_PAD_LEFT);

            $billingId = DB::table('trx_billing')->updateOrInsert(
                ['nomor_kunjungan' => $this->selectedPasien->nomor_kunjungan],
                [
                    'pasien_id' => $this->selectedPasien->pasien_id,
                    'no_faktur' => $no_faktur,
                    'total_tagihan' => $this->totalTagihan,
                    'total_bayar' => $bayar,
                    'kembalian' => $this->kembalian,
                    'hutang' => $this->hutang,
                    'status' => $this->status,
                    'tanggal_bayar' => now(),
                    'created_by' => auth()->user()->username ?? 'System',
                    'updated_at' => now(),
                ]
            );
            
            // Get ID for details
            $billingRecord = DB::table('trx_billing')->where('nomor_kunjungan', $this->selectedPasien->nomor_kunjungan)->first();

            if ($billingRecord) {
                // Delete existing details
                DB::table('trx_billing_detail')->where('billing_id', $billingRecord->id)->delete();
                
                // Insert new details
                foreach ($this->tindakanList as $tindakan) {
                    DB::table('trx_billing_detail')->insert([
                        'billing_id' => $billingRecord->id,
                        'kode_tindakan' => $tindakan->kode_tindakan,
                        'nama_tindakan' => $tindakan->nama_tindakan,
                        'biaya' => $tindakan->biaya,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Tagihan berhasil disimpan']);
            $this->loadPasien();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal menyimpan tagihan: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.modules.keuangan.billing-page');
    }

    public function terbilang($angka)
    {
        $angka = abs($angka);
        $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        $terbilang = "";

        if ($angka < 12) {
            $terbilang = " " . $baca[$angka];
        } elseif ($angka < 20) {
            $terbilang = $this->terbilang($angka - 10) . " Belas";
        } elseif ($angka < 100) {
            $terbilang = $this->terbilang($angka / 10) . " Puluh" . $this->terbilang($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = " Seratus" . $this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = $this->terbilang($angka / 100) . " Ratus" . $this->terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = " Seribu" . $this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = $this->terbilang($angka / 1000) . " Ribu" . $this->terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = $this->terbilang($angka / 1000000) . " Juta" . $this->terbilang($angka % 1000000);
        }

        return trim($terbilang);
    }
}
