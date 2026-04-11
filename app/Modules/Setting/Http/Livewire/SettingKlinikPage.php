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

    public function selectSSOrg($id)
    {
        $this->organization_id = str_replace('http://sys-ids.kemkes.go.id/organization/', '', $id);
        $this->dispatch('close-modal', 'search-org-modal');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Organization ID dipilih! Silakan simpan.']);
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

    public function render()

    {
        return <<<'HTML'
        <div x-data="{ searchOrgModal: false }" @open-search-org-modal.window="searchOrgModal = true" @close-search-org-modal.window="searchOrgModal = false">

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon"><i class="ri-hospital-line"></i></div>
                    <h1>Informasi Klinik</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
                    <span class="sep">/</span>
                    <span>Pengaturan</span>
                    <span class="sep">/</span>
                    <span>Klinik</span>
                </div>
            </div>

            <div class="max-w-4xl mx-auto mt-6">
                <form wire:submit.prevent="save">
                    <div class="card overflow-hidden border-t-4 border-[#405189] shadow-lg rounded-xl">
                        <div class="p-6 border-b border-[#eff2f7] bg-gray-50/50 flex justify-between items-center">
                            <div>
                                <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                                    <i class="ri-hospital-fill text-[#405189]"></i> Profil Instansi
                                </h5>
                                <p class="text-sm text-[#878a99] mt-1">Kelola identitas dan informasi kontak rekam medis klinik Anda.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($organization_id)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold border border-emerald-200" title="Terhubung: {{ $organization_id }}">
                                        <i class="ri-checkbox-circle-fill text-sm"></i> SatuSehat Synced
                                    </span>
                                    <button type="button" wire:click="syncSatuSehat" class="btn btn-sm bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white shadow-sm transition-all" title="Update ke SatuSehat">
                                        <i class="ri-refresh-line"></i> Update
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 text-gray-500 text-xs font-bold border border-gray-200">
                                        <i class="ri-cloud-off-line text-sm"></i> Not Synced
                                    </span>
                                    <button type="button" wire:click="syncSatuSehat" class="btn btn-sm bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm transition-all" title="Daftarkan ke SatuSehat">
                                        <i class="ri-cloud-line"></i> Register
                                    </button>
                                @endif
                            </div>
                        </div>

                        
                        <div class="p-6 border-b border-[#eff2f7]">
                            <div class="flex flex-col sm:flex-row items-center gap-6">
                                <div class="h-24 w-24 shrink-0 rounded-xl bg-gray-50 border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden group">
                                    @if($logo_file)
                                        <img src="{{ $logo_file->temporaryUrl() }}" class="w-full h-full object-cover">
                                    @elseif($logo)
                                        <img src="{{ asset('storage/'.$logo) }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="ri-image-add-line text-3xl text-gray-400 group-hover:text-[#405189] transition-colors"></i>
                                    @endif
                                    <label class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                        <i class="ri-camera-switch-line text-white text-xl"></i>
                                        <input type="file" wire:model="logo_file" class="hidden" accept="image/*">
                                    </label>
                                </div>
                                <div class="text-center sm:text-left">
                                    <h6 class="text-sm font-bold text-gray-700 mb-1">Logo Klinik</h6>
                                    <p class="text-xs text-gray-500 mb-2">Format yang didukung: JPG, PNG, atau GIF. Ukuran maksimal 2MB.</p>
                                    @error('logo_file') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Klinik</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-hospital-line text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="nama_klinik" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all font-semibold">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Pimpinan / Penanggung Jawab</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-user-star-line text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="pimpinan" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Telepon Utama</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-phone-fill text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="telepon" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Resmi</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-mail-fill text-gray-400"></i>
                                            </div>
                                            <input type="email" wire:model="email" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Website URL</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-global-line text-gray-400"></i>
                                            </div>
                                            <input type="text" wire:model="website" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Organization ID Fasyankes (Kemenkes)</label>
                                        <div class="flex gap-2">
                                            <div class="relative flex-1">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="ri-qr-code-line text-gray-400"></i>
                                                </div>
                                                <input type="text" wire:model="organization_id" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all bg-yellow-50" placeholder="Cth: R220001">
                                            </div>
                                            <button type="button" 
                                                    @click="$dispatch('open-search-org-modal')" 
                                                    class="btn bg-indigo-50 text-indigo-600 px-4 rounded-lg font-bold hover:bg-indigo-600 hover:text-white transition-colors"
                                                    title="Cari ID di SatuSehat">

                                                <i class="ri-search-line"></i> Cari
                                            </button>
                                        </div>
                                        <p class="text-[10px] text-gray-500 mt-1">Wajib diisi untuk registrasi poli/departemen ke SatuSehat.</p>
                                    </div>
                                </div>
                                <div class="space-y-4 flex flex-col justify-between">

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap</label>
                                        <textarea wire:model="alamat" class="w-full rounded-lg border-gray-200 text-sm p-3 h-20 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all resize-none shadow-sm"></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Provinsi</label>
                                            <x-custom-dropdown 
                                                model="provinsi_id" 
                                                :options="$this->provinsiOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                                placeholder="Pilih Provinsi"
                                                searchable="true"
                                                live="true"
                                            />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Kabupaten</label>
                                            <x-custom-dropdown 
                                                model="kabupaten_id" 
                                                :options="$this->kabupatenOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                                placeholder="Pilih Kabupaten"
                                                searchable="true"
                                                live="true"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Kecamatan</label>
                                            <x-custom-dropdown 
                                                model="kecamatan_id" 
                                                :options="$this->kecamatanOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                                placeholder="Pilih Kecamatan"
                                                searchable="true"
                                                live="true"
                                            />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Kelurahan</label>
                                            <x-custom-dropdown 
                                                model="kelurahan_id" 
                                                :options="$this->kelurahanOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                                placeholder="Pilih Kelurahan"
                                                searchable="true"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 mt-3">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest px-1">Kode Pos</label>
                                            <input type="text" wire:model="kode_pos" 
                                                   class="w-full bg-white border border-gray-200 rounded-lg py-2 px-3 text-sm focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all" 
                                                   placeholder="Kode Pos">
                                        </div>
                                    </div>
                                    <div class="mt-4 p-4 border border-blue-100 bg-blue-50/50 rounded-xl">

                                        <div class="flex items-start gap-3">
                                            <i class="ri-information-fill text-[#405189] mt-0.5"></i>
                                            <div>
                                                <h6 class="text-sm font-bold text-[#405189]">Info Update</h6>
                                                <p class="text-xs text-[#878a99] mt-1">Informasi ini akan ditampilkan pada kop surat, struk apotek, dan tiket antrian.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-5 bg-gray-50 flex justify-end gap-3 border-t border-[#eff2f7]">
                            <button type="button" wire:click="mount" class="btn bg-gray-500 text-white font-bold text-sm px-6 hover:bg-gray-600 hover:-translate-y-0.5 transition-all">Reset</button>
                            <button type="submit" class="btn bg-[#0d6efd] text-white font-bold text-sm px-8 shadow-md hover:bg-[#0b5ed7] hover:-translate-y-0.5 transition-all"><i class="ri-check-double-line mr-1"></i> Simpan Pengaturan Klinik</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Search Organization Modal -->
            <div x-show="searchOrgModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="searchOrgModal = false"></div>
                <div x-show="searchOrgModal" x-transition.scale.95 class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden p-6 relative z-10">
                    <div class="absolute top-4 right-4">
                        <button type="button" @click="searchOrgModal = false" class="text-gray-400 hover:text-rose-500 transition-colors">
                            <i class="ri-close-circle-fill text-2xl"></i>
                        </button>
                    </div>

                    <h3 class="text-xl font-bold text-[#405189] mb-1">Cari Organization SatuSehat</h3>
                    <p class="text-sm text-gray-500 mb-6">Mencari data instansi berdasarkan nama yang terdaftar di Kemenkes.</p>

                    <form wire:submit.prevent="searchSatuSehatOrg" class="flex gap-2 mb-6">
                        <input type="text" wire:model="searchOrgQuery" class="flex-1 rounded-xl border-gray-200 text-sm py-2 px-4 focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 placeholder:text-gray-400 font-medium" placeholder="Masukkan nama instansi / puskemas...">
                        <button type="submit" class="bg-[#405189] text-white px-5 rounded-xl text-sm font-bold shadow-sm hover:bg-indigo-600 transition-colors">
                            <span wire:loading.remove wire:target="searchSatuSehatOrg">Cari</span>
                            <span wire:loading wire:target="searchSatuSehatOrg"><i class="ri-loader-4-line animate-spin"></i></span>
                        </button>
                    </form>

                    @if(!empty($foundOrganizations))
                    <div class="max-h-80 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-gray-200">
                        @foreach($foundOrganizations as $org)
                            @php
                                $resource = $org['resource'];
                                $kemenkesId = '';
                                foreach ($resource['identifier'] ?? [] as $identifier) {
                                    $sys = $identifier['system'] ?? '';
                                    if (strpos($sys, 'sys-ids.kemkes.go.id/organization') !== false) {
                                        $extracted = trim(str_replace('http://sys-ids.kemkes.go.id/organization/', '', $sys));
                                        if (!empty($extracted)) {
                                            $kemenkesId = $extracted;
                                        } else {
                                            $kemenkesId = $identifier['value'] ?? '';
                                        }
                                        break;
                                    }
                                }
                                if(empty($kemenkesId)) $kemenkesId = 'Tidak ada ID';
                            @endphp
                            <div class="border border-gray-200 rounded-xl p-4 flex items-center justify-between hover:border-indigo-300 hover:bg-indigo-50/50 transition-colors group">
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm group-hover:text-[#405189] transition-colors">{{ $resource['name'] ?? 'No Name' }}</h4>
                                    <div class="flex gap-3 mt-1.5 text-xs text-gray-500">
                                        <span><i class="ri-qr-code-line mr-1"></i> ID: {{ $resource['id'] ?? '' }}</span>
                                    </div>
                                </div>
                                <button type="button" 
                                        wire:click="selectSSOrg('{{ $resource['id'] }}')" 
                                        @click="searchOrgModal = false"
                                        class="btn btn-sm bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white rounded-lg shrink-0">
                                    Pilih
                                </button>
                            </div>
                        @endforeach

                    </div>
                    @endif
                </div>
            </div>


        </div>

        HTML;
    }
}
