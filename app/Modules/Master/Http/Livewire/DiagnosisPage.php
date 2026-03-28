<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstDiagnosis;
use Illuminate\Validation\Rule;

class DiagnosisPage extends Component
{
    public $diagnosisId;
    public $kode_diagnosa, $nama_diagnosa, $kategori, $deskripsi, $status;
    
    public $diagnosisList = [];
    public $totalDiagnosis = 0;
    public $diagnosisAktif = 0;
    public $takAktif = 0;
    
    public $selectedStatus = 'all';
    public $isEdit = false;

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->dispatch('refresh-table');
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
        $this->dispatch('refresh-table');
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
        $this->dispatch('refresh-table');
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
            $this->dispatch('refresh-table');
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
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status diagnosis telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $query = MstDiagnosis::withTrashed();
        if ($this->selectedStatus !== 'all') { $query->where('status', $this->selectedStatus); }
        $this->diagnosisList = $query->get();
        $this->totalDiagnosis = MstDiagnosis::withTrashed()->count();
        $this->diagnosisAktif = MstDiagnosis::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstDiagnosis::withTrashed()->where('status', 'Tidak Aktif')->count();

        return <<<'HTML'
        <div x-data="{ showModal: false, initDataTable() { const t='#diagnosisTable'; if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} const tb=$(t).DataTable({scrollX:false,dom:'lrtip',language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',infoFiltered:'(disaring dari total _MAX_ data)',zeroRecords:'Tidak ada data yang ditemukan',emptyTable:'Tidak ada data dalam tabel',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}}); $('#customSearch').off('keyup').on('keyup',function(){tb.search(this.value).draw()}) }, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})} $nextTick(()=>this.initDataTable())}); $nextTick(()=>this.initDataTable())} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-stethoscope-line"></i></div><h1>Diagnosis</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a><span class="sep">/</span><a href="#">Master</a><span class="sep">/</span><span>Data Diagnosis</span></div></div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info"><i class="ri-stethoscope-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Diagnosis</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalDiagnosis) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success"><i class="ri-checkbox-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Diagnosis Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($diagnosisAktif) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger"><i class="ri-close-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Tidak Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4></div></div></div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7] bg-white">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua Data</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button"><i class="ri-checkbox-circle-line"></i><span>Aktif</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button"><i class="ri-close-circle-line"></i><span>Tidak Aktif</span></a></li>
                            </ul>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                            <div class="relative flex-grow md:flex-none"><input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg bg-[#f3f6f9] border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari diagnosis..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i></div>
                            <div class="flex items-center gap-1.5 p-1 bg-[#f3f6f9] rounded-lg border border-[#e9ecef]">
                                <a href="{{ route('master.diagnosis.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-white hover:shadow-sm transition-all" title="Cetak PDF"><i class="ri-printer-line text-lg"></i></a>
                                <div class="w-[1px] h-4 bg-[#e9ecef]"></div>
                                <a href="{{ route('master.diagnosis.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-white hover:shadow-sm transition-all" title="Unduh Excel"><i class="ri-file-excel-2-line text-lg"></i></a>
                            </div>
                            <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>
                            <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto"><i class="ri-add-line text-lg"></i><span class="font-semibold text-xs uppercase tracking-wider">Tambah Diagnosis</span></button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0"><div class="table-responsive bg-white">
                    <table id="diagnosisTable" class="display w-full">
                    <thead><tr><th>Kode ICD-10</th><th>Nama Diagnosa</th><th>Kategori</th><th>Status</th><th class="!text-center" style="text-align: center !important;">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($diagnosisList as $item)
                        <tr wire:key="diagnosis-{{ $item->id }}">
                            <td><span class="font-semibold text-[#405189]">{{ $item->kode_diagnosa }}</span></td>
                            <td>{{ $item->nama_diagnosa }}</td>
                            <td>{{ $item->kategori ?? '-' }}</td>
                            <td><span class="badge {{ $item->status == 'Aktif' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">{{ $item->status }}</span></td>
                            <td class="text-center"><div class="flex justify-center gap-2">
                                <button wire:click="edit({{ $item->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all"><i class="ri-edit-line"></i></button>
                                <button @click="if('{{ $item->status }}'==='Tidak Aktif'){Swal.fire({title:'Informasi',text:'Data dengan status Tidak Aktif tidak dapat dihapus.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi',text:'Apakah Anda yakin ingin menonaktifkan diagnosis ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan!',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $item->id }})}})}" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all"><i class="ri-delete-bin-line"></i></button>
                            </div></td>
                        </tr>
                        @endforeach
                    </tbody></table>
                </div></div>
            </div>

            <!-- Modal -->
            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Data Diagnosis' : 'Tambah Diagnosis Baru' }}</h5><button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save">
                            <div class="space-y-4">
                                <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Informasi Diagnosis (ICD-10-CM)</h6>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Kode ICD-10 <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="kode_diagnosa" x-ref="firstInput" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 font-bold text-[#405189] focus:border-[#405189] transition-all @error('kode_diagnosa') border-red-400 @enderror" placeholder="Contoh: K02.1">
                                        @error('kode_diagnosa') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Kategori Diagnosis</label>
                                        <x-custom-dropdown 
                                            model="kategori" 
                                            :options="[
                                                ['value' => 'Gigi', 'label' => 'Gigi', 'icon' => 'ri-mastodon-line text-blue-500'],
                                                ['value' => 'Umum', 'label' => 'Umum', 'icon' => 'ri-hospital-line text-green-500'],
                                                ['value' => 'Bedah', 'label' => 'Bedah', 'icon' => 'ri-scissors-line text-red-500']
                                            ]"
                                            placeholder="Pilih Kategori"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Diagnosa <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="nama_diagnosa" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('nama_diagnosa') border-red-400 @enderror" placeholder="Contoh: Dental caries on pit and fissure surface">
                                    @error('nama_diagnosa') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Deskripsi</label>
                                    <textarea wire:model="deskripsi" rows="3" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all" placeholder="Deskripsi tambahan..."></textarea>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-2 hover:bg-gray-50 transition-colors"><div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full {{ $status === 'Aktif' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div><span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status</span></div><div class="flex items-center gap-3"><span class="text-[10px] font-extrabold {{ $status === 'Aktif' ? 'text-green-600' : 'text-red-500' }}">{{ strtoupper($status) }}</span><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" class="sr-only peer" {{ $status === 'Aktif' ? 'checked' : '' }} @click="$wire.set('status', '{{ $status === 'Aktif' ? 'Tidak Aktif' : 'Aktif' }}')"><div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0ab39c]"></div></label></div></div>
                            </div>
                        </form>
                    </div>
                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Batal</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70 disabled:cursor-not-allowed"><svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><i wire:loading.remove wire:target="save" class="ri-save-line"></i><span wire:loading.remove wire:target="save">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}</span><span wire:loading wire:target="save">Memproses...</span></button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
