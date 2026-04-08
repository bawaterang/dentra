@props([
    'model',
    'searchMethod',
    'labelMethod' => null,
    'searchable' => true,
    'placeholder' => 'Pilih opsi...',
    'icon' => 'ri-list-check',
    'id' => null,
    'live' => false,
    'minChars' => 2
])

<div x-data="{
    open: false,
    search: '',
    selected: @entangle($model){{ $live ? '.live' : '' }},
    options: [],
    selectedLabel: '{{ $placeholder }}',
    selectedIcon: '{{ $icon }} opacity-30',
    loading: false,
    debounceTimer: null,
    initialized: false,

    async init() {
        // If there's already a selected value, fetch its label
        if (this.selected) {
            await this.fetchSelectedLabel();
        }
        this.$watch('selected', async (val) => {
            if (val) {
                await this.fetchSelectedLabel();
            } else {
                this.selectedLabel = '{{ $placeholder }}';
                this.selectedIcon = '{{ $icon }} opacity-30';
            }
        });
        this.initialized = true;
    },

    async fetchSelectedLabel() {
        @if($labelMethod)
        try {
            const result = await $wire.{{ $labelMethod }}(this.selected);
            if (result) {
                this.selectedLabel = result.label || '{{ $placeholder }}';
                this.selectedIcon = result.icon || '{{ $icon }}';
            }
        } catch(e) {
            console.warn('Failed to fetch label', e);
        }
        @else
        // Try to find in current options
        const opt = this.options.find(o => o.value == this.selected);
        if (opt) {
            this.selectedLabel = opt.label;
            this.selectedIcon = opt.icon || '{{ $icon }}';
        }
        @endif
    },

    async doSearch() {
        if (this.search.length < {{ $minChars }}) {
            this.options = [];
            return;
        }
        this.loading = true;
        try {
            const results = await $wire.{{ $searchMethod }}(this.search);
            this.options = results || [];
        } catch(e) {
            console.warn('Search failed', e);
            this.options = [];
        }
        this.loading = false;
    },

    onSearchInput() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.doSearch();
        }, 300);
    },

    selectOption(option) {
        this.selected = option.value;
        this.selectedLabel = option.label;
        this.selectedIcon = option.icon || '{{ $icon }}';
        this.open = false;
        this.search = '';
        this.options = [];
    }
}"
class="relative w-full custom-dropdown-container"
id="{{ $id ?? 'dropdown-async-' . str_replace('.', '-', $model) }}"
wire:key="dropdown-async-{{ str_replace('.', '-', $model) }}">
    <button type="button" @click="open = !open; if(open && $refs.searchInput) $nextTick(() => $refs.searchInput.focus())"
        class="w-full flex items-center justify-between rounded-lg border text-sm px-4 h-[42px] focus:border-[#405189] transition-all overflow-hidden"
        :class="selected ? 'bg-white border-[#405189] text-[#405189] font-bold shadow-sm' : 'bg-gray-50/50 border-gray-200 text-gray-400'">
        <div class="flex items-center gap-2 overflow-hidden">
            <i :class="selectedIcon" class="text-lg shrink-0"></i>
            <span x-text="selectedLabel" class="truncate text-left"></span>
        </div>
        <i class="ri-arrow-down-s-line transition-transform duration-200 text-gray-400 shrink-0" :class="open ? 'rotate-180' : ''"></i>
    </button>

    <div x-show="open" @click.away="open = false; search = ''; options = []"
        class="absolute z-[1100] w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-2xl overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        style="display: none;">

        <div class="p-2 border-b border-gray-50 bg-gray-50/30">
            <div class="relative">
                <input type="text" x-model="search" x-ref="searchInput" @input="onSearchInput()" @click.stop
                    class="w-full rounded-lg border-gray-200 text-xs pl-8 pr-3 py-2 focus:border-[#405189] focus:ring-0 transition-all"
                    placeholder="Ketik minimal {{ $minChars }} karakter untuk mencari...">
                <i class="ri-search-line absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" x-show="!loading"></i>
                <svg x-show="loading" class="animate-spin h-4 w-4 text-[#405189] absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>

        <div class="max-h-56 overflow-y-auto py-1 scrollbar-thin scrollbar-thumb-gray-200">
            <template x-for="option in options" :key="option.value">
                <button type="button" @click="selectOption(option)"
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

            <!-- Empty state: no search yet -->
            <div x-show="options.length === 0 && search.length < {{ $minChars }} && !loading" class="px-4 py-6 text-center">
                <i class="ri-search-eye-line text-2xl text-gray-200 block mb-1"></i>
                <p class="text-xs text-gray-400 italic font-medium">Ketik untuk mulai mencari...</p>
            </div>

            <!-- Empty state: no results -->
            <div x-show="options.length === 0 && search.length >= {{ $minChars }} && !loading" class="px-4 py-6 text-center">
                <i class="ri-file-unknow-line text-2xl text-gray-200 block mb-1"></i>
                <p class="text-xs text-gray-400 italic font-medium">Tidak ditemukan</p>
            </div>

            <!-- Loading state -->
            <div x-show="loading" class="px-4 py-4 text-center">
                <svg class="animate-spin h-5 w-5 text-[#405189] mx-auto mb-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-xs text-gray-400 font-medium">Mencari...</p>
            </div>
        </div>

        <button type="button" @click="selected = null; open = false; search = ''; options = []"
            class="w-full px-4 py-2.5 text-left text-xs text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all border-t border-gray-50 italic font-medium">
            Kosongkan Pilihan
        </button>
    </div>
</div>
