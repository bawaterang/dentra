<?php

namespace App\Modules\Bridging\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TrxPasienBpjs;
use Carbon\Carbon;

class DataPasienBpjsPage extends Component
{
    use WithPagination;

    public $filter_type = 'semua'; // semua, nomor_urut
    public $search_date;
    public $nomor_urut;

    public $search = '';
    public $perPage = 10;

    protected $queryString = ['search', 'filter_type', 'search_date'];

    public function mount()
    {
        $this->search_date = Carbon::now()->format('Y-m-d');
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function cari()
    {
        // Simulation of API BPJS Fetching
        // In real implementation, this would call a Service that uses MstSettingApi credentials
        
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Menghubungkan ke API BPJS...']);
        
        // Mocking behavior: Let's create some dummy data if the table is empty or just for demo
        $this->mockApiData();
        
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data ditemukan dan diperbarui dari API BPJS!']);
    }

    private function mockApiData()
    {
        $names = ['Budi Santoso', 'Siti Aminah', 'Agus Prayogo', 'Dewi Lestari', 'Eko Saputra'];
        
        for ($i = 0; $i < 3; $i++) {
            $noKartu = '000' . rand(10000000, 99999999);
            $nik = '327' . rand(10000000000, 99999999999);
            
            TrxPasienBpjs::updateOrCreate(
                ['no_kartu' => $noKartu],
                [
                    'nik' => $nik,
                    'nama' => $names[array_rand($names)],
                    'pisa' => '1',
                    'sex' => rand(0, 1) ? 'L' : 'P',
                    'tgl_lahir' => Carbon::now()->subYears(rand(20, 60))->format('Y-m-d'),
                    'kd_provider' => '0114U055',
                    'nm_provider' => 'KLINIK DHARMA BAKTI',
                    'kd_cabang' => '0114',
                    'nm_cabang' => 'KCU BANDUNG',
                    'tgl_cetak_kartu' => Carbon::now()->subYears(2)->format('Y-m-d'),
                    'jns_kelas' => 'KELAS ' . rand(1, 3),
                    'jns_peserta' => 'PEGAWAI SWASTA',
                    'status_peserta' => 'AKTIF',
                ]
            );
        }
    }

    public function delete($id)
    {
        TrxPasienBpjs::find($id)->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data pasien berhasil dihapus dari penyimpanan lokal!']);
    }

    public function riwayat($id)
    {
        // Placeholder for history logic
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Fitur Riwayat Pasien sedang dalam pengembangan.']);
    }

    public function render()
    {
        $data = TrxPasienBpjs::where(function($query) {
                $query->where('nama', 'like', '%' . $this->search . '%')
                      ->orWhere('no_kartu', 'like', '%' . $this->search . '%')
                      ->orWhere('nik', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('bridging::livewire.data-pasien-bpjs-page', [
            'pasiens' => $data
        ]);
    }
}
