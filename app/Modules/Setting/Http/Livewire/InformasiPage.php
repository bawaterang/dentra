<?php

namespace App\Modules\Setting\Http\Livewire;

use App\Models\TrxInformasi;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class InformasiPage extends Component
{
    use WithPagination;

    public $informasiId;
    public $description;
    public $date_start;
    public $date_expired;

    public $totalInformasi = 0;
    public $aktif = 0;
    public $expired = 0;
    public $selectedStatus = 'all';
    public $search = '';
    public $isEdit = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function informations()
    {
        // Using withTrashed to show all info including soft deleted if needed,
        // but typically we only want active ones. The user asked for soft delete 
        // to be implemented, meaning we might not query withTrashed() by default
        // unless requested. We'll stick to non-deleted for standard view.
        $query = TrxInformasi::query();
        
        $today = Carbon::today()->format('Y-m-d');

        if ($this->selectedStatus === 'Aktif') {
            $query->where('date_start', '<=', $today)
                  ->where('date_expired', '>=', $today);
        } elseif ($this->selectedStatus === 'Expired') {
            $query->where(function ($q) use ($today) {
                $q->where('date_expired', '<', $today)
                  ->orWhere('date_start', '>', $today);
            });
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%'.$this->search.'%')
                  ->orWhere('created_by', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('date_start', 'desc')->paginate(10);
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
            'description' => 'required|string',
            'date_start' => 'required|date',
            'date_expired' => 'required|date|after_or_equal:date_start',
        ];
    }

    public function resetForm()
    {
        $this->reset([
            'informasiId', 'description', 'date_start', 'date_expired', 'isEdit'
        ]);

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
        $info = TrxInformasi::findOrFail($id);

        $this->informasiId = $info->id;
        $this->description = $info->description;
        $this->date_start = $info->date_start ? $info->date_start->format('Y-m-d') : null;
        $this->date_expired = $info->date_expired ? $info->date_expired->format('Y-m-d') : null;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $info = $this->informasiId
                ? TrxInformasi::findOrFail($this->informasiId)
                : new TrxInformasi;

            $info->fill([
                'description' => $this->description,
                'date_start' => $this->date_start,
                'date_expired' => $this->date_expired,
            ]);

            if (! $this->informasiId) {
                $info->created_by = Auth::user()->username ?? 'System';
            }

            $info->save();

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data informasi berhasil diperbarui!' : 'Informasi baru berhasil ditambahkan!']);
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
        try {
            $info = TrxInformasi::findOrFail($id);
            $info->delete();
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Data informasi berhasil dihapus!']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Hapus Gagal: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        $this->totalInformasi = TrxInformasi::count();
        $today = Carbon::today()->format('Y-m-d');
        
        $this->aktif = TrxInformasi::where('date_start', '<=', $today)
                                   ->where('date_expired', '>=', $today)
                                   ->count();
                                   
        $this->expired = TrxInformasi::where(function($q) use ($today) {
                                       $q->where('date_expired', '<', $today)
                                         ->orWhere('date_start', '>', $today);
                                   })->count();

        return view('livewire.modules.setting.informasi-page');
    }
}
