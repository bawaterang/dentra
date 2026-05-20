<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\MstInstansi;
use Livewire\Attributes\Computed;
use App\Modules\Bridging\Services\SatuSehatService;

class SettingKlinikPage extends Component

{
    use WithFileUploads;

    public $nama_klinik;
    public $alamat;
    public $telepon;
    public $email;
    public $website;
    public $pimpinan;
    public $logo;
    public $logo_file;

    // SatuSehat Organization Fields
    public $organization_id;
    public $kode_pos;
    public $provinsi_id;
    public $kabupaten_id;
    public $kecamatan_id;
    public $kelurahan_id;

    // Search SS Organization Modal
    public $searchOrgQuery = '';
    public $foundOrganizations = [];

    // Location Fields
    public $activeTab = 'profil';
    public $locations = [];
    public $locationId, $location_name, $location_description, $longitude, $latitude, $loc_status, $ss_location_id;

    public $isEditLoc = false;
    public $searchLocQuery = '';
    public $searchLocFilter = 'name'; // Options: name, organization, id
    public $foundLocations = [];





    public function mount()
    {
        $instansi = MstInstansi::first();
        if ($instansi) {
            $this->nama_klinik = $instansi->nama_instansi;
            $this->alamat = $instansi->alamat;
            $this->telepon = $instansi->telepon;
            $this->email = $instansi->email;
            $this->website = $instansi->website;
            $this->pimpinan = $instansi->pimpinan;
            $this->logo = $instansi->logo;
            $this->organization_id = $instansi->organization_id;
            $this->kode_pos = $instansi->kode_pos;
            $this->provinsi_id = $instansi->provinsi_id;
            $this->kabupaten_id = $instansi->kabupaten_id;
            $this->kecamatan_id = $instansi->kecamatan_id;
            $this->kelurahan_id = $instansi->kelurahan_id;
        } else {
            // Defaults
            $this->nama_klinik = 'SIGI Dental Clinic';
            $this->email = 'info@sigidental.id';
        }

        $this->loadLocations();
    }

    public function loadLocations()
    {
        $this->locations = \App\Models\MstLocation::orderBy('location_name', 'asc')->get();
    }

