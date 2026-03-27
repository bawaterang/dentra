<nav class="module-nav">
    @foreach($menus as $menu)
        @if($menu->submenus->isEmpty())
            {{-- Horizontal Menu Item without Submenu --}}
            <a href="{{ $menu->menu_link !== '#' ? $menu->menu_link : '#' }}"
               wire:navigate
               class="{{ request()->is(ltrim($menu->menu_link, '/').'*') && $menu->menu_link !== '/' ? 'active' : '' }}">
                @if($menu->menu_icon)
                    <i class="{{ $menu->menu_icon }}" style="font-size: 16px; margin-right: 4px;"></i>
                @endif
                <span>{{ $menu->menu_name }}</span>
            </a>
        @else
            {{-- Horizontal Menu Item WITH Submenu (Dropdown) --}}
            <div x-data="{ open: false }" 
                 @mouseenter="open = true" 
                 @mouseleave="open = false" 
                 class="relative flex items-center h-full max-md:static">
                
                {{-- Backdrop Overlay (Teleported to body to stay behind the navbar) --}}
                <template x-teleport="body">
                    <div x-show="open" 
                         x-transition:enter="transition opacity ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition opacity ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 bg-black/30 backdrop-blur-[1px] z-[999] pointer-events-none md:hidden"
                         style="display: none;"></div>
                </template>

                @php
                    $isParentActive = $menu->submenus->contains(fn($s) => request()->is(ltrim($s->menu_link, '/') . '*'));
                @endphp

                <a href="#" class="flex items-center gap-1.5 px-3.5 h-full text-[13px] font-medium text-[var(--text-muted)] no-underline border-b-2 border-transparent transition-colors duration-150 whitespace-nowrap hover:text-[var(--text-heading)] cursor-pointer {{ $isParentActive ? 'active' : '' }}">
                    @if($menu->menu_icon)
                        <i class="{{ $menu->menu_icon }}" style="font-size: 16px; margin-right: 4px;"></i>
                    @endif
                    <span>{{ $menu->menu_name }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1 opacity-70"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </a>

                {{-- Dropdown Panel --}}
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 top-full mt-0 w-56 rounded-md bg-[var(--bg-card)] shadow-lg focus:outline-none z-[1001] py-1 border border-[var(--bg-card)]"
                     style="display: none;">
                    
                    @foreach($menu->submenus as $submenu)
                        <a href="{{ $submenu->menu_link !== '#' ? $submenu->menu_link : '#' }}"
                           wire:navigate
                           class="group flex items-center px-4 py-2 text-[13px] text-[var(--text-heading)] hover:bg-[var(--icon-hover)] transition-colors {{ request()->is(ltrim($submenu->menu_link, '/').'*') ? 'bg-[var(--icon-hover)] font-medium text-[#6691e7]' : '' }}">
                            @if($submenu->menu_icon)
                                <i class="{{ $submenu->menu_icon }} mr-2 text-[var(--text-muted)] group-hover:text-[var(--text-heading)] transition-colors"></i>
                            @endif
                            {{ $submenu->menu_name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</nav>
