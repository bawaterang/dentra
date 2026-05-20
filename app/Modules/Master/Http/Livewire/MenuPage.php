<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstMenu;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class MenuPage extends Component
{
    use WithPagination;

    public $menuId;

    public $menu_name;

    public $menu_link;

    public $menu_icon;

    public $parent_id;

    public $order_no;

    public $is_active;

    public $module_id;

    public $totalMenu = 0;

    public $menuAktif = 0;

    public $takAktif = 0;

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $parentList = [];

    public $iconList = [];

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function menus()
    {
        $query = MstMenu::query();

        if ($this->selectedStatus === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($this->selectedStatus === 'Tidak Aktif') {
            $query->where('is_active', false);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('menu_name', 'like', '%'.$this->search.'%')
                    ->orWhere('menu_link', 'like', '%'.$this->search.'%')
                    ->orWhere('menu_icon', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('order_no')->paginate(10);
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
        $this->is_active = true;
        $this->order_no = 0;
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
        $item = MstMenu::findOrFail($id);
        $this->menuId = $item->id;
        $this->menu_name = $item->menu_name;
        $this->menu_link = $item->menu_link;
        $this->menu_icon = $item->menu_icon;
        $this->parent_id = $item->parent_id;
        $this->order_no = $item->order_no;
        $this->is_active = $item->is_active;
        $this->module_id = $item->module_id;
        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $item = $this->menuId ? MstMenu::findOrFail($this->menuId) : new MstMenu;
            $item->fill([
                'menu_name' => $this->menu_name,
                'menu_link' => $this->menu_link,
                'menu_icon' => $this->menu_icon,
                'parent_id' => $this->parent_id,
                'order_no' => $this->order_no,
                'is_active' => $this->is_active,
                'module_id' => $this->module_id,
            ]);
            $item->save();

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data menu berhasil diperbarui!' : 'Menu baru berhasil ditambahkan!']);
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
        $item = MstMenu::findOrFail($id);
        if (! $item->is_active) {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Menu yang sudah tidak aktif tidak dapat dihapus. Aktifkan terlebih dahulu.']);

            return;
        }
        $item->update(['is_active' => false]);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status menu telah diubah menjadi Tidak Aktif!']);
    }

    public function render()
    {
        $this->totalMenu = MstMenu::count();
        $this->menuAktif = MstMenu::where('is_active', true)->count();
        $this->takAktif = MstMenu::where('is_active', false)->count();

        $this->parentList = MstMenu::whereNull('parent_id')->orWhere('parent_id', 0)->get()->map(fn ($m) => [
            'value' => $m->id,
            'label' => $m->menu_name,
            'icon' => $m->menu_icon ?? 'ri-folder-line',
        ])->toArray();

        $this->iconList = [
            ['value' => 'ri-dashboard-line', 'label' => 'Dashboard', 'icon' => 'ri-dashboard-line'],
            ['value' => 'ri-home-line', 'label' => 'Home', 'icon' => 'ri-home-line'],
            ['value' => 'ri-menu-line', 'label' => 'Menu', 'icon' => 'ri-menu-line'],
            ['value' => 'ri-settings-4-line', 'label' => 'Settings', 'icon' => 'ri-settings-4-line'],
            ['value' => 'ri-user-line', 'label' => 'User', 'icon' => 'ri-user-line'],
            ['value' => 'ri-group-line', 'label' => 'Group', 'icon' => 'ri-group-line'],
            ['value' => 'ri-health-book-line', 'label' => 'Health Book', 'icon' => 'ri-health-book-line'],
            ['value' => 'ri-capsule-line', 'label' => 'Capsule', 'icon' => 'ri-capsule-line'],
            ['value' => 'ri-money-dollar-circle-line', 'label' => 'Dollar', 'icon' => 'ri-money-dollar-circle-line'],
            ['value' => 'ri-file-list-line', 'label' => 'File List', 'icon' => 'ri-file-list-line'],
            ['value' => 'ri-calendar-line', 'label' => 'Calendar', 'icon' => 'ri-calendar-line'],
            ['value' => 'ri-notification-3-line', 'label' => 'Notification', 'icon' => 'ri-notification-3-line'],
            ['value' => 'ri-database-2-line', 'label' => 'Database', 'icon' => 'ri-database-2-line'],
            ['value' => 'ri-shield-check-line', 'label' => 'Shield Check', 'icon' => 'ri-shield-check-line'],
            ['value' => 'ri-delete-bin-line', 'label' => 'Delete', 'icon' => 'ri-delete-bin-line'],
            ['value' => 'ri-edit-line', 'label' => 'Edit', 'icon' => 'ri-edit-line'],
        ];

        return view('livewire.modules.master.menu-page');
    }
}
