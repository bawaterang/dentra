<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstTindakan;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TindakanPage extends Component
{
    use WithPagination, DynamicKodeGenerator;

    public $tindakanId;

    public $kode_tindakan;

    public $nama_tindakan;

    public $kategori_tindakan;

    public $icd9cm_code;

    public $icd9cm_name;

    public $snomed_code;

    public $snomed_name;

    public $harga_default;

    public $deskripsi;

    public $status;

    public $totalTindakan = 0;

    public $tindakanAktif = 0;

    public $takAktif = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $kodeReadonly = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function tindakans()
    {
        $query = MstTindakan::withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_tindakan', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_tindakan', 'like', '%'.$this->search.'%')
                    ->orWhere('kategori_tindakan', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('kode_tindakan', 'asc')->paginate(10);
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
            'kode_tindakan' => ['required', 'string', 'max:20', Rule::unique('mst_tindakan', 'kode_tindakan')->ignore($this->tindakanId)],
            'nama_tindakan' => 'required|string|max:200',
            'kategori_tindakan' => 'nullable|string|max:100',
            'harga_default' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['tindakanId', 'kode_tindakan', 'nama_tindakan', 'kategori_tindakan', 'icd9cm_code', 'icd9cm_name', 'snomed_code', 'snomed_name', 'harga_default', 'deskripsi', 'isEdit']);
        $this->status = 'Aktif';
        $this->harga_default = 0;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_tindakan', 'kode_tindakan');
        if ($generated) {
            $this->kode_tindakan = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstTindakan::withTrashed()->findOrFail($id);
        $this->tindakanId = $item->id;
        $this->kode_tindakan = $item->kode_tindakan;
        $this->nama_tindakan = $item->nama_tindakan;
        $this->kategori_tindakan = $item->kategori_tindakan;
        $this->icd9cm_code = $item->icd9cm_code;
        $this->icd9cm_name = $item->icd9cm_name;
        $this->snomed_code = $item->snomed_code;
        $this->snomed_name = $item->snomed_name;
        $this->harga_default = $item->harga_default;
        $this->deskripsi = $item->deskripsi;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $item = $this->tindakanId
                ? MstTindakan::withTrashed()->findOrFail($this->tindakanId)
                : new MstTindakan;

            if (! $this->tindakanId && empty($this->kode_tindakan)) {
                $this->kode_tindakan = $this->generateDynamicKode('mst_tindakan', 'kode_tindakan');
            }

            $item->fill([
                'kode_tindakan' => $this->kode_tindakan,
                'nama_tindakan' => $this->nama_tindakan,
                'kategori_tindakan' => $this->kategori_tindakan,
                'icd9cm_code' => $this->icd9cm_code,
                'icd9cm_name' => $this->icd9cm_name,
                'snomed_code' => $this->snomed_code,
                'snomed_name' => $this->snomed_name,
                'harga_default' => $this->harga_default,
                'deskripsi' => $this->deskripsi,
                'status' => $this->status ?? 'Aktif',
            ]);
            $item->save();

            if ($this->status === 'Aktif' && $item->trashed()) {
                $item->restore();
            } elseif ($this->status === 'Tidak Aktif' && ! $item->trashed()) {
                $item->delete();
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data tindakan berhasil diperbarui!' : 'Tindakan baru berhasil ditambahkan!']);
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
        $item = MstTindakan::withTrashed()->findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status tindakan telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalTindakan = MstTindakan::withTrashed()->count();
        $this->tindakanAktif = MstTindakan::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstTindakan::withTrashed()->where('status', 'Tidak Aktif')->count();

        return view('livewire.modules.master.tindakan-page');
    }
}
