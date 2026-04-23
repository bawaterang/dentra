<div x-data="{ sidebarOpen: true }" class="min-h-[calc(100vh-200px)]">
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon"><i class="ri-book-open-line"></i></div>
            <h1>Dokumentasi API BPJS PCare</h1>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
            <span class="sep text-gray-300">/</span>
            <span class="text-gray-400 font-medium">Bridging</span>
            <span class="sep text-gray-300">/</span>
            <span class="text-[#405189] font-bold">Dokumentasi API</span>
        </div>
    </div>

    <div class="mt-6 flex flex-col lg:flex-row gap-6">
        {{-- ============================================================ --}}
        {{-- SIDEBAR: Endpoint Selector --}}
        {{-- ============================================================ --}}
        <div class="w-full lg:w-72 flex-shrink-0">
            {{-- Mobile Toggle --}}
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden w-full mb-3 btn bg-[#0d6efd] text-white font-bold text-xs uppercase tracking-widest h-10 px-4 shadow-md flex items-center justify-between rounded-xl">
                <span class="flex items-center gap-2"><i class="ri-menu-line text-lg"></i> Pilih Endpoint</span>
                <i class="ri-arrow-down-s-line text-lg transition-transform" :class="sidebarOpen ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="card shadow-sm rounded-xl border border-gray-100 overflow-hidden sticky top-4">
                <div class="p-4 bg-gradient-to-r from-[#0d6efd] to-[#405189] border-b">
                    <h5 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <i class="ri-api-line text-lg"></i> Endpoint API PCare
                    </h5>
                    <p class="text-blue-200 text-[10px] mt-1 font-medium">Pilih endpoint untuk melakukan testing</p>
                </div>

                <div class="p-3 max-h-[calc(100vh-380px)] overflow-y-auto scrollbar-thin">
                    @foreach($this->groupedEndpoints as $category => $endpoints)
                    <div class="mb-3">
                        <h6 class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-2 mb-1.5">{{ $category }}</h6>
                        @foreach($endpoints as $key => $ep)
                        <button
                            wire:click="$set('selectedEndpoint', '{{ $key }}')"
                            class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 flex items-center gap-2.5 group mb-0.5
                                {{ $selectedEndpoint === $key
                                    ? 'bg-[#0d6efd]/10 text-[#0d6efd] border border-[#0d6efd]/20 shadow-sm'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 border border-transparent' }}"
                        >
                            <div class="h-7 w-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all
                                {{ $selectedEndpoint === $key ? 'bg-[#0d6efd] text-white shadow-sm' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200' }}">
                                <i class="{{ $ep['icon'] }} text-sm"></i>
                            </div>
                            <span class="truncate">{{ $ep['label'] }}</span>
                            @if($ep['method'] !== 'GET')
                            <span class="ml-auto text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded
                                {{ $ep['method'] === 'POST' ? 'bg-emerald-100 text-emerald-600' : '' }}
                                {{ $ep['method'] === 'PUT' ? 'bg-amber-100 text-amber-600' : '' }}
                                {{ $ep['method'] === 'DELETE' ? 'bg-red-100 text-red-600' : '' }}
                            ">{{ $ep['method'] }}</span>
                            @endif
                            @if($selectedEndpoint === $key)
                            <i class="ri-arrow-right-s-line ml-auto text-[#0d6efd]"></i>
                            @endif
                        </button>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- MAIN CONTENT --}}
        {{-- ============================================================ --}}
        <div class="flex-1 min-w-0">
            @if($this->currentEndpoint)
            @php $ep = $this->currentEndpoint; @endphp

            {{-- Endpoint Info Header --}}
            <div class="card shadow-sm rounded-xl border border-gray-100 overflow-hidden border-t-4" style="border-top-color: {{ $ep['color'] }};">
                <div class="p-5 bg-[#f3f6f9]/50 border-b border-gray-100">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl flex items-center justify-center text-white shadow-md" style="background: {{ $ep['color'] }};">
                                <i class="{{ $ep['icon'] }} text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-gray-800">{{ $ep['label'] }}</h3>
                                <p class="text-[11px] text-gray-500 font-medium">{{ $ep['description'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider">{{ $ep['method'] }}</span>
                            @if($responseTime > 0)
                            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-lg text-[10px] font-bold">
                                <i class="ri-timer-line"></i> {{ $responseTime }}ms
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3">
                        <code class="text-xs font-mono px-3 py-1.5 rounded-lg block break-all border border-gray-200 bg-white text-gray-700">
                            <span class="text-gray-400">{{ $ep['method'] }}</span> <span style="color: {{ $ep['color'] }};">{{ $ep['endpoint'] }}</span>
                        </code>
                    </div>
                </div>

                {{-- Parameters Form --}}
                <div class="p-5 border-b border-gray-100 bg-white">
                    <h6 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="ri-settings-4-line text-sm"></i> Parameter Request
                    </h6>

                    <div class="flex flex-wrap items-end gap-3">
                        @if(in_array('start', $ep['params']))
                        <div class="flex-1 min-w-[120px] max-w-[160px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Start (offset)</label>
                            <input type="number" wire:model="paramStart" min="0" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm text-center">
                        </div>
                        @endif

                        @if(in_array('limit', $ep['params']))
                        <div class="flex-1 min-w-[100px] max-w-[140px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Limit</label>
                            <select wire:model="paramLimit" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        @endif

                        @if(in_array('keyword', $ep['params']))
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Keyword</label>
                            <input type="text" wire:model="paramKeyword" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm" placeholder="Kata kunci pencarian...">
                        </div>
                        @endif

                        @if(in_array('noKartu', $ep['params']))
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">No. Kartu BPJS</label>
                            <input type="text" wire:model="paramNoKartu" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm" placeholder="0001234567890">
                        </div>
                        @endif

                        @if(in_array('nik', $ep['params']))
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">NIK</label>
                            <input type="text" wire:model="paramNik" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm" placeholder="3201234567890001">
                        </div>
                        @endif

                        @if(in_array('noKunjungan', $ep['params']))
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">No. Kunjungan</label>
                            <input type="text" wire:model="paramNoKunjungan" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm" placeholder="Nomor kunjungan...">
                        </div>
                        @endif

                        @if(in_array('isRawatInap', $ep['params']))
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Status Rawat Inap</label>
                            <select wire:model="paramIsRawatInap" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm">
                                <option value="0">False (Bukan Rawat Inap)</option>
                                <option value="1">True (Rawat Inap)</option>
                            </select>
                        </div>
                        @endif

                        @if(in_array('kdTkp', $ep['params']))
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Tingkat Pelayanan (kdTkp)</label>
                            <select wire:model="paramKdTkp" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm">
                                <option value="10">10 - RJTP (Rawat Jalan)</option>
                                <option value="20">20 - RITP (Rawat Inap)</option>
                                <option value="50">50 - Promotif</option>
                            </select>
                        </div>
                        @endif

                        @if(in_array('kodepoli', $ep['params']))
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Kode Poli</label>
                            <input type="text" wire:model="paramKodePoli" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm" placeholder="Contoh: 001">
                        </div>
                        @endif

                        @if(in_array('tanggal', $ep['params']))
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Tanggal</label>
                            <input type="date" wire:model="paramTanggal" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm">
                        </div>
                        @endif

                        @if(in_array('noKunjunganBpjs', $ep['params']))
                        <div class="flex-1 min-w-[250px]">
                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">No. Kunjungan BPJS</label>
                            <input type="text" wire:model="paramNoKunjunganBpjs" class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm" placeholder="0114U1630316Y000001">
                        </div>
                        @endif

                        @if(in_array('kunjungan_body', $ep['params']))
                        </div>
                        {{-- Kunjungan Body: Patient Search + JSON Textarea --}}
                        <div class="mt-4 border-t border-gray-100 pt-4" x-data="{ showResults: false }">
                            {{-- Patient Search --}}
                            <div class="mb-4">
                                <h6 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                                    <i class="ri-user-search-line text-sm"></i> Cari Pasien dari Aplikasi
                                </h6>
                                <div class="flex gap-2 items-end relative">
                                    <div class="flex-1 relative">
                                        <input
                                            type="text"
                                            wire:model.live.debounce.300ms="searchPasienQuery"
                                            wire:keyup="searchPasien"
                                            @focus="showResults = true"
                                            @click.away="setTimeout(() => showResults = false, 200)"
                                            class="w-full rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm pl-9"
                                            placeholder="Cari nama pasien, No. RM, atau No. Kartu BPJS..."
                                        >
                                        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>

                                        {{-- Search Results Dropdown --}}
                                        @if(count($foundPasiens) > 0)
                                        <div
                                            x-show="showResults"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-[280px] overflow-y-auto"
                                        >
                                            @foreach($foundPasiens as $p)
                                            <button
                                                wire:click="selectPasien({{ $p['id'] }})"
                                                @click="showResults = false"
                                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50/80 border-b border-gray-50 last:border-0 transition-colors group"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-bold text-gray-800 truncate">{{ $p['nama_pasien'] }}</span>
                                                            <span class="text-[9px] font-semibold text-blue-500 bg-blue-50 px-1.5 py-0.5 rounded">{{ $p['no_rm'] }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-3 mt-0.5">
                                                            <span class="text-[9px] text-gray-400"><i class="ri-bank-card-line mr-0.5"></i>{{ $p['no_kartu'] }}</span>
                                                            <span class="text-[9px] text-gray-400"><i class="ri-hospital-line mr-0.5"></i>{{ $p['poli'] }}</span>
                                                            <span class="text-[9px] text-gray-400"><i class="ri-stethoscope-line mr-0.5"></i>{{ $p['dokter'] }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right flex-shrink-0 ml-3">
                                                        <span class="text-[9px] text-gray-400 block">{{ $p['tanggal'] }}</span>
                                                        <span class="text-[8px] font-mono text-gray-300">{{ $p['nomor_kunjungan'] }}</span>
                                                    </div>
                                                </div>
                                            </button>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-1.5 italic">
                                    <i class="ri-information-line"></i> Pilih pasien untuk otomatis mengisi body request dari data kunjungan aplikasi
                                </p>
                            </div>

                            {{-- JSON Body Editor --}}
                            <div class="mb-3">
                                <div class="flex items-center justify-between mb-2">
                                    <h6 class="text-[10px] font-black text-gray-500 uppercase tracking-widest flex items-center gap-2">
                                        <i class="ri-code-s-slash-line text-sm"></i> Body Request (JSON)
                                    </h6>
                                    <button
                                        wire:click="generateDefaultBody"
                                        class="text-[9px] font-bold text-indigo-500 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition-all flex items-center gap-1"
                                    >
                                        <i class="ri-file-code-line text-xs"></i> Generate Template
                                    </button>
                                </div>
                                <textarea
                                    wire:model="kunjunganBodyJson"
                                    rows="20"
                                    class="w-full rounded-xl border-gray-200 text-xs px-4 py-3 font-mono leading-relaxed focus:border-[#0d6efd] transition-all shadow-sm bg-gray-50/50 resize-y"
                                    placeholder='Klik "Generate Template" atau cari pasien untuk mengisi body JSON...'
                                    spellcheck="false"
                                ></textarea>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-end gap-3 mt-4">
                        @endif

                        @if(empty($ep['params']))
                        <div class="flex-1">
                            <p class="text-xs text-gray-400 font-medium italic"><i class="ri-information-line"></i> Endpoint ini tidak memerlukan parameter.</p>
                        </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="executeEndpoint"
                                wire:loading.attr="disabled"
                                wire:target="executeEndpoint"
                                class="btn text-white font-bold text-xs uppercase tracking-widest h-10 px-5 shadow-md hover:translate-y-[-2px] transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg"
                                style="background: {{ $ep['color'] }};"
                            >
                                <span wire:loading.remove wire:target="executeEndpoint">
                                    <i class="ri-play-circle-line mr-1"></i> Jalankan
                                </span>
                                <span wire:loading wire:target="executeEndpoint">
                                    <i class="ri-loader-4-line mr-1 animate-spin"></i> Loading...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Headers Info (collapsible) --}}
                <details class="border-b border-gray-100">
                    <summary class="px-5 py-3 cursor-pointer text-[10px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2 hover:bg-gray-50/50 transition-colors select-none">
                        <i class="ri-shield-keyhole-line text-sm"></i> Headers Required
                        <i class="ri-arrow-down-s-line ml-auto text-sm"></i>
                    </summary>
                    <div class="px-5 pb-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $ep['category'] === 'Antrean' ? '4' : '5' }} gap-2">
                            <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                <span class="text-[10px] font-bold text-[#0d6efd] block">X-cons-id</span>
                                <span class="text-[10px] text-gray-400">Consumer ID</span>
                            </div>
                            <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                <span class="text-[10px] font-bold text-[#0d6efd] block">X-timestamp</span>
                                <span class="text-[10px] text-gray-400">UTC Unix Timestamp</span>
                            </div>
                            <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                <span class="text-[10px] font-bold text-[#0d6efd] block">X-signature</span>
                                <span class="text-[10px] text-gray-400">HMAC-SHA256 + Base64</span>
                            </div>
                            @if($ep['category'] !== 'Antrean')
                            <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                <span class="text-[10px] font-bold text-[#0d6efd] block">X-authorization</span>
                                <span class="text-[10px] text-gray-400">Basic Auth (User:Pass:KdApp)</span>
                            </div>
                            @endif
                            <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                <span class="text-[10px] font-bold text-[#0d6efd] block">user_key</span>
                                <span class="text-[10px] text-gray-400">User Key BPJS</span>
                            </div>
                        </div>
                    </div>
                </details>
            </div>

            {{-- ============================================================ --}}
            {{-- RESPONSE Section --}}
            {{-- ============================================================ --}}

            {{-- Error Alert --}}
            @if($errorMessage)
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                <div class="h-8 w-8 rounded-lg bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0 mt-0.5">
                    <i class="ri-error-warning-line text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h6 class="text-xs font-bold text-red-700 uppercase tracking-wider mb-1">Error Response</h6>
                    <p class="text-sm text-red-600 font-medium break-all">{{ $errorMessage }}</p>
                    @if($rawResponse)
                    <details class="mt-2">
                        <summary class="text-xs text-red-500 font-bold uppercase tracking-wider cursor-pointer hover:text-red-700">
                            <i class="ri-code-line"></i> Raw Response
                        </summary>
                        <pre class="mt-2 p-3 bg-red-100/50 rounded-lg text-xs text-red-700 overflow-x-auto max-h-40 font-mono break-all whitespace-pre-wrap">{{ $rawResponse }}</pre>
                    </details>
                    @endif
                </div>
            </div>
            @endif

            {{-- Success Alert --}}
            @if($successMessage && !$errorMessage)
            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                <div class="h-8 w-8 rounded-lg bg-green-100 flex items-center justify-center text-green-600 flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-lg"></i>
                </div>
                <div>
                    <h6 class="text-xs font-bold text-green-700 uppercase tracking-wider mb-1">Berhasil</h6>
                    <p class="text-sm text-green-600 font-medium">{{ $successMessage }}</p>
                    <div class="flex flex-wrap items-center gap-4 mt-2">
                        @if($responseMetaCode)
                        <span class="text-[10px] font-bold text-green-500 uppercase tracking-widest">
                            <i class="ri-hashtag"></i> Code: {{ $responseMetaCode }}
                        </span>
                        @endif
                        @if($responseMetaMessage)
                        <span class="text-[10px] font-bold text-green-500 uppercase tracking-widest">
                            <i class="ri-message-2-line"></i> {{ $responseMetaMessage }}
                        </span>
                        @endif
                        @if($lastFetched)
                        <span class="text-[10px] font-bold text-green-500 uppercase tracking-widest">
                            <i class="ri-time-line"></i> {{ $lastFetched }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Response Data Table --}}
            @if(!empty($responseData))
            <div class="card shadow-sm rounded-xl mt-4 border border-gray-100 overflow-hidden">
                <div class="p-4 bg-[#f3f6f9]/50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h5 class="text-xs font-bold uppercase tracking-widest flex items-center gap-2" style="color: {{ $ep['color'] }};">
                        <i class="ri-table-line text-lg"></i> Response Data
                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px] font-bold ml-1">{{ $totalData }} record</span>
                    </h5>

                    @if(in_array('start', $ep['params']))
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="prevPage"
                            @if($paramStart <= 0) disabled @endif
                            class="h-8 px-3 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-1
                                {{ $paramStart > 0 ? 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 active:scale-95' : 'bg-gray-100 text-gray-300 cursor-not-allowed' }}"
                        >
                            <i class="ri-arrow-left-s-line"></i> Prev
                        </button>
                        <span class="h-8 px-3 rounded-lg text-white text-[10px] font-bold flex items-center shadow-sm" style="background: {{ $ep['color'] }};">
                            {{ floor($paramStart / $paramLimit) + 1 }}
                        </span>
                        <button
                            wire:click="nextPage"
                            @if(count($responseData) < $paramLimit) disabled @endif
                            class="h-8 px-3 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-1
                                {{ count($responseData) >= $paramLimit ? 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 active:scale-95' : 'bg-gray-100 text-gray-300 cursor-not-allowed' }}"
                        >
                            Next <i class="ri-arrow-right-s-line"></i>
                        </button>
                    </div>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <th class="px-3 py-2.5 font-bold text-gray-700 uppercase tracking-widest text-[10px] w-10">#</th>
                                @foreach($responseColumns as $col)
                                <th class="px-3 py-2.5 font-bold text-gray-700 uppercase tracking-widest text-[10px] whitespace-nowrap">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($responseData as $index => $row)
                            <tr class="hover:bg-blue-50/30 transition-colors" wire:key="row-{{ $index }}">
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-50 text-blue-600 text-[10px] font-bold">
                                        {{ $paramStart + $index + 1 }}
                                    </span>
                                </td>
                                @foreach($responseColumns as $col)
                                <td class="px-3 py-2.5 text-xs text-gray-700 font-medium max-w-[300px] truncate" title="{{ is_array($row[$col] ?? '') ? json_encode($row[$col]) : ($row[$col] ?? '-') }}">
                                    @if(is_array($row[$col] ?? null))
                                        <code class="text-[10px] bg-gray-100 px-1.5 py-0.5 rounded text-gray-600">{{ json_encode($row[$col]) }}</code>
                                    @elseif(is_bool($row[$col] ?? null))
                                        <span class="badge {{ ($row[$col]) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} px-2 py-0.5 rounded text-[10px] font-bold">
                                            {{ ($row[$col]) ? 'true' : 'false' }}
                                        </span>
                                    @else
                                        {{ $row[$col] ?? '-' }}
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(in_array('start', $ep['params']))
                <div class="p-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-3 bg-gray-50/30">
                    <div class="text-[10px] text-gray-500 font-medium">
                        Menampilkan <strong class="text-gray-700">{{ $paramStart + 1 }}</strong> - <strong class="text-gray-700">{{ $paramStart + count($responseData) }}</strong>
                        dari total <strong style="color: {{ $ep['color'] }};">{{ $totalData }}</strong> data
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="prevPage"
                            @if($paramStart <= 0) disabled @endif
                            class="h-8 px-3 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-1
                                {{ $paramStart > 0 ? 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 active:scale-95' : 'bg-gray-100 text-gray-300 cursor-not-allowed' }}"
                        >
                            <i class="ri-arrow-left-s-line"></i> Sebelumnya
                        </button>
                        <span class="h-8 px-3 rounded-lg text-white text-[10px] font-bold flex items-center shadow-sm" style="background: {{ $ep['color'] }};">
                            {{ floor($paramStart / $paramLimit) + 1 }}
                        </span>
                        <button
                            wire:click="nextPage"
                            @if(count($responseData) < $paramLimit) disabled @endif
                            class="h-8 px-3 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-1
                                {{ count($responseData) >= $paramLimit ? 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 active:scale-95' : 'bg-gray-100 text-gray-300 cursor-not-allowed' }}"
                        >
                            Berikutnya <i class="ri-arrow-right-s-line"></i>
                        </button>
                    </div>
                </div>
                @endif
            </div>
            @elseif(!$errorMessage && !$successMessage)
            {{-- Empty State --}}
            <div class="card shadow-sm rounded-xl mt-4 border border-gray-100 overflow-hidden">
                <div class="p-12 text-center">
                    <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center mx-auto mb-5">
                        <i class="{{ $ep['icon'] }} text-4xl" style="color: {{ $ep['color'] }};"></i>
                    </div>
                    <p class="text-gray-500 font-bold text-sm mb-1">{{ $ep['label'] }}</p>
                    <p class="text-gray-300 font-medium text-xs max-w-md mx-auto">
                        {{ $ep['description'] }}<br>
                        Klik tombol <strong class="text-[#0d6efd]">"Jalankan"</strong> untuk mengirim request ke API PCare BPJS.
                    </p>
                </div>
            </div>
            @endif

            {{-- Response Fields Reference --}}
            @if(!empty($ep['response_fields']))
            <div class="card shadow-sm rounded-xl mt-4 border border-gray-100 overflow-hidden">
                <details>
                    <summary class="p-4 cursor-pointer text-[10px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2 hover:bg-gray-50/50 transition-colors select-none">
                        <i class="ri-file-info-line text-sm" style="color: {{ $ep['color'] }};"></i> Response Fields Reference
                        <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-[10px] font-bold ml-1">{{ count($ep['response_fields']) }} fields</span>
                        <i class="ri-arrow-down-s-line ml-auto text-sm"></i>
                    </summary>
                    <div class="px-4 pb-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                            @foreach($ep['response_fields'] as $field => $label)
                            <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                <code class="text-[10px] font-bold block" style="color: {{ $ep['color'] }};">{{ $field }}</code>
                                <span class="text-[10px] text-gray-400">{{ $label }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </details>
            </div>
            @endif

            @endif
        </div>
    </div>
</div>
