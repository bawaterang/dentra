<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;
use App\Models\MstMenu;

class MenuPage extends Component
{
    public $menuId;
    public $menu_name, $menu_link, $menu_icon, $parent_id, $order_no, $is_active, $module_id;
    
    public $menuList = [];
    public $parentList = [];
    public $iconList = [];
    public $totalMenu = 0;
    public $menuAktif = 0;
    public $takAktif = 0;
    
    public $selectedStatus = 'all';
    public $isEdit = false;

    public function setStatus($status) { $this->selectedStatus = $status; $this->dispatch('refresh-table'); }

    protected function rules()
    {
        return [
            'menu_name' => 'required|string|max:100',
            'menu_link' => 'nullable|string|max:255',
            'menu_icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer',
            'order_no' => 'nullable|integer|min:0',
        ];
    }

    public function resetForm()
    {
        $this->reset(['menuId', 'menu_name', 'menu_link', 'menu_icon', 'parent_id', 'order_no', 'module_id', 'isEdit']);
        $this->is_active = true; $this->order_no = 0;
        $this->resetErrorBag();
    }

    public function create() { $this->resetForm(); $this->dispatch('open-modal'); $this->dispatch('refresh-table'); }

    public function edit($id)
    {
        $this->resetForm();
        $item = MstMenu::findOrFail($id);
        $this->menuId = $item->id; $this->menu_name = $item->menu_name; $this->menu_link = $item->menu_link;
        $this->menu_icon = $item->menu_icon; $this->parent_id = $item->parent_id; $this->order_no = $item->order_no;
        $this->is_active = $item->is_active; $this->module_id = $item->module_id;
        $this->isEdit = true; $this->dispatch('open-modal'); $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());
            $item = $this->menuId ? MstMenu::findOrFail($this->menuId) : new MstMenu();
            $item->fill(['menu_name' => $this->menu_name, 'menu_link' => $this->menu_link, 'menu_icon' => $this->menu_icon, 'parent_id' => $this->parent_id, 'order_no' => $this->order_no, 'is_active' => $this->is_active, 'module_id' => $this->module_id]);
            $item->save();
            $this->dispatch('close-modal'); $this->dispatch('refresh-table');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data menu berhasil diperbarui!' : 'Menu baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']); throw $e;
        } catch (\Exception $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]); }
    }

    public function delete($id)
    {
        $item = MstMenu::findOrFail($id);
        if (!$item->is_active) { $this->dispatch('alert', ['type' => 'info', 'message' => 'Menu yang sudah tidak aktif tidak dapat dihapus. Aktifkan terlebih dahulu.']); return; }
        $item->update(['is_active' => false]);
        $this->dispatch('refresh-table'); $this->dispatch('alert', ['type' => 'success', 'message' => 'Status menu telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $query = MstMenu::query();
        if ($this->selectedStatus === 'Aktif') { $query->where('is_active', true); }
        elseif ($this->selectedStatus === 'Tidak Aktif') { $query->where('is_active', false); }
        $this->menuList = $query->orderBy('order_no')->get();
        $this->totalMenu = MstMenu::count();
        $this->menuAktif = MstMenu::where('is_active', true)->count();
        $this->takAktif = MstMenu::where('is_active', false)->count();

        $this->parentList = MstMenu::whereNull('parent_id')->orWhere('parent_id', 0)->get()->map(fn($m) => [
            'value' => $m->id,
            'label' => $m->menu_name,
            'icon' => $m->menu_icon ?? 'ri-folder-line'
        ])->toArray();

        $this->iconList = [
            // System & Navigation
            ['value' => 'ri-dashboard-line', 'label' => 'Dashboard', 'icon' => 'ri-dashboard-line'],
            ['value' => 'ri-home-line', 'label' => 'Home', 'icon' => 'ri-home-line'],
            ['value' => 'ri-home-2-line', 'label' => 'Home 2', 'icon' => 'ri-home-2-line'],
            ['value' => 'ri-menu-line', 'label' => 'Menu', 'icon' => 'ri-menu-line'],
            ['value' => 'ri-menu-2-line', 'label' => 'Menu 2', 'icon' => 'ri-menu-2-line'],
            ['value' => 'ri-apps-line', 'label' => 'Apps', 'icon' => 'ri-apps-line'],
            ['value' => 'ri-layout-grid-line', 'label' => 'Grid Layout', 'icon' => 'ri-layout-grid-line'],
            ['value' => 'ri-layout-masonry-line', 'label' => 'Masonry', 'icon' => 'ri-layout-masonry-line'],
            ['value' => 'ri-settings-4-line', 'label' => 'Settings', 'icon' => 'ri-settings-4-line'],
            ['value' => 'ri-settings-3-line', 'label' => 'Settings 3', 'icon' => 'ri-settings-3-line'],
            ['value' => 'ri-tools-line', 'label' => 'Tools', 'icon' => 'ri-tools-line'],
            ['value' => 'ri-search-line', 'label' => 'Search', 'icon' => 'ri-search-line'],
            ['value' => 'ri-filter-3-line', 'label' => 'Filter', 'icon' => 'ri-filter-3-line'],
            ['value' => 'ri-logout-box-line', 'label' => 'Logout', 'icon' => 'ri-logout-box-line'],
            ['value' => 'ri-login-box-line', 'label' => 'Login', 'icon' => 'ri-login-box-line'],
            // User & People
            ['value' => 'ri-user-line', 'label' => 'User', 'icon' => 'ri-user-line'],
            ['value' => 'ri-user-heart-line', 'label' => 'User Heart', 'icon' => 'ri-user-heart-line'],
            ['value' => 'ri-user-star-line', 'label' => 'User Star', 'icon' => 'ri-user-star-line'],
            ['value' => 'ri-user-settings-line', 'label' => 'User Settings', 'icon' => 'ri-user-settings-line'],
            ['value' => 'ri-user-add-line', 'label' => 'User Add', 'icon' => 'ri-user-add-line'],
            ['value' => 'ri-group-line', 'label' => 'Group', 'icon' => 'ri-group-line'],
            ['value' => 'ri-team-line', 'label' => 'Team', 'icon' => 'ri-team-line'],
            ['value' => 'ri-admin-line', 'label' => 'Admin', 'icon' => 'ri-admin-line'],
            ['value' => 'ri-shield-user-line', 'label' => 'Shield User', 'icon' => 'ri-shield-user-line'],
            ['value' => 'ri-contacts-line', 'label' => 'Contacts', 'icon' => 'ri-contacts-line'],
            // Medical & Health
            ['value' => 'ri-health-book-line', 'label' => 'Health Book', 'icon' => 'ri-health-book-line'],
            ['value' => 'ri-heart-pulse-line', 'label' => 'Heart Pulse', 'icon' => 'ri-heart-pulse-line'],
            ['value' => 'ri-pulse-line', 'label' => 'Pulse', 'icon' => 'ri-pulse-line'],
            ['value' => 'ri-stethoscope-line', 'label' => 'Stethoscope', 'icon' => 'ri-stethoscope-line'],
            ['value' => 'ri-capsule-line', 'label' => 'Capsule', 'icon' => 'ri-capsule-line'],
            ['value' => 'ri-medicine-bottle-line', 'label' => 'Medicine Bottle', 'icon' => 'ri-medicine-bottle-line'],
            ['value' => 'ri-syringe-line', 'label' => 'Syringe', 'icon' => 'ri-syringe-line'],
            ['value' => 'ri-test-tube-line', 'label' => 'Test Tube', 'icon' => 'ri-test-tube-line'],
            ['value' => 'ri-flask-line', 'label' => 'Flask', 'icon' => 'ri-flask-line'],
            ['value' => 'ri-microscope-line', 'label' => 'Microscope', 'icon' => 'ri-microscope-line'],
            ['value' => 'ri-hospital-line', 'label' => 'Hospital', 'icon' => 'ri-hospital-line'],
            ['value' => 'ri-dossier-line', 'label' => 'Dossier', 'icon' => 'ri-dossier-line'],
            ['value' => 'ri-first-aid-kit-line', 'label' => 'First Aid Kit', 'icon' => 'ri-first-aid-kit-line'],
            ['value' => 'ri-mental-health-line', 'label' => 'Mental Health', 'icon' => 'ri-mental-health-line'],
            ['value' => 'ri-psychotherapy-line', 'label' => 'Psychotherapy', 'icon' => 'ri-psychotherapy-line'],
            ['value' => 'ri-hand-sanitizer-line', 'label' => 'Hand Sanitizer', 'icon' => 'ri-hand-sanitizer-line'],
            ['value' => 'ri-surgical-mask-line', 'label' => 'Surgical Mask', 'icon' => 'ri-surgical-mask-line'],
            // Business & Finance
            ['value' => 'ri-money-dollar-circle-line', 'label' => 'Dollar', 'icon' => 'ri-money-dollar-circle-line'],
            ['value' => 'ri-wallet-3-line', 'label' => 'Wallet', 'icon' => 'ri-wallet-3-line'],
            ['value' => 'ri-bank-card-line', 'label' => 'Bank Card', 'icon' => 'ri-bank-card-line'],
            ['value' => 'ri-shopping-cart-line', 'label' => 'Shopping Cart', 'icon' => 'ri-shopping-cart-line'],
            ['value' => 'ri-store-line', 'label' => 'Store', 'icon' => 'ri-store-line'],
            ['value' => 'ri-bill-line', 'label' => 'Bill', 'icon' => 'ri-bill-line'],
            ['value' => 'ri-exchange-dollar-line', 'label' => 'Exchange', 'icon' => 'ri-exchange-dollar-line'],
            ['value' => 'ri-percent-line', 'label' => 'Percent', 'icon' => 'ri-percent-line'],
            ['value' => 'ri-bar-chart-line', 'label' => 'Bar Chart', 'icon' => 'ri-bar-chart-line'],
            ['value' => 'ri-pie-chart-line', 'label' => 'Pie Chart', 'icon' => 'ri-pie-chart-line'],
            ['value' => 'ri-line-chart-line', 'label' => 'Line Chart', 'icon' => 'ri-line-chart-line'],
            // Documents & Files
            ['value' => 'ri-file-list-line', 'label' => 'File List', 'icon' => 'ri-file-list-line'],
            ['value' => 'ri-file-chart-line', 'label' => 'File Chart', 'icon' => 'ri-file-chart-line'],
            ['value' => 'ri-file-text-line', 'label' => 'File Text', 'icon' => 'ri-file-text-line'],
            ['value' => 'ri-file-copy-line', 'label' => 'File Copy', 'icon' => 'ri-file-copy-line'],
            ['value' => 'ri-file-excel-2-line', 'label' => 'Excel', 'icon' => 'ri-file-excel-2-line'],
            ['value' => 'ri-file-pdf-line', 'label' => 'PDF', 'icon' => 'ri-file-pdf-line'],
            ['value' => 'ri-folder-line', 'label' => 'Folder', 'icon' => 'ri-folder-line'],
            ['value' => 'ri-folder-open-line', 'label' => 'Folder Open', 'icon' => 'ri-folder-open-line'],
            ['value' => 'ri-clipboard-line', 'label' => 'Clipboard', 'icon' => 'ri-clipboard-line'],
            ['value' => 'ri-book-open-line', 'label' => 'Book Open', 'icon' => 'ri-book-open-line'],
            ['value' => 'ri-newspaper-line', 'label' => 'Newspaper', 'icon' => 'ri-newspaper-line'],
            ['value' => 'ri-printer-line', 'label' => 'Printer', 'icon' => 'ri-printer-line'],
            // Calendar & Time
            ['value' => 'ri-calendar-event-line', 'label' => 'Calendar Event', 'icon' => 'ri-calendar-event-line'],
            ['value' => 'ri-calendar-line', 'label' => 'Calendar', 'icon' => 'ri-calendar-line'],
            ['value' => 'ri-calendar-check-line', 'label' => 'Calendar Check', 'icon' => 'ri-calendar-check-line'],
            ['value' => 'ri-time-line', 'label' => 'Time', 'icon' => 'ri-time-line'],
            ['value' => 'ri-timer-line', 'label' => 'Timer', 'icon' => 'ri-timer-line'],
            ['value' => 'ri-history-line', 'label' => 'History', 'icon' => 'ri-history-line'],
            // Communication
            ['value' => 'ri-notification-3-line', 'label' => 'Notification', 'icon' => 'ri-notification-3-line'],
            ['value' => 'ri-mail-line', 'label' => 'Mail', 'icon' => 'ri-mail-line'],
            ['value' => 'ri-chat-3-line', 'label' => 'Chat', 'icon' => 'ri-chat-3-line'],
            ['value' => 'ri-phone-line', 'label' => 'Phone', 'icon' => 'ri-phone-line'],
            ['value' => 'ri-customer-service-2-line', 'label' => 'Customer Service', 'icon' => 'ri-customer-service-2-line'],
            ['value' => 'ri-question-line', 'label' => 'Question', 'icon' => 'ri-question-line'],
            ['value' => 'ri-information-line', 'label' => 'Information', 'icon' => 'ri-information-line'],
            // Database & Storage
            ['value' => 'ri-database-2-line', 'label' => 'Database', 'icon' => 'ri-database-2-line'],
            ['value' => 'ri-server-line', 'label' => 'Server', 'icon' => 'ri-server-line'],
            ['value' => 'ri-archive-line', 'label' => 'Archive', 'icon' => 'ri-archive-line'],
            ['value' => 'ri-inbox-line', 'label' => 'Inbox', 'icon' => 'ri-inbox-line'],
            ['value' => 'ri-cloud-line', 'label' => 'Cloud', 'icon' => 'ri-cloud-line'],
            ['value' => 'ri-hard-drive-2-line', 'label' => 'Hard Drive', 'icon' => 'ri-hard-drive-2-line'],
            // Map & Location
            ['value' => 'ri-map-pin-2-line', 'label' => 'Map Pin', 'icon' => 'ri-map-pin-2-line'],
            ['value' => 'ri-map-line', 'label' => 'Map', 'icon' => 'ri-map-line'],
            ['value' => 'ri-building-line', 'label' => 'Building', 'icon' => 'ri-building-line'],
            ['value' => 'ri-building-4-line', 'label' => 'Building 4', 'icon' => 'ri-building-4-line'],
            // Actions & UI
            ['value' => 'ri-add-circle-line', 'label' => 'Add Circle', 'icon' => 'ri-add-circle-line'],
            ['value' => 'ri-edit-line', 'label' => 'Edit', 'icon' => 'ri-edit-line'],
            ['value' => 'ri-delete-bin-line', 'label' => 'Delete', 'icon' => 'ri-delete-bin-line'],
            ['value' => 'ri-save-line', 'label' => 'Save', 'icon' => 'ri-save-line'],
            ['value' => 'ri-download-line', 'label' => 'Download', 'icon' => 'ri-download-line'],
            ['value' => 'ri-upload-line', 'label' => 'Upload', 'icon' => 'ri-upload-line'],
            ['value' => 'ri-refresh-line', 'label' => 'Refresh', 'icon' => 'ri-refresh-line'],
            ['value' => 'ri-check-double-line', 'label' => 'Check Double', 'icon' => 'ri-check-double-line'],
            ['value' => 'ri-checkbox-circle-line', 'label' => 'Checkbox Circle', 'icon' => 'ri-checkbox-circle-line'],
            ['value' => 'ri-close-circle-line', 'label' => 'Close Circle', 'icon' => 'ri-close-circle-line'],
            ['value' => 'ri-eye-line', 'label' => 'Eye', 'icon' => 'ri-eye-line'],
            ['value' => 'ri-lock-line', 'label' => 'Lock', 'icon' => 'ri-lock-line'],
            ['value' => 'ri-key-2-line', 'label' => 'Key', 'icon' => 'ri-key-2-line'],
            ['value' => 'ri-link', 'label' => 'Link', 'icon' => 'ri-link'],
            ['value' => 'ri-share-line', 'label' => 'Share', 'icon' => 'ri-share-line'],
            // Miscellaneous
            ['value' => 'ri-shield-check-line', 'label' => 'Shield Check', 'icon' => 'ri-shield-check-line'],
            ['value' => 'ri-star-line', 'label' => 'Star', 'icon' => 'ri-star-line'],
            ['value' => 'ri-bookmark-line', 'label' => 'Bookmark', 'icon' => 'ri-bookmark-line'],
            ['value' => 'ri-flag-line', 'label' => 'Flag', 'icon' => 'ri-flag-line'],
            ['value' => 'ri-hashtag', 'label' => 'Hashtag', 'icon' => 'ri-hashtag'],
            ['value' => 'ri-global-line', 'label' => 'Global', 'icon' => 'ri-global-line'],
            ['value' => 'ri-lightbulb-line', 'label' => 'Lightbulb', 'icon' => 'ri-lightbulb-line'],
            ['value' => 'ri-award-line', 'label' => 'Award', 'icon' => 'ri-award-line'],
            ['value' => 'ri-gift-line', 'label' => 'Gift', 'icon' => 'ri-gift-line'],
            ['value' => 'ri-node-tree', 'label' => 'Node Tree', 'icon' => 'ri-node-tree'],
            ['value' => 'ri-function-line', 'label' => 'Function', 'icon' => 'ri-function-line'],
            ['value' => 'ri-terminal-box-line', 'label' => 'Terminal', 'icon' => 'ri-terminal-box-line'],
            ['value' => 'ri-code-s-slash-line', 'label' => 'Code', 'icon' => 'ri-code-s-slash-line'],
            ['value' => 'ri-bug-line', 'label' => 'Bug', 'icon' => 'ri-bug-line'],
            ['value' => 'ri-image-line', 'label' => 'Image', 'icon' => 'ri-image-line'],
            ['value' => 'ri-video-line', 'label' => 'Video', 'icon' => 'ri-video-line'],
            ['value' => 'ri-music-2-line', 'label' => 'Music', 'icon' => 'ri-music-2-line'],
            ['value' => 'ri-attachment-line', 'label' => 'Attachment', 'icon' => 'ri-attachment-line'],
            ['value' => 'ri-qr-code-line', 'label' => 'QR Code', 'icon' => 'ri-qr-code-line'],
            ['value' => 'ri-barcode-line', 'label' => 'Barcode', 'icon' => 'ri-barcode-line'],
            ['value' => 'ri-fingerprint-line', 'label' => 'Fingerprint', 'icon' => 'ri-fingerprint-line'],
            ['value' => 'ri-speed-line', 'label' => 'Speed', 'icon' => 'ri-speed-line'],
            ['value' => 'ri-paint-brush-line', 'label' => 'Paint Brush', 'icon' => 'ri-paint-brush-line'],
            ['value' => 'ri-palette-line', 'label' => 'Palette', 'icon' => 'ri-palette-line'],
            ['value' => 'ri-recycle-line', 'label' => 'Recycle', 'icon' => 'ri-recycle-line'],
            ['value' => 'ri-truck-line', 'label' => 'Truck', 'icon' => 'ri-truck-line'],
            ['value' => 'ri-rocket-line', 'label' => 'Rocket', 'icon' => 'ri-rocket-line'],
            ['value' => 'ri-trophy-line', 'label' => 'Trophy', 'icon' => 'ri-trophy-line'],
        ];

        return <<<'HTML'
        <div x-data="{ showModal: false, initDataTable() { const t='#menuTable'; if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()} const tb=$(t).DataTable({scrollX:false,dom:'lrtip',language:{lengthMenu:'_MENU_',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',infoFiltered:'(disaring dari total _MAX_ data)',zeroRecords:'Tidak ada data yang ditemukan',emptyTable:'Tidak ada data dalam tabel',paginate:{previous:'<i class=ri-arrow-left-s-line></i>',next:'<i class=ri-arrow-right-s-line></i>'}}}); $('#customSearch').off('keyup').on('keyup',function(){tb.search(this.value).draw()}) }, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})} $nextTick(()=>this.initDataTable())}); $nextTick(()=>this.initDataTable())} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" @refresh-table.window="$nextTick(()=>initDataTable())" x-init="initDataTable()">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-menu-line"></i></div><h1>Menu</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-database-2-line"></i></a><span class="sep">/</span><a href="#">Master</a><span class="sep">/</span><span>Data Menu</span></div></div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #405189;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info"><i class="ri-menu-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Total Menu</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($totalMenu) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #0ab39c;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success"><i class="ri-checkbox-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Menu Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($menuAktif) }}</h4></div></div></div>
                <div class="card shadow-sm hover:shadow-md transition-all duration-300" style="border-top: 3px solid #f06548;"><div class="flex items-center p-5 gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-subtle text-danger"><i class="ri-close-circle-line text-xl"></i></div><div><p class="mb-1 text-[#878a99] font-medium text-xs uppercase tracking-wider">Tidak Aktif</p><h4 class="mb-0 font-bold text-2xl text-[#495057]">{{ number_format($takAktif) }}</h4></div></div></div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7] bg-white"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom"><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua Menu</span></a></li><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" wire:click="setStatus('Aktif')" role="button"><i class="ri-checkbox-circle-line"></i><span>Aktif</span></a></li><li class="nav-item"><a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" wire:click="setStatus('Tidak Aktif')" role="button"><i class="ri-close-circle-line"></i><span>Tidak Aktif</span></a></li></ul></div>
                    <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
                        <div class="relative flex-grow md:flex-none"><input type="text" id="customSearch" class="h-10 w-full md:w-64 rounded-lg bg-[#f3f6f9] border border-[#e9ecef] pl-10 pr-4 text-sm outline-none focus:border-[#405189] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari menu..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99] text-base"></i></div>
                        <div class="flex items-center gap-1.5 p-1 bg-[#f3f6f9] rounded-lg border border-[#e9ecef]"><a href="{{ route('master.menu.print', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-indigo-500 hover:bg-white hover:shadow-sm transition-all" title="Cetak PDF"><i class="ri-printer-line text-lg"></i></a><div class="w-[1px] h-4 bg-[#e9ecef]"></div><a href="{{ route('master.menu.export', ['status' => $selectedStatus]) }}" target="_blank" class="h-8 w-8 rounded-md flex items-center justify-center text-emerald-500 hover:bg-white hover:shadow-sm transition-all" title="Unduh Excel"><i class="ri-file-excel-2-line text-lg"></i></a></div>
                        <div class="hidden lg:block h-6 w-[1px] bg-[#e9ecef] mx-1"></div>
                        <button @click="$wire.create()" class="btn btn-primary h-10 px-5 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full sm:w-auto"><i class="ri-add-line text-lg"></i><span class="font-semibold text-xs uppercase tracking-wider">Tambah Menu</span></button>
                    </div>
                </div></div>
                <div class="card-body p-0"><div class="table-responsive bg-white">
                    <table id="menuTable" class="display w-full">
                    <thead><tr><th>Nama Menu</th><th>Link</th><th>Icon</th><th>Parent ID</th><th>Urutan</th><th>Status</th><th class="!text-center" style="text-align: center !important;">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($menuList as $item)
                        <tr wire:key="menu-{{ $item->id }}">
                            <td><span class="font-semibold text-[#405189]">{{ $item->menu_name }}</span></td>
                            <td>{{ $item->menu_link ?? '-' }}</td>
                            <td>@if($item->menu_icon)<i class="{{ $item->menu_icon }}"></i> {{ $item->menu_icon }}@else - @endif</td>
                            <td>{{ $item->parent_id ?? '-' }}</td>
                            <td>{{ $item->order_no }}</td>
                            <td><span class="badge {{ $item->is_active ? 'bg-success-subtle' : 'bg-danger-subtle' }}">{{ $item->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></td>
                            <td class="text-center"><div class="flex justify-center gap-2">
                                <button wire:click="edit({{ $item->id }})" class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all"><i class="ri-edit-line"></i></button>
                                <button @click="if(!{{ $item->is_active ? 'true' : 'false' }}){Swal.fire({title:'Informasi',text:'Menu yang sudah tidak aktif tidak dapat dihapus.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi',text:'Apakah Anda yakin ingin menonaktifkan menu ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan!',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $item->id }})}})}" class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all"><i class="ri-delete-bin-line"></i></button>
                            </div></td>
                        </tr>
                        @endforeach
                    </tbody></table>
                </div></div>
            </div>

            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]">{{ $isEdit ? 'Ubah Data Menu' : 'Tambah Menu Baru' }}</h5><button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-8 py-6 max-h-[75vh] overflow-y-auto">
                        <form wire:submit.prevent="save">
                            <div class="space-y-4">
                                <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Informasi Menu</h6>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nama Menu <span class="text-red-500">*</span></label><input type="text" wire:model="menu_name" x-ref="firstInput" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all @error('menu_name') border-red-400 @enderror" placeholder="Contoh: Dashboard">@error('menu_name') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror</div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Link/URL</label><input type="text" wire:model="menu_link" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="/dashboard"></div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Pilih Icon</label>
                                        <x-custom-dropdown 
                                            model="menu_icon" 
                                            :options="$iconList"
                                            placeholder="Pilih Icon Menu..."
                                            searchable="true"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Parent Menu</label>
                                        <x-custom-dropdown 
                                            model="parent_id" 
                                            :options="$parentList"
                                            placeholder="Pilih Parent (Root)"
                                            searchable="true"
                                            icon="ri-node-tree"
                                        />
                                    </div>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Urutan</label><input type="number" wire:model="order_no" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" min="0"></div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-2 hover:bg-gray-50 transition-colors"><div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full {{ $is_active ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div><span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">Status</span></div><div class="flex items-center gap-3"><span class="text-[10px] font-extrabold {{ $is_active ? 'text-green-600' : 'text-red-500' }}">{{ $is_active ? 'AKTIF' : 'TIDAK AKTIF' }}</span><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" class="sr-only peer" {{ $is_active ? 'checked' : '' }} @click="$wire.set('is_active', {{ $is_active ? 'false' : 'true' }})"><div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0ab39c]"></div></label></div></div>
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
