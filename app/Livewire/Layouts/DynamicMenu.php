<?php

namespace App\Livewire\Layouts;

use App\Models\MstMenu;
use Livewire\Component;

class DynamicMenu extends Component
{
    public function render()
    {
        // Fetch only active parent menus (parent_id is null) ordered by order_no
        // Eager load active submenus as well
        $menus = MstMenu::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['submenus' => function ($query) {
                $query->where('is_active', true)->orderBy('order_no');
            }])
            ->orderBy('order_no')
            ->get();

        return view('livewire.layouts.dynamic-menu', [
            'menus' => $menus
        ]);
    }
}
