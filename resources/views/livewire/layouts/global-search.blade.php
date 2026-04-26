<div class="topbar-search" x-data="{ focused: false }" @click.outside="focused = false">
    {{-- Search Icon --}}
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8" />
        <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>

    {{-- Search Input --}}
    <input
        type="text"
        id="global-search-input"
        placeholder="Cari pasien, kunjungan, dokter, menu…"
        wire:model.live.debounce.300ms="query"
        @focus="focused = true"
        @keydown.escape="focused = false; $wire.clearSearch()"
        autocomplete="off"
    >

    {{-- Clear Button --}}
    @if(strlen($query) > 0)
        <button class="search-clear-btn" wire:click="clearSearch" title="Hapus pencarian">
            <i class="ri-close-line"></i>
        </button>
    @endif

    {{-- Loading Indicator --}}
    <div class="search-loading" wire:loading wire:target="query">
        <div class="search-loading-spinner"></div>
    </div>

    {{-- Results Dropdown --}}
    @if($showResults && $totalResults > 0)
        <div class="search-dropdown" x-show="focused" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1">

            <div class="search-dropdown-header">
                <span>{{ $totalResults }} hasil ditemukan</span>
                <kbd class="search-kbd">ESC</kbd>
            </div>

            <div class="search-dropdown-body">
                @foreach($results as $category => $items)
                    <div class="search-category">
                        <div class="search-category-label">{{ $this->getCategoryLabel($category) }}</div>
                        @foreach($items as $item)
                            <a href="{{ $item['url'] }}" wire:navigate class="search-result-item"
                               @click="focused = false; $wire.clearSearch()">
                                <div class="search-result-icon {{ $item['icon_bg'] }}">
                                    <i class="{{ $item['icon'] }}"></i>
                                </div>
                                <div class="search-result-content">
                                    <div class="search-result-title">{{ $item['title'] }}</div>
                                    <div class="search-result-subtitle">{{ $item['subtitle'] }}</div>
                                </div>
                                <div class="search-result-meta">{{ $item['meta'] }}</div>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @elseif(strlen($query) >= 2 && $totalResults === 0)
        <div class="search-dropdown" x-show="focused" x-cloak x-transition>
            <div class="search-empty">
                <i class="ri-search-eye-line"></i>
                <p>Tidak ada hasil untuk "<strong>{{ $query }}</strong>"</p>
                <span>Coba kata kunci lain seperti nama pasien, No. RM, atau menu</span>
            </div>
        </div>
    @endif
</div>
