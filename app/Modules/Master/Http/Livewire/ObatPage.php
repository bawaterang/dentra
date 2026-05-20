<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstObat;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ObatPage extends Component
{
    use WithPagination, DynamicKodeGenerator;

    public $obatId;

    public $kode_obat;

    public $nama_obat;

    public $satuan;

    public $stok;

    public $stok_minimal;

    public $harga_beli;

    public $harga_jual;

    public $tanggal_beli;

    public $tanggal_expired;

    public $keterangan;

    public $status;

    public $totalObat = 0;

    public $obatAktif = 0;

    public $takAktif = 0;

    public $stokHabis = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $kodeReadonly = false;

    // KFA Properties
    public $searchKfaKeyword = '';
    public $kfaResults = [];
    public $mappingObatId = null;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function obats()
    {
        $query = MstObat::with(['kfaMapping.kfaObat'])->withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_obat', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_obat', 'like', '%'.$this->search.'%')
                    ->orWhere('satuan', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('kode_obat', 'asc')->paginate(10);
    }

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'kode_obat' => ['required', 'string', 'max:20', Rule::unique('mst_obat', 'kode_obat')->ignore($this->obatId)],
            'nama_obat' => 'required|string|max:100',
            'satuan' => 'nullable|string|max:20',
            'stok' => 'nullable|integer|min:0',
            'stok_minimal' => 'nullable|integer|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'harga_jual' => 'nullable|numeric|min:0',
            'tanggal_beli' => 'nullable|date',
            'tanggal_expired' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['obatId', 'kode_obat', 'nama_obat', 'satuan', 'stok', 'stok_minimal', 'harga_beli', 'harga_jual', 'tanggal_beli', 'tanggal_expired', 'keterangan', 'isEdit']);
        $this->status = 'Aktif';
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_obat', 'kode_obat');
        if ($generated) {
            $this->kode_obat = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstObat::withTrashed()->findOrFail($id);
        $this->obatId = $item->id;
        $this->kode_obat = $item->kode_obat;
        $this->nama_obat = $item->nama_obat;
        $this->satuan = $item->satuan;
        $this->stok = $item->stok;
        $this->stok_minimal = $item->stok_minimal;
        $this->harga_beli = $item->harga_beli;
        $this->harga_jual = $item->harga_jual;
        $this->tanggal_beli = $item->tanggal_beli ? $item->tanggal_beli->format('Y-m-d') : null;
        $this->tanggal_expired = $item->tanggal_expired ? $item->tanggal_expired->format('Y-m-d') : null;
        $this->keterangan = $item->keterangan;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $item = $this->obatId
                ? MstObat::withTrashed()->findOrFail($this->obatId)
                : new MstObat;

            if (! $this->obatId && empty($this->kode_obat)) {
                $this->kode_obat = $this->generateDynamicKode('mst_obat', 'kode_obat');
            }

            $item->fill([
                'kode_obat' => $this->kode_obat,
                'nama_obat' => $this->nama_obat,
                'satuan' => $this->satuan,
                'stok' => $this->stok,
                'stok_minimal' => $this->stok_minimal,
                'harga_beli' => $this->harga_beli,
                'harga_jual' => $this->harga_jual,
                'tanggal_beli' => $this->tanggal_beli,
                'tanggal_expired' => $this->tanggal_expired,
                'keterangan' => $this->keterangan,
                'status' => $this->status ?? 'Aktif',
            ]);
            $item->save();

            if ($this->status === 'Aktif' && $item->trashed()) {
                $item->restore();
            } elseif (in_array($this->status, ['Tidak Aktif', 'Stok Habis']) && ! $item->trashed()) {
                $item->delete();
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data obat berhasil diperbarui!' : 'Obat baru berhasil ditambahkan!']);
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
        $item = MstObat::withTrashed()->findOrFail($id);
        if (in_array($item->status, ['Tidak Aktif', 'Stok Habis'])) {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status '.$item->status.' tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status obat telah diubah menjadi Tidak Aktif!']);
    }

    public function openKfaModal($obatId)
    {
        $this->mappingObatId = $obatId;
        $this->searchKfaKeyword = '';
        $this->kfaResults = [];
        $this->dispatch('open-modal', 'kfa-modal');
    }

    public function searchKfaObat()
    {
        if (empty($this->searchKfaKeyword)) {
            $this->dispatch('alert', ['type' => 'warning', 'message' => 'Masukkan kata kunci pencarian!']);
            return;
        }

        try {
            $service = new \App\Modules\Bridging\Services\SatuSehatService();
            $result = $service->searchKfaProduct($this->searchKfaKeyword, 1, 100);
            
            $this->kfaResults = $result['items']['data'] ?? $result['data'] ?? [];
            if (empty($this->kfaResults)) {
                $this->dispatch('alert', ['type' => 'warning', 'message' => 'Obat tidak ditemukan di KFA.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal mencari obat KFA: ' . $e->getMessage()]);
        }
    }

    public function selectKfaMapping($kfaData)
    {
        if (!$this->mappingObatId) return;

        try {
            $service = new \App\Modules\Bridging\Services\SatuSehatService();
            // Simpan master KFA terlebih dahulu
            $kfaObat = $service->syncKfaProduct($kfaData);

            // Simpan relasinya
            \App\Models\MstMapObatKfa::updateOrCreate(
                ['obat_id' => $this->mappingObatId],
                ['kfa_code' => $kfaObat->kfa_code, 'is_active' => true]
            );

            $this->dispatch('close-modal', 'kfa-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Mapping obat KFA berhasil disimpan!']);
            
            $this->mappingObatId = null;
            $this->resetPage(); // Trigger re-render
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal menyimpan mapping KFA: ' . $e->getMessage()]);
        }
    }

    public function removeKfaMapping($obatId)
    {
        \App\Models\MstMapObatKfa::where('obat_id', $obatId)->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Mapping obat KFA berhasil dihapus!']);
    }

    public function render()
    {
        $this->totalObat = MstObat::withTrashed()->count();
        $this->obatAktif = MstObat::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstObat::withTrashed()->where('status', 'Tidak Aktif')->count();
        $this->stokHabis = MstObat::withTrashed()->where('status', 'Stok Habis')->count();

        return view('livewire.modules.master.obat-page');
    }
}
