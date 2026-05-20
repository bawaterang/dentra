<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstKategoriGigi;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class GigiPage extends Component
{
    use WithPagination, DynamicKodeGenerator;

    public $gigiId;

    public $kode_kategori;

    public $nama_kategori;

    public $warna;

    public $deskripsi;

    public $status;

    public $totalGigi = 0;

    public $gigiAktif = 0;

    public $takAktif = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $kodeReadonly = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function gigis()
    {
        $query = MstKategoriGigi::withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_kategori', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_kategori', 'like', '%'.$this->search.'%')
                    ->orWhere('deskripsi', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('kode_kategori', 'asc')->paginate(10);
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
            'kode_kategori' => ['required', 'string', 'max:20', Rule::unique('mst_kategori_gigi', 'kode_kategori')->ignore($this->gigiId)],
            'nama_kategori' => 'required|string|max:100',
            'warna' => 'nullable|string|max:10',
            'deskripsi' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['gigiId', 'kode_kategori', 'nama_kategori', 'warna', 'deskripsi', 'isEdit']);
        $this->status = 'Aktif';
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_kategori_gigi', 'kode_kategori');
        if ($generated) {
            $this->kode_kategori = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstKategoriGigi::withTrashed()->findOrFail($id);
        $this->gigiId = $item->id;
        $this->kode_kategori = $item->kode_kategori;
        $this->nama_kategori = $item->nama_kategori;
        $this->warna = $item->warna;
        $this->deskripsi = $item->deskripsi;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $item = $this->gigiId
                ? MstKategoriGigi::withTrashed()->findOrFail($this->gigiId)
                : new MstKategoriGigi;

            if (! $this->gigiId && empty($this->kode_kategori)) {
                $this->kode_kategori = $this->generateDynamicKode('mst_kategori_gigi', 'kode_kategori');
            }

            $item->fill([
                'kode_kategori' => $this->kode_kategori,
                'nama_kategori' => $this->nama_kategori,
                'warna' => $this->warna,
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
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data kategori gigi berhasil diperbarui!' : 'Kategori gigi baru berhasil ditambahkan!']);
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
        $item = MstKategoriGigi::withTrashed()->findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status kategori gigi telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalGigi = MstKategoriGigi::withTrashed()->count();
        $this->gigiAktif = MstKategoriGigi::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstKategoriGigi::withTrashed()->where('status', 'Tidak Aktif')->count();

        return view('livewire.modules.master.gigi-page');
    }
}
