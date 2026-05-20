<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstAsuransi;
use App\Models\MstTarif;
use App\Models\MstTindakan;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TarifPage extends Component
{
    use WithPagination;

    public $tarifId;

    public $kode_tindakan;

    public $kode_asuransi;

    public $tarif;

    public $jasmed;

    public $satuan_jasmed = 'Rp';

    public $bhp;

    public $adm_fee;

    public $satuan;

    public $status;

    public $totalTarif = 0;

    public $tarifAktif = 0;

    public $takAktif = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $tindakanList = [];

    public $asuransiList = [];

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function tarifs()
    {
        $query = MstTarif::withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_tindakan', 'like', '%'.$this->search.'%')
                    ->orWhere('kode_asuransi', 'like', '%'.$this->search.'%');
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
            'kode_tindakan' => 'required|string|max:20',
            'kode_asuransi' => 'required|string|max:20',
            'tarif' => 'nullable|numeric|min:0',
            'jasmed' => 'nullable|numeric|min:0',
            'satuan_jasmed' => 'nullable|string|in:Rp,%',
            'bhp' => 'nullable|numeric|min:0',
            'adm_fee' => 'nullable|numeric|min:0',
            'satuan' => 'nullable|string|max:50',
        ];
    }

    public function resetForm()
    {
        $this->reset(['tarifId', 'kode_tindakan', 'kode_asuransi', 'tarif', 'jasmed', 'satuan_jasmed', 'bhp', 'adm_fee', 'satuan', 'isEdit']);
        $this->status = 'Aktif';
        $this->satuan_jasmed = 'Rp';
        $this->tarif = 0;
        $this->jasmed = 0;
        $this->bhp = 0;
        $this->adm_fee = 0;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstTarif::withTrashed()->findOrFail($id);
        $this->tarifId = $item->id;
        $this->kode_tindakan = $item->kode_tindakan;
        $this->kode_asuransi = $item->kode_asuransi;
        $this->tarif = $item->tarif;
        $this->jasmed = $item->jasmed;
        $this->satuan_jasmed = $item->satuan_jasmed ?? 'Rp';
        $this->bhp = $item->bhp;
        $this->adm_fee = $item->adm_fee;
        $this->satuan = $item->satuan;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $item = $this->tarifId
                ? MstTarif::withTrashed()->findOrFail($this->tarifId)
                : new MstTarif;

            $item->fill([
                'kode_tindakan' => $this->kode_tindakan,
                'kode_asuransi' => $this->kode_asuransi,
                'tarif' => $this->tarif,
                'jasmed' => $this->jasmed,
                'satuan_jasmed' => $this->satuan_jasmed ?? 'Rp',
                'bhp' => $this->bhp,
                'adm_fee' => $this->adm_fee,
                'satuan' => $this->satuan,
                'status' => $this->status ?? 'Aktif',
            ]);
            $item->save();

            if ($this->status === 'Aktif' && $item->trashed()) {
                $item->restore();
            } elseif ($this->status === 'Tidak Aktif' && ! $item->trashed()) {
                $item->delete();
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data tarif berhasil diperbarui!' : 'Tarif baru berhasil ditambahkan!']);
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
        $item = MstTarif::withTrashed()->findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status tarif telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalTarif = MstTarif::withTrashed()->count();
        $this->tarifAktif = MstTarif::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstTarif::withTrashed()->where('status', 'Tidak Aktif')->count();

        $this->tindakanList = MstTindakan::all()->map(fn ($t) => ['value' => $t->kode_tindakan, 'label' => $t->nama_tindakan, 'icon' => 'ri-pulse-line'])->toArray();
        $this->asuransiList = MstAsuransi::all()->map(fn ($a) => ['value' => $a->kode_asuransi, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-user-line'])->toArray();

        return view('livewire.modules.master.tarif-page');
    }
}
