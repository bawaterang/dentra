<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstDiagnosis;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;

class DiagnosisPage extends Component
{
    use WithPagination;

    public $diagnosisId;
    public $kode_diagnosa, $nama_diagnosa, $kategori, $deskripsi, $status;
    
    public $totalDiagnosis = 0;
    public $diagnosisAktif = 0;
    public $takAktif = 0;
    
    public $selectedStatus = 'all';
    public $search = '';
    public $isEdit = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function diagnoses()
    {
        $query = MstDiagnosis::withTrashed();
        
        if ($this->selectedStatus !== 'all') { 
            $query->where('status', $this->selectedStatus); 
        }

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('kode_diagnosa', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_diagnosa', 'like', '%' . $this->search . '%')
                  ->orWhere('kategori', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('kode_diagnosa', 'asc')->paginate(10);
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
            'kode_diagnosa' => ['required', 'string', 'max:20', Rule::unique('mst_diagnosis', 'kode_diagnosa')->ignore($this->diagnosisId)],
            'nama_diagnosa' => 'required|string|max:200',
            'kategori' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['diagnosisId', 'kode_diagnosa', 'nama_diagnosa', 'kategori', 'deskripsi', 'isEdit']);
        $this->status = 'Aktif';
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
        $item = MstDiagnosis::withTrashed()->findOrFail($id);
        $this->diagnosisId = $item->id;
        $this->kode_diagnosa = $item->kode_diagnosa;
        $this->nama_diagnosa = $item->nama_diagnosa;
        $this->kategori = $item->kategori;
        $this->deskripsi = $item->deskripsi;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $item = $this->diagnosisId 
                ? MstDiagnosis::withTrashed()->findOrFail($this->diagnosisId) 
                : new MstDiagnosis();

            $item->fill([
                'kode_diagnosa' => $this->kode_diagnosa,
                'nama_diagnosa' => $this->nama_diagnosa,
                'kategori' => $this->kategori,
                'deskripsi' => $this->deskripsi,
                'status' => $this->status ?? 'Aktif',
            ]);
            $item->save();

            if ($this->status === 'Aktif' && $item->trashed()) {
                $item->restore();
            } elseif ($this->status === 'Tidak Aktif' && !$item->trashed()) {
                $item->delete();
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data diagnosis berhasil diperbarui!' : 'Diagnosis baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $item = MstDiagnosis::withTrashed()->findOrFail($id);
        if ($item->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);
            return;
        }
        $item->update(['status' => 'Tidak Aktif']);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status diagnosis telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalDiagnosis = MstDiagnosis::withTrashed()->count();
        $this->diagnosisAktif = MstDiagnosis::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstDiagnosis::withTrashed()->where('status', 'Tidak Aktif')->count();

        return view('livewire.modules.master.diagnosis-page');
    }
}
