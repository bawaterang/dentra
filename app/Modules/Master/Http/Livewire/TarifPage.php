<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstTarif;
use App\Models\MstTindakan;
use App\Models\MstAsuransi;
use Illuminate\Validation\Rule;

class TarifPage extends Component
{
    public $tarifId;
    public $kode_tindakan, $kode_asuransi, $tarif, $jasmed, $satuan_jasmed = 'Rp', $bhp, $adm_fee, $satuan, $status;
    
    public $tarifList = [];
    public $tindakanList = [];
    public $asuransiList = [];
    public $totalTarif = 0;
    public $tarifAktif = 0;
    public $takAktif = 0;
    
    public $selectedStatus = 'all';
    public $isEdit = false;

    public function setStatus($status) { $this->selectedStatus = $status; $this->dispatch('refresh-table'); }

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
        $this->status = 'Aktif'; $this->satuan_jasmed = 'Rp'; $this->tarif = 0; $this->jasmed = 0; $this->bhp = 0; $this->adm_fee = 0;
        $this->resetErrorBag();
    }

    public function create() { $this->resetForm(); $this->dispatch('open-modal'); $this->dispatch('refresh-table'); }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstTarif::withTrashed()->findOrFail($id);
        $this->tarifId = $item->id; $this->kode_tindakan = $item->kode_tindakan; $this->kode_asuransi = $item->kode_asuransi;
        $this->tarif = $item->tarif; $this->jasmed = $item->jasmed; $this->satuan_jasmed = $item->satuan_jasmed ?? 'Rp'; $this->bhp = $item->bhp;
        $this->adm_fee = $item->adm_fee; $this->satuan = $item->satuan; $this->status = $item->status;
        $this->isEdit = true; $this->dispatch('open-modal'); $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());
            $item = $this->tarifId ? MstTarif::withTrashed()->findOrFail($this->tarifId) : new MstTarif();
            $item->fill(['kode_tindakan' => $this->kode_tindakan, 'kode_asuransi' => $this->kode_asuransi, 'tarif' => $this->tarif, 'jasmed' => $this->jasmed, 'satuan_jasmed' => $this->satuan_jasmed ?? 'Rp', 'bhp' => $this->bhp, 'adm_fee' => $this->adm_fee, 'satuan' => $this->satuan, 'status' => $this->status ?? 'Aktif']);
            $item->save();
            if ($this->status === 'Aktif' && $item->trashed()) { $item->restore(); }
            elseif ($this->status === 'Tidak Aktif' && !$item->trashed()) { $item->delete(); }
            $this->dispatch('close-modal'); $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data tarif berhasil diperbarui!' : 'Tarif baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']); throw $e;
        } catch (\Exception $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]); }
    }

    public function delete($id)
    {
        $item = MstTarif::withTrashed()->findOrFail($id);
        if ($item->status === 'Tidak Aktif') { $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus.']); return; }
        $item->update(['status' => 'Tidak Aktif']); $item->delete();
        $this->dispatch('refresh-table'); $this->dispatch('alert', ['type' => 'success', 'message' => 'Status tarif telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $query = MstTarif::withTrashed();
        if ($this->selectedStatus !== 'all') { $query->where('status', $this->selectedStatus); }
        $this->tarifList = $query->get();
        $this->totalTarif = MstTarif::withTrashed()->count();
        $this->tarifAktif = MstTarif::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstTarif::withTrashed()->where('status', 'Tidak Aktif')->count();

        $this->tindakanList = MstTindakan::all()->map(fn($t) => ['value' => $t->kode_tindakan, 'label' => $t->nama_tindakan, 'icon' => 'ri-pulse-line'])->toArray();
        $this->asuransiList = MstAsuransi::all()->map(fn($a) => ['value' => $a->kode_asuransi, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-user-line'])->toArray();

        return <<<'HTML'
        <div x-data="{ showModal: false, initDataTable() { const t='#tarifTable'; if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} const tb=$(t).DataTable({scrollX:false,dom:'lrtip',language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',infoFiltered:'(disaring dari total _MAX_ data)',zeroRecords:'Tidak ada data yang ditemukan',emptyTable:'Tidak ada data dalam tabel',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}}); $('#customSearch').off('keyup').on('keyup',function(){tb.search(this.value).draw()}) }, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})} $nextTick(()=>this.initDataTable())}); $nextTick(()=>this.initDataTable())} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-money-dollar-circle-line"></i></div><h1>Tarif</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a><span class="sep">/</span><a href="#">Master</a><span class="sep">/</span><span>Data Tarif</span></div></div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info"><i class="ri-money-dollar-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Tarif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalTarif) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success"><i class="ri-checkbox-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Tarif Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($tarifAktif) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger"><i class="ri-close-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Tidak Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4></div></div></div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7]"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom"><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua Tarif</span></a></li><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button"><i class="ri-checkbox-circle-line"></i><span>Aktif</span></a></li><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button"><i class="ri-close-circle-line"></i><span>Tidak Aktif</span></a></li></ul></div>
                    <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                        <div class="relative flex-grow md:flex-none"><input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari tarif..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i></div>
                        <div class="flex items-center gap-1.5 p-1 rounded-lg border border-[#e9ecef]"><a href="{{ route('master.tarif.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-white hover:shadow-sm transition-all" title="Cetak PDF"><i class="ri-printer-line text-lg"></i></a><div class="w-[1px] h-4 bg-[#e9ecef]"></div><a href="{{ route('master.tarif.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-white hover:shadow-sm transition-all" title="Unduh Excel"><i class="ri-file-excel-2-line text-lg"></i></a></div>
                        <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>
                        <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto"><i class="ri-add-line text-lg"></i><span class="font-semibold text-xs uppercase tracking-wider">Tambah Tarif</span></button>
                    </div>
                </div></div>
                <div class="card-body p-0"><div class="table-responsive dark:bg-transparent">
                    <table id="tarifTable" class="table align-middle table-nowrap w-full">
                    <thead class="table-light text-muted"><tr><th>Kode Tindakan</th><th>Kode Asuransi</th><th>Tarif</th><th>Jasmed</th><th>Satuan</th><th>BHP</th><th>Status</th><th class="!text-center" style="text-align: center !important;">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($tarifList as $item)
                        <tr wire:key="tarif-{{ $item->id }}">
                            <td><span class="font-semibold text-[#405189]">{{ $item->kode_tindakan }}</span></td>
                            <td>{{ $item->kode_asuransi }}</td>
                            <td>Rp {{ number_format($item->tarif, 0, ',', '.') }}</td>
                            <td>{{ ($item->satuan_jasmed ?? 'Rp') === '%' ? number_format($item->jasmed, 0, ',', '.') . '%' : 'Rp ' . number_format($item->jasmed, 0, ',', '.') }}</td>
                            <td><span class="text-[11px] font-bold px-2 py-0.5 rounded {{ ($item->satuan_jasmed ?? 'Rp') === '%' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">{{ $item->satuan_jasmed ?? 'Rp' }}</span></td>
                            <td>Rp {{ number_format($item->bhp, 0, ',', '.') }}</td>
                            <td><span class="badge {{ $item->status == 'Aktif' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">{{ $item->status }}</span></td>
                            <td class="text-center"><div class="flex justify-center gap-2">
                                <button wire:click="edit({{ $item->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all"><i class="ri-edit-line"></i></button>
                                <button @click="if('{{ $item->status }}'==='Tidak Aktif'){Swal.fire({title:'Informasi',text:'Data dengan status Tidak Aktif tidak dapat dihapus.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi',text:'Apakah Anda yakin ingin menonaktifkan tarif ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan!',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $item->id }})}})}" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all"><i class="ri-delete-bin-line"></i></button>
                            </div></td>
                        </tr>
                        @endforeach
                    </tbody></table>
                </div></div>
            </div>

            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-3xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Data Tarif' : 'Tambah Tarif Baru' }}</h5><button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Referensi</h6>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Pilih Tindakan <span class="text-red-500">*</span></label>
                                        <x-custom-dropdown 
                                            model="kode_tindakan" 
                                            :options="$tindakanList"
                                            placeholder="Cari & Pilih Tindakan..."
                                            searchable="true"
                                            icon="ri-pulse-line"
                                        />
                                        @error('kode_tindakan') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Pilih Asuransi <span class="text-red-500">*</span></label>
                                        <x-custom-dropdown 
                                            model="kode_asuransi" 
                                            :options="$asuransiList"
                                            placeholder="Cari & Pilih Asuransi..."
                                            searchable="true"
                                            icon="ri-shield-user-line"
                                        />
                                        @error('kode_asuransi') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Satuan</label>
                                        <x-custom-dropdown 
                                            model="satuan" 
                                            :options="[
                                                ['value' => 'Sesi', 'label' => 'Sesi', 'icon' => 'ri-time-line'],
                                                ['value' => 'Kali', 'label' => 'Kali', 'icon' => 'ri-history-line'],
                                                ['value' => 'Tindakan', 'label' => 'Tindakan', 'icon' => 'ri-list-check']
                                            ]"
                                            placeholder="Pilih Satuan"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#0ab39c] uppercase tracking-widest border-b pb-2">Rincian Biaya</h6>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tarif Total (Rp)</label><input type="number" wire:model="tarif" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 mb-1">Jasa Medik</label><input type="number" wire:model="jasmed" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">Satuan</label>
                                            <x-custom-dropdown 
                                                model="satuan_jasmed" 
                                                :options="[
                                                    ['value' => 'Rp', 'label' => 'Rp (Rupiah)', 'icon' => 'ri-money-dollar-circle-line text-blue-500'],
                                                    ['value' => '%', 'label' => '% (Persen)', 'icon' => 'ri-percent-line text-amber-500']
                                                ]"
                                                placeholder="Rp"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">BHP (Rp)</label><input type="number" wire:model="bhp" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Adm Fee (Rp)</label><input type="number" wire:model="adm_fee" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-2 hover:bg-gray-50 transition-colors"><div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full {{ $status === 'Aktif' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div><span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status</span></div><div class="flex items-center gap-3"><span class="text-[10px] font-extrabold {{ $status === 'Aktif' ? 'text-green-600' : 'text-red-500' }}">{{ strtoupper($status) }}</span><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" class="sr-only peer" {{ $status === 'Aktif' ? 'checked' : '' }} @click="$wire.set('status', '{{ $status === 'Aktif' ? 'Tidak Aktif' : 'Aktif' }}')"><div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0ab39c]"></div></label></div></div>
                                </div>
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
