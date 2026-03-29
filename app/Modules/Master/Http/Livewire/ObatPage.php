<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstObat;
use Illuminate\Validation\Rule;

class ObatPage extends Component
{
    public $obatId;
    public $kode_obat, $nama_obat, $satuan, $stok, $stok_minimal, $harga_beli, $harga_jual, $tanggal_beli, $tanggal_expired, $keterangan, $status;
    
    public $obatList = [];
    public $totalObat = 0;
    public $obatAktif = 0;
    public $takAktif = 0;
    public $stokHabis = 0;
    
    public $selectedStatus = 'all';
    public $isEdit = false;

    public function setStatus($status) { $this->selectedStatus = $status; $this->dispatch('refresh-table'); }

    protected function rules()
    {
        return [
            'kode_obat' => ['required', 'string', 'max:20', Rule::unique('mst_obat', 'kode_obat')->ignore($this->obatId)],
            'nama_obat' => 'required|string|max:100',
            'satuan' => 'nullable|string|max:20',
            'stok' => 'nullable|integer|min:0',
            'stok_minimal' => 'nullable|integer|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'harga_jual' => 'nullable|numeric|min:0',
            'tanggal_beli' => 'nullable|date',
            'tanggal_expired' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ];
    }

    public function resetForm()
    {
        $this->reset(['obatId', 'kode_obat', 'nama_obat', 'satuan', 'stok', 'stok_minimal', 'harga_beli', 'harga_jual', 'tanggal_beli', 'tanggal_expired', 'keterangan', 'isEdit']);
        $this->status = 'Aktif'; $this->stok = 0; $this->stok_minimal = 10; $this->harga_beli = 0; $this->harga_jual = 0;
        $this->resetErrorBag();
    }

