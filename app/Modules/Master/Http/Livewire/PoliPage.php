<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstPoli;
use Illuminate\Validation\Rule;

class PoliPage extends Component
{
    public $poliId;
    public $kode_poli, $nama_poli, $status;

    public $poliList = [];
    public $totalPoli = 0;
    public $poliAktif = 0;
    public $takAktif = 0;

    public $selectedStatus = 'all';
    public $isEdit = false;

    public function setStatus($status) { $this->selectedStatus = $status; $this->dispatch('refresh-table'); }

    protected function rules()
    {
        return [
            'kode_poli' => ['required', 'string', 'max:20', Rule::unique('mst_poli', 'kode_poli')->ignore($this->poliId)],
            'nama_poli' => 'required|string|max:100',
        ];
    }

    public function resetForm()
    {
        $this->reset(['poliId', 'kode_poli', 'nama_poli', 'isEdit']);
        $this->status = 'Aktif';
        $this->resetErrorBag();
    }

    private function generateKode()
    {
        $last = MstPoli::withTrashed()->orderBy('id', 'desc')->first();
        $next = 1;
        if ($last && $last->kode_poli) {
            $num = (int) preg_replace('/[^0-9]/', '', $last->kode_poli);
            $next = $num + 1;
        }
        return 'POL' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        $this->resetForm();
        $this->kode_poli = $this->generateKode();
        $this->dispatch('open-modal');
        $this->dispatch('refresh-table');
    }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstPoli::findOrFail($id);
        $this->poliId = $item->id;
        $this->kode_poli = $item->kode_poli;
        $this->nama_poli = $item->nama_poli;
        $this->status = $item->status;
        $this->isEdit = true;
        $this->dispatch('open-modal');
        $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());
            $item = $this->poliId ? MstPoli::findOrFail($this->poliId) : new MstPoli();
            $item->fill(['kode_poli' => $this->kode_poli, 'nama_poli' => $this->nama_poli, 'status' => $this->status]);
            $item->save();
            $this->dispatch('close-modal');
            $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data poli berhasil diperbarui!' : 'Poli baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) { throw $e;
        } catch (\Exception $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]); }
    }

    public function delete($id)
    {
        $item = MstPoli::findOrFail($id);
        if ($item->status === 'Tidak Aktif') { $this->dispatch('alert', ['type' => 'info', 'message' => 'Poli sudah tidak aktif.']); return; }
        $item->update(['status' => 'Tidak Aktif']);
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status poli diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $query = MstPoli::query();
        if ($this->selectedStatus === 'Aktif') { $query->where('status', 'Aktif'); }
        elseif ($this->selectedStatus === 'Tidak Aktif') { $query->where('status', 'Tidak Aktif'); }
        $this->poliList = $query->orderBy('kode_poli')->get();
        $this->totalPoli = MstPoli::count();
        $this->poliAktif = MstPoli::where('status', 'Aktif')->count();
        $this->takAktif = MstPoli::where('status', 'Tidak Aktif')->count();

        return <<<'HTML'
        <div x-data="{ showModal: false, initDataTable() { const t='#poliTable'; if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} const tb=$(t).DataTable({scrollX:false,dom:'lrtip',language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',infoFiltered:'(disaring dari total _MAX_ data)',zeroRecords:'Tidak ada data yang ditemukan',emptyTable:'Tidak ada data dalam tabel',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}}); $('#customSearch').off('keyup').on('keyup',function(){tb.search(this.value).draw()}) }, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})} $nextTick(()=>this.initDataTable())}); $nextTick(()=>this.initDataTable())} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-hospital-line"></i></div><h1>Data Poli</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a><span class="sep">/</span><a href="#">Master</a><span class="sep">/</span><span>Data Poli</span></div></div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info"><i class="ri-hospital-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Poli</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalPoli) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success"><i class="ri-checkbox-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($poliAktif) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger"><i class="ri-close-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Tidak Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4></div></div></div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7]"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom"><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua</span></a></li><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button"><i class="ri-checkbox-circle-line"></i><span>Aktif</span></a></li><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button"><i class="ri-close-circle-line"></i><span>Tidak Aktif</span></a></li></ul></div>
                    <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                        <div class="relative flex-grow md:flex-none"><input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari poli..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i></div>
                        <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>
                        <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto"><i class="ri-add-line text-lg"></i><span class="font-semibold text-xs uppercase tracking-wider">Tambah Poli</span></button>
                    </div>
                </div></div>
                <div class="card-body p-0"><div class="table-responsive dark:bg-transparent">
                    <table id="poliTable" class="table align-middle table-nowrap w-full">
                    <thead class="table-light text-muted"><tr><th width="5%">No</th><th>Kode Poli</th><th>Nama Poli</th><th>Status</th><th class="!text-center" style="text-align: center !important;">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($poliList as $index => $item)
                        <tr wire:key="poli-{{ $item->id }}">
                            <td>{{ $index + 1 }}</td>
                            <td><span class="font-mono font-bold text-[#405189]">{{ $item->kode_poli }}</span></td>
                            <td><span class="font-medium text-[#495057]">{{ $item->nama_poli }}</span></td>
                            <td><span class="badge {{ $item->status === 'Aktif' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">{{ $item->status }}</span></td>
                            <td class="text-center"><div class="flex justify-center gap-2">
                                <button wire:click="edit({{ $item->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all"><i class="ri-edit-line"></i></button>
                                <button @click="if('{{ $item->status }}' === 'Tidak Aktif'){Swal.fire({title:'Informasi',text:'Poli ini sudah tidak aktif.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi',text:'Nonaktifkan poli ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan!',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $item->id }})}})}" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all"><i class="ri-delete-bin-line"></i></button>
                            </div></td>
                        </tr>
                        @endforeach
                    </tbody></table>
                </div></div>
            </div>

            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-visible">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Data Poli' : 'Tambah Poli Baru' }}</h5><button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-8 py-6 max-h-[75vh] overflow-visible">
                        <form wire:submit.prevent="save">
                            <div class="space-y-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Kode Poli <span class="text-red-500">*</span></label><input type="text" wire:model="kode_poli" x-ref="firstInput" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all @error('kode_poli') border-red-400 @enderror" placeholder="POL001" {{ $isEdit ? 'readonly' : '' }}>@error('kode_poli') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror</div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nama Poli <span class="text-red-500">*</span></label><input type="text" wire:model="nama_poli" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all @error('nama_poli') border-red-400 @enderror" placeholder="Poli Gigi & Mulut">@error('nama_poli') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror</div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                                    <x-custom-dropdown model="status" :options="[
                                        ['value' => 'Aktif', 'label' => 'Aktif', 'icon' => 'ri-checkbox-circle-line text-green-500'],
                                        ['value' => 'Tidak Aktif', 'label' => 'Tidak Aktif', 'icon' => 'ri-close-circle-line text-red-500']
                                    ]" placeholder="Pilih Status" />
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Batal</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70"><svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><i wire:loading.remove wire:target="save" class="ri-save-line"></i><span wire:loading.remove wire:target="save">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}</span><span wire:loading wire:target="save">Memproses...</span></button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
