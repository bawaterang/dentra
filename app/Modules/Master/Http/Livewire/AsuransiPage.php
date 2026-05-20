<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstAsuransi;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AsuransiPage extends Component
{
    use WithPagination, DynamicKodeGenerator;

    public $asuransiId;

    public $kode_asuransi;

    public $nama_asuransi;

    public $tipe_asuransi;

    public $diskon;

    public $no_telepon;

    public $email;

    public $alamat;

    public $status;

    public $totalAsuransi = 0;

    public $asuransiAktif = 0;

    public $takAktif = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $kodeReadonly = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function asuransis()
    {
        $query = MstAsuransi::withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_asuransi', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_asuransi', 'like', '%'.$this->search.'%')
                    ->orWhere('tipe_asuransi', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('kode_asuransi', 'asc')->paginate(10);
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
            'kode_asuransi' => ['required', 'string', 'max:20', Rule::unique('mst_asuransi', 'kode_asuransi')->ignore($this->asuransiId)],
            'nama_asuransi' => 'required|string|max:100',
            'tipe_asuransi' => 'required|in:Pemerintah,Swasta,Lainnya',
            'diskon' => 'nullable|numeric|min:0|max:100',
            'no_telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'alamat' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['asuransiId', 'kode_asuransi', 'nama_asuransi', 'tipe_asuransi', 'diskon', 'no_telepon', 'email', 'alamat', 'isEdit']);
        $this->status = 'Aktif';
        $this->tipe_asuransi = 'Swasta';
        $this->diskon = 0;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_asuransi', 'kode_asuransi');
        if ($generated) {
            $this->kode_asuransi = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstAsuransi::withTrashed()->findOrFail($id);

        $this->asuransiId = $item->id;
        $this->kode_asuransi = $item->kode_asuransi;
        $this->nama_asuransi = $item->nama_asuransi;
        $this->tipe_asuransi = $item->tipe_asuransi;
        $this->diskon = $item->diskon;
        $this->no_telepon = $item->no_telepon;
        $this->email = $item->email;
        $this->alamat = $item->alamat;
        $this->status = $item->status;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $item = $this->asuransiId
                ? MstAsuransi::withTrashed()->findOrFail($this->asuransiId)
                : new MstAsuransi;

            if (! $this->asuransiId && empty($this->kode_asuransi)) {
                $this->kode_asuransi = $this->generateDynamicKode('mst_asuransi', 'kode_asuransi');
            }

            $item->fill([
                'kode_asuransi' => $this->kode_asuransi,
                'nama_asuransi' => $this->nama_asuransi,
                'tipe_asuransi' => $this->tipe_asuransi,
                'diskon' => $this->diskon,
                'no_telepon' => $this->no_telepon,
                'email' => $this->email,
                'alamat' => $this->alamat,
                'status' => $this->status ?? 'Aktif',
            ]);
            $item->save();

            if ($this->status === 'Aktif' && $item->trashed()) {
                $item->restore();
            } elseif ($this->status === 'Tidak Aktif' && ! $item->trashed()) {
                $item->delete();
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data asuransi berhasil diperbarui!' : 'Asuransi baru berhasil ditambahkan!']);
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
        $item = MstAsuransi::withTrashed()->findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status asuransi telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalAsuransi = MstAsuransi::withTrashed()->count();
        $this->asuransiAktif = MstAsuransi::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstAsuransi::withTrashed()->where('status', 'Tidak Aktif')->count();

        return view('livewire.modules.master.asuransi-page');
    }
}