    private function generateKode()
    {
        $last = MstObat::withTrashed()->orderBy('id', 'desc')->first();
        $next = 1;
        if ($last && $last->kode_obat) { $num = (int) substr($last->kode_obat, 3); $next = $num + 1; }
        return 'OBT' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function create() { $this->resetForm(); $this->kode_obat = $this->generateKode(); $this->dispatch('open-modal'); $this->dispatch('refresh-table'); }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstObat::withTrashed()->findOrFail($id);
        $this->obatId = $item->id; $this->kode_obat = $item->kode_obat; $this->nama_obat = $item->nama_obat;
        $this->satuan = $item->satuan; $this->stok = $item->stok; $this->stok_minimal = $item->stok_minimal;
        $this->harga_beli = $item->harga_beli; $this->harga_jual = $item->harga_jual;
        $this->tanggal_beli = $item->tanggal_beli ? $item->tanggal_beli->format('Y-m-d') : null;
        $this->tanggal_expired = $item->tanggal_expired ? $item->tanggal_expired->format('Y-m-d') : null;
        $this->keterangan = $item->keterangan; $this->status = $item->status;
        $this->isEdit = true; $this->dispatch('open-modal'); $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $rules = $this->rules();
            if (!$this->obatId) { unset($rules['kode_obat']); }
            $this->validate($rules);
            $attempts = 0; $success = false;
            while (!$success && $attempts < 5) {
                try {
                    $item = $this->obatId ? MstObat::withTrashed()->findOrFail($this->obatId) : new MstObat();
                    if (!$this->obatId && empty($this->kode_obat)) { $this->kode_obat = $this->generateKode(); }
                    $item->fill([
                        'kode_obat' => $this->kode_obat, 'nama_obat' => $this->nama_obat, 'satuan' => $this->satuan,
                        'stok' => $this->stok, 'stok_minimal' => $this->stok_minimal, 'harga_beli' => $this->harga_beli,
                        'harga_jual' => $this->harga_jual, 'tanggal_beli' => $this->tanggal_beli, 'tanggal_expired' => $this->tanggal_expired,
                        'keterangan' => $this->keterangan, 'status' => $this->status ?? 'Aktif',
                    ]);
                    $item->save();
                    if ($this->status === 'Aktif' && $item->trashed()) { $item->restore(); }
                    elseif (in_array($this->status, ['Tidak Aktif', 'Stok Habis']) && !$item->trashed()) { $item->delete(); }
                    $success = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'kode_obat')) { if (!$this->obatId) { $attempts++; $this->kode_obat = $this->generateKode(); continue; } }
                    throw $e;
                }
            }
            if (!$success) { throw new \Exception("Gagal menghasilkan kode unik."); }
            $this->dispatch('close-modal'); $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data obat berhasil diperbarui!' : 'Obat baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']); throw $e;
        } catch (\Exception $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]); }
    }

    public function delete($id)
    {
        $item = MstObat::withTrashed()->findOrFail($id);
        if (in_array($item->status, ['Tidak Aktif', 'Stok Habis'])) { $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status '.$item->status.' tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']); return; }
        $item->update(['status' => 'Tidak Aktif']); $item->delete();
        $this->dispatch('refresh-table'); $this->dispatch('alert', ['type' => 'success', 'message' => 'Status obat telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $query = MstObat::withTrashed();
        if ($this->selectedStatus !== 'all') { $query->where('status', $this->selectedStatus); }
        $this->obatList = $query->get();
        $this->totalObat = MstObat::withTrashed()->count();
        $this->obatAktif = MstObat::withTrashed()->where('status', 'Aktif')->count();
        $this->takAktif = MstObat::withTrashed()->where('status', 'Tidak Aktif')->count();
        $this->stokHabis = MstObat::withTrashed()->where('status', 'Stok Habis')->count();

        return <<<'HTML'
        <div x-data="{ showModal: false, initDataTable() { const t='#obatTable'; if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} const tb=$(t).DataTable({scrollX:false,dom:'lrtip',language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',infoFiltered:'(disaring dari total _MAX_ data)',zeroRecords:'Tidak ada data yang ditemukan',emptyTable:'Tidak ada data dalam tabel',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}}); $('#customSearch').off('keyup').on('keyup',function(){tb.search(this.value).draw()}) }, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})} $nextTick(()=>this.initDataTable())}); $nextTick(()=>this.initDataTable())} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-capsule-line"></i></div><h1>Obat</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a><span class="sep">/</span><a href="#">Master</a><span class="sep">/</span><span>Data Obat</span></div></div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info"><i class="ri-capsule-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Obat</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalObat) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success"><i class="ri-checkbox-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Obat Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($obatAktif) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger"><i class="ri-close-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Tidak Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f7b84b;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-subtle text-warning"><i class="ri-error-warning-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Stok Habis</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($stokHabis) }}</h4></div></div></div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7]"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom">
                        <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua Obat</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button"><i class="ri-checkbox-circle-line"></i><span>Aktif</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button"><i class="ri-close-circle-line"></i><span>Tidak Aktif</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Stok Habis' ? 'active active-pill-warning' : '' }}" wire:click="setStatus('Stok Habis')" role="button"><i class="ri-error-warning-line"></i><span>Stok Habis</span></a></li>
                    </ul></div>
                    <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                        <div class="relative flex-grow md:flex-none"><input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari obat..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i></div>
                        <div class="flex items-center gap-1.5 p-1 rounded-lg border border-[#e9ecef]"><a href="{{ route('master.obat.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-white hover:shadow-sm transition-all" title="Cetak PDF"><i class="ri-printer-line text-lg"></i></a><div class="w-[1px] h-4 bg-[#e9ecef]"></div><a href="{{ route('master.obat.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-white hover:shadow-sm transition-all" title="Unduh Excel"><i class="ri-file-excel-2-line text-lg"></i></a></div>
                        <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>
                        <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto"><i class="ri-add-line text-lg"></i><span class="font-semibold text-xs uppercase tracking-wider">Tambah Obat</span></button>
                    </div>
                </div></div>
                <div class="card-body p-0"><div class="table-responsive dark:bg-transparent">
                    <table id="obatTable" class="table align-middle table-nowrap w-full">
                    <thead class="table-light text-muted"><tr><th>Kode</th><th>Nama Obat</th><th>Satuan</th><th>Stok</th><th>Harga Jual</th><th>Status</th><th class="!text-center" style="text-align: center !important;">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($obatList as $item)
                        <tr wire:key="obat-{{ $item->id }}">
                            <td><span class="font-semibold text-[#405189]">{{ $item->kode_obat }}</span></td>
                            <td>{{ $item->nama_obat }}</td>
                            <td>{{ $item->satuan ?? '-' }}</td>
                            <td>{{ number_format($item->stok) }}</td>
                            <td>Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                            <td><span class="badge {{ $item->status == 'Aktif' ? 'bg-success-subtle' : ($item->status == 'Stok Habis' ? 'bg-warning-subtle' : 'bg-danger-subtle') }}">{{ $item->status }}</span></td>
                            <td class="text-center"><div class="flex justify-center gap-2">
                                <button wire:click="edit({{ $item->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all"><i class="ri-edit-line"></i></button>
                                <button @click="if('{{ $item->status }}'!=='Aktif'){Swal.fire({title:'Informasi',text:'Data dengan status {{ $item->status }} tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi',text:'Apakah Anda yakin ingin menonaktifkan obat ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan!',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $item->id }})}})}" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all"><i class="ri-delete-bin-line"></i></button>
                            </div></td>
                        </tr>
                        @endforeach
                    </tbody></table>
                </div></div>
            </div>

            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-4xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Data Obat' : 'Tambah Obat Baru' }}</h5><button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Informasi Obat</h6>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Kode Obat <span class="text-red-500">*</span></label><input type="text" wire:model="kode_obat" x-ref="firstInput" class="w-full rounded-lg border-gray-100 bg-gray-50/50 text-sm px-4 py-2.5 font-bold text-[#405189]" readonly tabindex="-1"></div>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nama Obat <span class="text-red-500">*</span></label><input type="text" wire:model="nama_obat" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('nama_obat') border-red-400 @enderror" placeholder="Contoh: Amoxicillin 500mg">@error('nama_obat') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Satuan</label>
                                        <x-custom-dropdown 
                                            model="satuan" 
                                            :options="[
                                                ['value' => 'Tablet', 'label' => 'Tablet', 'icon' => 'ri-capsule-line text-blue-500'],
                                                ['value' => 'Kapsul', 'label' => 'Kapsul', 'icon' => 'ri-capsule-fill text-indigo-500'],
                                                ['value' => 'Botol', 'label' => 'Botol', 'icon' => 'ri-flask-line text-green-500'],
                                                ['value' => 'Tube', 'label' => 'Tube', 'icon' => 'ri-paint-brush-line text-yellow-500'],
                                                ['value' => 'Sachet', 'label' => 'Sachet', 'icon' => 'ri-sticky-note-line text-gray-400']
                                            ]"
                                            placeholder="Pilih Satuan"
                                        />
                                        </div>
                                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Stok</label><input type="number" wire:model="stok" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                    </div>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Stok Minimal</label><input type="number" wire:model="stok_minimal" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                </div>
                                <div class="space-y-4">
                                    <h6 class="text-xs font-bold text-[#0ab39c] uppercase tracking-widest border-b pb-2">Harga & Tanggal</h6>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Harga Beli (Rp)</label><input type="number" wire:model="harga_beli" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Harga Jual (Rp)</label><input type="number" wire:model="harga_jual" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Beli</label><input type="date" wire:model="tanggal_beli" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all"></div>
                                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Expired</label><input type="date" wire:model="tanggal_expired" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all"></div>
                                    </div>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Keterangan</label><textarea wire:model="keterangan" rows="2" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2 focus:border-[#405189] transition-all" placeholder="Keterangan tambahan..."></textarea></div>
                                    <div class="space-y-2 mt-2">
                                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-tight mb-2">Pilih Status Obat</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <button type="button" 
                                                @click="$wire.set('status', 'Aktif')"
                                                class="flex flex-col items-center justify-center p-2 rounded-xl border-2 transition-all {{ $status === 'Aktif' ? 'border-green-500 bg-green-50 text-green-700 shadow-sm' : 'border-gray-100 bg-white text-gray-400 grayscale opacity-60 hover:grayscale-0 hover:opacity-100' }}">
                                                <i class="ri-checkbox-circle-line text-lg mb-0.5"></i>
                                                <span class="text-[10px] font-bold uppercase">Aktif</span>
                                            </button>
                                            
                                            <button type="button" 
                                                @click="$wire.set('status', 'Stok Habis')"
                                                class="flex flex-col items-center justify-center p-2 rounded-xl border-2 transition-all {{ $status === 'Stok Habis' ? 'border-warning bg-warning-subtle text-warning shadow-sm' : 'border-gray-100 bg-white text-gray-400 grayscale opacity-60 hover:grayscale-0 hover:opacity-100' }}">
                                                <i class="ri-error-warning-line text-lg mb-0.5"></i>
                                                <span class="text-[10px] font-bold uppercase">Habis</span>
                                            </button>

                                            <button type="button" 
                                                @click="$wire.set('status', 'Tidak Aktif')"
                                                class="flex flex-col items-center justify-center p-2 rounded-xl border-2 transition-all {{ $status === 'Tidak Aktif' ? 'border-red-500 bg-red-50 text-red-700 shadow-sm' : 'border-gray-100 bg-white text-gray-400 grayscale opacity-60 hover:grayscale-0 hover:opacity-100' }}">
                                                <i class="ri-close-circle-line text-lg mb-0.5"></i>
                                                <span class="text-[10px] font-bold uppercase">Off</span>
                                            </button>
                                        </div>
                                    </div>
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