    public function saveLoc()
    {
        $this->validate([
            'location_name' => 'required|string|max:100',
            'ss_location_id' => 'required|string',
        ]);

        try {
            \App\Models\MstLocation::updateOrCreate(
                ['id' => $this->locationId],
                [
                    'organization_id' => $this->organization_id,
                    'location_id' => $this->ss_location_id,
                    'location_name' => $this->location_name,
                    'description' => $this->location_description,
                    'longitude' => $this->longitude,
                    'latitude' => $this->latitude,
                    'status' => $this->loc_status ?: 'active',
                ]
            );

            $this->loadLocations();
            $this->dispatch('close-modal', 'location-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Data lokasi berhasil disimpan!']);
            $this->resetLocFields();
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal menyimpan lokasi: ' . $e->getMessage()]);
        }
    }

    private function resetLocFields()
    {
        $this->reset([
            'locationId', 'location_name', 'location_description', 
            'longitude', 'latitude', 'loc_status', 'ss_location_id', 'isEditLoc'
        ]);
    }



    #[Computed]
    public function provinsiOptions()
    {
        return \App\Models\MstWilayahProvinsi::orderBy('nama')->get();
    }

    #[Computed]
    public function kabupatenOptions()
    {
        return $this->provinsi_id 
            ? \App\Models\MstWilayahKabupaten::where('provinsi_kode', $this->provinsi_id)->orderBy('nama')->get() 
            : collect([]);
    }

    #[Computed]
    public function kecamatanOptions()
    {
        return $this->kabupaten_id 
            ? \App\Models\MstWilayahKecamatan::where('kabupaten_kode', $this->kabupaten_id)->orderBy('nama')->get() 
            : collect([]);
    }

    #[Computed]
    public function kelurahanOptions()
    {
        return $this->kecamatan_id 
            ? \App\Models\MstWilayahKelurahan::where('kecamatan_kode', $this->kecamatan_id)->orderBy('nama')->get() 
            : collect([]);
    }

    public function updatedProvinsiId()
    {
        $this->reset(['kabupaten_id', 'kecamatan_id', 'kelurahan_id']);
    }

    public function updatedKabupatenId()
    {
        $this->reset(['kecamatan_id', 'kelurahan_id']);
    }

    public function updatedKecamatanId()
    {
        $this->reset(['kelurahan_id']);
    }



    public function rules()
    {
        return [
            'nama_klinik' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'website' => 'nullable|string|max:255',
            'pimpinan' => 'nullable|string|max:255',
            'logo_file' => 'nullable|image|max:2048',
            'organization_id' => 'nullable|string',
            'kode_pos' => 'nullable|string|max:10',
            'provinsi_id' => 'nullable|exists:mst_wilayah_provinsi,kode',
            'kabupaten_id' => 'nullable|exists:mst_wilayah_kabupaten,kode',
            'kecamatan_id' => 'nullable|exists:mst_wilayah_kecamatan,kode',
            'kelurahan_id' => 'nullable|exists:mst_wilayah_kelurahan,kode',
        ];
    }


    public function save()
    {
        $this->validate();
        
        $instansi = MstInstansi::first() ?? new MstInstansi();
        $instansi->nama_instansi = $this->nama_klinik;
        $instansi->alamat = $this->alamat;
        $instansi->telepon = $this->telepon;
        $instansi->email = $this->email;
        $instansi->website = $this->website;
        $instansi->pimpinan = $this->pimpinan;
        $instansi->organization_id = $this->organization_id;
        $instansi->kode_pos = $this->kode_pos;
        $instansi->provinsi_id = $this->provinsi_id;
        $instansi->kabupaten_id = $this->kabupaten_id;
        $instansi->kecamatan_id = $this->kecamatan_id;
        $instansi->kelurahan_id = $this->kelurahan_id;


        if ($this->logo_file) {
            if ($instansi->logo) {
                Storage::disk('public')->delete($instansi->logo);
            }
            $this->logo = $this->logo_file->store('logos', 'public');
            $instansi->logo = $this->logo;
        }

        $instansi->save();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Informasi klinik berhasil diperbarui!']);
    }

    public function openSearchSSOrg()
    {
        $this->reset(['searchOrgQuery', 'foundOrganizations']);
        $this->dispatch('open-modal', 'search-org-modal');
    }

    public function searchSatuSehatOrg()

    {
        if (empty($this->searchOrgQuery)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Masukkan kata kunci pencarian!']);
            return;
        }

        try {
            $service = new SatuSehatService();
            $results = $service->searchOrganization($this->searchOrgQuery);
            $this->foundOrganizations = $results ?: [];

            if (empty($this->foundOrganizations)) {
                $this->dispatch('alert', ['type' => 'warning', 'message' => 'Organisasi tidak ditemukan di SatuSehat.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal mencari Organisasi: ' . $e->getMessage()]);
        }
    }

    public function selectSSOrg($id, $name)
    {
        $this->organization_id = str_replace('http://sys-ids.kemkes.go.id/organization/', '', $id);
        $this->nama_klinik = $name;
        $this->reset(['searchOrgQuery', 'foundOrganizations']);
        $this->dispatch('close-modal', 'search-org-modal');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Organization ID dan Nama Klinik berhasil diperbarui! Silakan simpan.']);
    }


    public function syncSatuSehat()
    {
        $instansi = MstInstansi::first();
        if (!$instansi) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Silakan simpan profil klinik terlebih dahulu sebelum sinkronisasi.']);
            return;
        }

        try {
            $service = new SatuSehatService();
            if ($instansi->organization_id) {
                // Update
                $service->updateOrganization($instansi);
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Berhasil memperbarui data Organization di SatuSehat.']);
            } else {
                // Create
                $service->createOrganization($instansi);
                $this->organization_id = $instansi->fresh()->organization_id;
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Berhasil mendaftarkan Organization ke SatuSehat.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Sync Error: ' . $e->getMessage()]);
        }
    }

    // --- Location Methods ---

    public function resetLocForm()
    {
        $this->reset(['locationId', 'location_name', 'location_description', 'longitude', 'latitude', 'isEditLoc']);

        $this->loc_status = 'active';
        $this->resetErrorBag();
    }

    public function createLoc()
    {
        $this->resetLocForm();
        $this->dispatch('open-modal', 'location-modal');
    }


    public function editLoc($id)
    {
        $this->resetLocForm();
        $loc = \App\Models\MstLocation::findOrFail($id);
        $this->locationId = $loc->id;
        $this->ss_location_id = $loc->location_id;
        $this->location_name = $loc->location_name;
        $this->location_description = $loc->description;
        $this->longitude = $loc->longitude;
        $this->latitude = $loc->latitude;
        $this->loc_status = $loc->status;
        $this->isEditLoc = true;
        $this->dispatch('open-modal', 'location-modal');
    }


    public function deleteLoc($id)

    {
        \App\Models\MstLocation::destroy($id);
        $this->loadLocations();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Lokasi dihapus.']);
    }

    public function syncLocToSS($id)
    {
        try {
            $loc = \App\Models\MstLocation::findOrFail($id);
            $service = new SatuSehatService();
            
            if ($loc->location_id) {
                $service->updateLocation($loc);
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Location updated in SatuSehat.']);
            } else {
                $service->createLocation($loc);
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Location registered to SatuSehat!']);
            }
            $this->loadLocations();
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Sync Error: ' . $e->getMessage()]);
        }
    }

    public function openSearchSSLoc()
    {
        $this->reset(['searchLocQuery', 'foundLocations', 'searchLocFilter']);
        $this->dispatch('open-modal', 'search-loc-modal');
    }

    public function searchSSLocation()

    {
        if (empty($this->searchLocQuery)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Masukkan kata kunci pencarian!']);
            return;
        }

        try {
            $service = new SatuSehatService();
            $results = null;

            switch ($this->searchLocFilter) {
                case 'name':
                    $results = $service->searchLocationByName($this->searchLocQuery);
                    break;
                case 'organization':
                    $results = $service->searchLocationByOrganization($this->searchLocQuery);
                    break;
                case 'id':
                    $results = $service->searchLocationByIDLocation($this->searchLocQuery);
                    break;
                default:
                    $results = $service->searchLocationByName($this->searchLocQuery);
                    break;
            }

            $this->foundLocations = $results ?: [];

            if (empty($this->foundLocations)) {
                $this->dispatch('alert', ['type' => 'warning', 'message' => 'Lokasi tidak ditemukan di SatuSehat.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal mencari Lokasi: ' . $e->getMessage()]);
        }
    }



    public function selectSSLoc($data)
    {
        $this->ss_location_id = $data['id'];
        $this->location_name = $data['name'];
        $this->location_description = $data['description'] ?? '';
        $this->latitude = $data['position']['latitude'] ?? '';
        $this->longitude = $data['position']['longitude'] ??'';
        
        $this->reset(['searchLocQuery', 'foundLocations']);

        $this->dispatch('close-modal', 'search-loc-modal');
        $this->dispatch('open-modal', 'location-modal'); // Open the form modal
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data lokasi dari SatuSehat dimuat ke form!']);
    }



    public function render()

    {
        return view('livewire.modules.setting.setting-klinik-page');
    }

}
