<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstSurvei;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class SurveiPage extends Component
{
    use WithPagination;

    public $surveiId;

    public $pertanyaan;

    public $jenis_survei;

    public $status;

    public $totalSurvei = 0;

    public $surveiAktif = 0;

    public $takAktif = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function surveis()
    {
        $query = MstSurvei::query();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('pertanyaan', 'like', '%'.$this->search.'%')
                    ->orWhere('jenis_survei', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('id', 'asc')->paginate(10);
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
            'pertanyaan' => 'required|string|max:500',
            'jenis_survei' => 'required|string|max:50',
        ];
    }

    public function resetForm()
    {
        $this->reset(['surveiId', 'pertanyaan', 'jenis_survei', 'isEdit']);
        $this->status = 'Aktif';
        $this->jenis_survei = 'screening';
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
        $item = MstSurvei::findOrFail($id);
        $this->surveiId = $item->id;
        $this->pertanyaan = $item->pertanyaan;
        $this->jenis_survei = $item->jenis_survei;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());
            $item = $this->surveiId ? MstSurvei::findOrFail($this->surveiId) : new MstSurvei;
            $item->fill(['pertanyaan' => $this->pertanyaan, 'jenis_survei' => $this->jenis_survei, 'status' => $this->status ?? 'Aktif']);
            $item->save();
            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data survei berhasil diperbarui!' : 'Pertanyaan survei baru berhasil ditambahkan!']);
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
        $item = MstSurvei::findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Survei sudah tidak aktif.']);

            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status survei telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalSurvei = MstSurvei::count();
        $this->surveiAktif = MstSurvei::where('status', 'Aktif')->count();
        $this->takAktif = MstSurvei::where('status', 'Tidak Aktif')->count();

        return view('livewire.modules.master.survei-page');
    }
}
