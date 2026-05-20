<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstPoli;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PoliPage extends Component
{
    use WithPagination, DynamicKodeGenerator;

    public $poliId;

    public $kode_poli;

    public $nama_poli;

    public $poli_bpjs_id;

    public $prefix_antrian;

    public $status;

    public $totalPoli = 0;

    public $poliAktif = 0;

    public $takAktif = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $kodeReadonly = false;
    public $activeTab = 'polis'; // 'polis' or 'mapping'
    public $selectedPoliId = '';
    public $allPolisMapping = [];
    public $allDokters = [];
    public $mappedDokters = [];

    // BPJS Search
    public $searchBpjsPoliQuery = '';
    public $foundBpjsPolis = [];

    protected $queryString = ['search', 'selectedStatus', 'activeTab'];

    #[Computed]
    public function polis()
    {
        $query = MstPoli::query();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_poli', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_poli', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('kode_poli', 'asc')->paginate(10);
    }

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->resetPage();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedSelectedPoliId($value)
    {
        if ($value) {
            $poli = MstPoli::with('dokters')->find($value);
            if ($poli && $poli->dokters) {
                $this->mappedDokters = $poli->dokters->pluck('id')->map(fn($id) => (string) $id)->toArray();
            } else {
                $this->mappedDokters = [];
            }
        } else {
            $this->mappedDokters = [];
        }
    }

    public function saveMapping()
    {
        if (!$this->selectedPoliId) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Silakan pilih Poli terlebih dahulu!']);
            return;
        }

        try {
            $poli = MstPoli::findOrFail($this->selectedPoliId);
            $poli->dokters()->sync($this->mappedDokters ?? []);
            
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Pemetaan Poli ke Dokter berhasil disimpan!']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Pemetaan Gagal: ' . $e->getMessage()]);
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'kode_poli' => ['required', 'string', 'max:20', Rule::unique('mst_poli', 'kode_poli')->ignore($this->poliId)],
            'nama_poli' => 'required|string|max:100',
            'poli_bpjs_id' => 'nullable|string|max:50',
            'prefix_antrian' => 'nullable|string|max:5',
        ];
    }

    public function resetForm()
    {
        $this->reset(['poliId', 'kode_poli', 'nama_poli', 'poli_bpjs_id', 'prefix_antrian', 'isEdit']);
        $this->status = 'Aktif';
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_poli', 'kode_poli');
        if ($generated) {
            $this->kode_poli = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstPoli::findOrFail($id);
        $this->poliId = $item->id;
        $this->kode_poli = $item->kode_poli;
        $this->nama_poli = $item->nama_poli;
        $this->poli_bpjs_id = $item->poli_bpjs_id;
        $this->prefix_antrian = $item->prefix_antrian;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());
            $item = $this->poliId ? MstPoli::findOrFail($this->poliId) : new MstPoli;
            $item->fill([
                'kode_poli' => $this->kode_poli, 
                'nama_poli' => $this->nama_poli, 
                'poli_bpjs_id' => $this->poli_bpjs_id,
                'prefix_antrian' => $this->prefix_antrian ? strtoupper($this->prefix_antrian) : null,
                'status' => $this->status ?? 'Aktif'
            ]);
            $item->save();
            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data poli berhasil diperbarui!' : 'Poli baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: '.$e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $item = MstPoli::findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Poli sudah tidak aktif.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status poli diubah menjadi Tidak Aktif!']);
    }

    public function searchBpjsPoli()
    {
        try {
            $service = new \App\Modules\Bridging\Services\BpjsPcareService();
            $response = $service->getPoli(0, 100);
            
            if ($response['success']) {
                $data = $response['data'] ?? [];
                $polis = $data['list'] ?? $data;
                
                if (!empty($this->searchBpjsPoliQuery)) {
                    $polis = array_filter($polis, function($p) {
                        return str_contains(strtolower($p['nmPoli'] ?? ''), strtolower($this->searchBpjsPoliQuery)) ||
                               str_contains(strtolower($p['kdPoli'] ?? ''), strtolower($this->searchBpjsPoliQuery));
                    });
                }
                
                $this->foundBpjsPolis = $polis;
                $this->dispatch('open-search-bpjs-poli-modal');
            } else {
                $msg = $response['metadata']['message'] ?? 'Gagal mengambil data dari BPJS.';
                $this->dispatch('alert', ['type' => 'error', 'message' => $msg]);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal mencari Poli BPJS: ' . $e->getMessage()]);
        }
    }

    public function selectBpjsPoli($id)
    {
        $this->poli_bpjs_id = $id;
        $this->dispatch('close-search-bpjs-poli-modal');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Kode Poli BPJS dipilih!']);
    }

    public function render()
    {
        $this->totalPoli = MstPoli::count();
        $this->poliAktif = MstPoli::where('status', 'Aktif')->count();
        $this->takAktif = MstPoli::where('status', 'Tidak Aktif')->count();

        // For Mapping Tab
        $this->allPolisMapping = MstPoli::where('status', 'Aktif')->orderBy('nama_poli')->get();
        $this->allDokters = \App\Models\MstDokter::where('status', 'Aktif')->orderBy('nama_dokter')->get();

        return view('livewire.modules.master.poli-page');
    }
}
