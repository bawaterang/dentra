<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstBmhp;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class BmhpPage extends Component
{
    use WithPagination, DynamicKodeGenerator;

    public $bmhpId;

    public $kode_bmhp;

    public $nama_bmhp;

    public $satuan;

    public $stok;

    public $stok_minimal;

    public $harga_satuan;

    public $keterangan;

    public $status;

    public $totalBmhp = 0;

    public $bmhpAktif = 0;

    public $takAktif = 0;

    public $stokHabis = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $kodeReadonly = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function bmhps()
    {
        $query = MstBmhp::withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_bmhp', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_bmhp', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('kode_bmhp', 'asc')->paginate(10);
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
            'kode_bmhp' => ['required', 'string', 'max:20', Rule::unique('mst_bmhp', 'kode_bmhp')->ignore($this->bmhpId)],
            'nama_bmhp' => 'required|string|max:100',
            'satuan' => 'nullable|string|max:20',
            'stok' => 'nullable|integer|min:0',
            'stok_minimal' => 'nullable|integer|min:0',
            'harga_satuan' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['bmhpId', 'kode_bmhp', 'nama_bmhp', 'satuan', 'stok', 'stok_minimal', 'harga_satuan', 'keterangan', 'isEdit']);
        $this->status = 'Aktif';
        $this->stok = 0;
        $this->stok_minimal = 5;
        $this->harga_satuan = 0;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_bmhp', 'kode_bmhp');
        if ($generated) {
            $this->kode_bmhp = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstBmhp::withTrashed()->findOrFail($id);
        $this->bmhpId = $item->id;
        $this->kode_bmhp = $item->kode_bmhp;
        $this->nama_bmhp = $item->nama_bmhp;
        $this->satuan = $item->satuan;
        $this->stok = $item->stok;
        $this->stok_minimal = $item->stok_minimal;
        $this->harga_satuan = $item->harga_satuan;
        $this->keterangan = $item->keterangan;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $rules = $this->rules();
            if (! $this->bmhpId) {
                unset($rules['kode_bmhp']);
            }
            $this->validate($rules);

            $attempts = 0;
            $success = false;

            while (! $success && $attempts < 5) {
                try {
                    $item = $this->bmhpId ? MstBmhp::withTrashed()->findOrFail($this->bmhpId) : new MstBmhp;

                    if (! $this->bmhpId && empty($this->kode_bmhp)) {
                        $this->kode_bmhp = $this->generateDynamicKode('mst_bmhp', 'kode_bmhp');
                    }

                    $item->fill([
                        'kode_bmhp' => $this->kode_bmhp,
                        'nama_bmhp' => $this->nama_bmhp,
                        'satuan' => $this->satuan,
                        'stok' => $this->stok,
                        'stok_minimal' => $this->stok_minimal,
                        'harga_satuan' => $this->harga_satuan,
                        'keterangan' => $this->keterangan,
                        'status' => $this->status ?? 'Aktif',
                    ]);
                    $item->save();

                    if ($this->status === 'Aktif' && $item->trashed()) {
                        $item->restore();
                    } elseif (in_array($this->status, ['Tidak Aktif', 'Stok Habis']) && ! $item->trashed()) {
                        $item->delete();
                    }

                    $success = true;
                } catch (QueryException $e) {
                    if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'kode_bmhp')) {
                        if (! $this->bmhpId) {
                            $attempts++;
                            $this->kode_bmhp = $this->generateDynamicKode('mst_bmhp', 'kode_bmhp');

                            continue;
                        }
                    }
                    throw $e;
                }
            }

            if (! $success) {
                throw new \Exception('Gagal menghasilkan kode unik.');
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data BMHP berhasil diperbarui!' : 'BMHP baru berhasil ditambahkan!']);
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
        $item = MstBmhp::withTrashed()->findOrFail($id);
        if (in_array($item->status, ['Tidak Aktif', 'Stok Habis'])) {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status '.$item->status.' tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status BMHP telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalBmhp = MstBmhp::withTrashed()->count();
        $this->bmhpAktif = MstBmhp::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstBmhp::withTrashed()->where('status', 'Tidak Aktif')->count();
        $this->stokHabis = MstBmhp::withTrashed()->where('status', 'Stok Habis')->count();

        return view('livewire.modules.master.bmhp-page');
    }
}
