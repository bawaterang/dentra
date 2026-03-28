@props([
    'model', 
    'options' => [], 
    'searchable' => false, 
    'placeholder' => 'Pilih opsi...', 
    'icon' => 'ri-list-check',
    'id' => null
])

<div x-data="{ 
    open: false, 
    search: '',
    selected: @entangle($model),
    options: {{ json_encode($options) }},
    get filteredOptions() {
        if (!this.search) return this.options;
        return this.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()));
    },
    get selectedLabel() {
        const opt = this.options.find(o => o.value == this.selected);
        return opt ? opt.label : '{{ $placeholder }}';
    },
    get selectedIcon() {
        const opt = this.options.find(o => o.value == this.selected);
        return opt && opt.icon ? opt.icon : '{{ $icon }} opacity-30';
    }
}" 
class="relative w-full custom-dropdown-container"
id="{{ $id ?? 'dropdown-' . bin2hex(random_bytes(4)) }}">
    <button type="button" @click="open = !open; if(open && $refs.searchInput) $nextTick(() => $refs.searchInput.focus())" 
        class="w-full flex items-center justify-between rounded-lg border text-sm px-4 h-[42px] focus:border-[#405189] transition-all overflow-hidden"
        :class="selected ? 'bg-white border-[#405189] text-[#405189] font-bold shadow-sm' : 'bg-gray-50/50 border-gray-200 text-gray-400'">
        <div class="flex items-center gap-2 overflow-hidden">
            <i :class="selectedIcon" class="text-lg shrink-0"></i>
            <span x-text="selectedLabel" class="truncate text-left"></span>
        </div>
        <i class="ri-arrow-down-s-line transition-transform duration-200 text-gray-400 shrink-0" :class="open ? 'rotate-180' : ''"></i>
    </button>
    
    <div x-show="open" @click.away="open = false" 
        class="absolute z-[1100] w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-2xl overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        style="display: none;">
        
        @if($searchable)
        <div class="p-2 border-b border-gray-50 bg-gray-50/30">
            <div class="relative">
                <input type="text" x-model="search" x-ref="searchInput" 
                    class="w-full rounded-lg border-gray-200 text-xs pl-8 pr-3 py-2 focus:border-[#405189] focus:ring-0 transition-all" 
                    placeholder="Cari...">
                <i class="ri-search-line absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
        @endif

        <div class="max-h-56 overflow-y-auto py-1 scrollbar-thin scrollbar-thumb-gray-200">
            <template x-for="option in filteredOptions" :key="option.value">
                <button type="button" @click="selected = option.value; open = false" 
                    class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-3 hover:bg-[#f3f6f9] transition-all group"
                    :class="selected == option.value ? 'bg-indigo-50/50 text-[#405189] font-bold' : 'text-gray-600'">
                    <div x-show="option.icon" class="w-8 h-8 rounded-lg flex items-center justify-center transition-all bg-gray-50 group-hover:bg-white shrink-0"
                        :class="selected == option.value ? 'bg-white shadow-sm' : ''">
                        <i :class="option.icon"></i>
                    </div>
                    <span x-text="option.label" class="truncate"></span>
                    <i x-show="selected == option.value" class="ri-check-line ms-auto text-indigo-500 shrink-0"></i>
                </button>
            </template>
            <div x-show="filteredOptions.length === 0" class="px-4 py-4 text-center text-xs text-gray-400 italic font-medium">
                Tidak ditemukan
            </div>
        </div>
        
        <button type="button" @click="selected = null; open = false" 
            class="w-full px-4 py-2.5 text-left text-xs text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all border-t border-gray-50 italic font-medium">
            Kosongkan Pilihan
        </button>
    </div>
</div>
