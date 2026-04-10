<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use App\Models\MstRoleUser;
use App\Models\MstMenu;
use App\Models\MstRoleUserAccess;

class AksesMenuPage extends Component
{
    public $selectedRoleId = null;
    public $access = [];

    public function updatedSelectedRoleId($value)
    {
        $this->loadAccess($value);
    }

    public function loadAccess($roleId)
    {
        $this->access = [];
        if (!$roleId) return;

        $permissions = MstRoleUserAccess::where('role_id', $roleId)->get();
        foreach ($permissions as $p) {
            $this->access[$p->menu_id] = [
                'can_view' => (bool)$p->can_view,
                'can_create' => (bool)$p->can_create,
                'can_update' => (bool)$p->can_update,
                'can_delete' => (bool)$p->can_delete,
            ];
        }
    }

    public function toggleAllRow($menuId, $value)
    {
        $this->access[$menuId] = [
            'can_view' => $value,
            'can_create' => $value,
            'can_update' => $value,
            'can_delete' => $value,
        ];
    }

    public function toggleAllColumn($column, $value)
    {
        $menus = MstMenu::all();
        foreach ($menus as $m) {
            if (!isset($this->access[$m->id])) {
                $this->access[$m->id] = ['can_view' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false];
            }
            $this->access[$m->id][$column] = $value;
        }
    }

    public function saveAccess()
    {
        if (!$this->selectedRoleId) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Pilih role terlebih dahulu!']);
            return;
        }

        $menus = MstMenu::all();
        foreach ($menus as $menu) {
            $row = $this->access[$menu->id] ?? [];
            $can_view = $row['can_view'] ?? false;
            $can_create = $row['can_create'] ?? false;
            $can_update = $row['can_update'] ?? false;
            $can_delete = $row['can_delete'] ?? false;

            if ($can_view || $can_create || $can_update || $can_delete) {
                MstRoleUserAccess::updateOrCreate(
                    ['role_id' => $this->selectedRoleId, 'menu_id' => $menu->id],
                    [
                        'can_view' => $can_view,
                        'can_create' => $can_create,
                        'can_update' => $can_update,
                        'can_delete' => $can_delete,
                    ]
                );
            } else {
                MstRoleUserAccess::where('role_id', $this->selectedRoleId)->where('menu_id', $menu->id)->delete();
            }
        }

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Hak akses menu berhasil disimpan!']);
        $this->loadAccess($this->selectedRoleId);
    }

    public function render()
    {
        $allRoles = MstRoleUser::orderBy('nama_role')->get();
        // Load only parent menus and their active children
        $menus = MstMenu::whereNull('parent_id')
            ->orderBy('order_no')
            ->with(['submenus' => function($q) {
                $q->orderBy('order_no');
            }])
            ->get();

        return view('livewire.akses-menu-page', compact('allRoles', 'menus'));
    }
}
