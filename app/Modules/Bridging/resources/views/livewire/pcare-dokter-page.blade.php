<div>
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon"><i class="ri-stethoscope-line"></i></div>
            <h1>Data Dokter PCare BPJS</h1>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
            <span class="sep text-gray-300">/</span>
            <span class="text-gray-400 font-medium">Bridging</span>
            <span class="sep text-gray-300">/</span>
            <span class="text-[#405189] font-bold">Dokter PCare</span>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        {{-- Total Dokter --}}
        <div class="card shadow-sm rounded-xl p-4 bg-gradient-to-br from-blue-50 to-white border-l-4 border-blue-500" style="border-top: 3px solid #0d6efd;">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                    <i class="ri-user-heart-line text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-tight">Total Dokter</p>
                    <h3 class="text-xl font-black text-gray-800">{{ $totalDokter }}</h3>
                </div>
            </div>
        </div>

        {{-- Data Range --}}
        <div class="card shadow-sm rounded-xl p-4 bg-gradient-to-br from-green-50 to-white border-l-4 border-green-500" style="border-top: 3px solid #0ab39c;">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                    <i class="ri-list-check-2 text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-tight">Data Range</p>
                    <h3 class="text-xl font-black text-gray-800">{{ $start }} - {{ $start + $limit }}</h3>
                </div>
            </div>
        </div>

        {{-- Last Fetched --}}
        <div class="card shadow-sm rounded-xl p-4 bg-gradient-to-br from-amber-50 to-white border-l-4 border-amber-500" style="border-top: 3px solid #ebab0c;">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                    <i class="ri-time-line text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-tight">Terakhir Diperbarui</p>
                    <h3 class="text-sm font-bold text-gray-800">{{ $lastFetched ?: '-' }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card shadow-sm rounded-xl mt-6 border border-gray-100 overflow-hidden border-t-4 border-[#0d6efd]">
        {{-- Header with controls --}}
        <div class="p-6 bg-[#f3f6f9]/50 border-b border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h5 class="text-xs font-bold text-[#0d6efd] uppercase tracking-widest flex items-center gap-2">
                    <i class="ri-hospital-line text-lg"></i> Data Dokter dari PCare BPJS
                </h5>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Start/Limit Controls --}}
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-tight whitespace-nowrap">Mulai dari:</label>
                        <input type="number" wire:model="start" min="0" class="w-20 rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm text-center">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-tight whitespace-nowrap">Limit:</label>
                        <select wire:model="limit" class="rounded-lg border-gray-200 text-sm px-3 py-2 focus:border-[#0d6efd] transition-all shadow-sm">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    {{-- Fetch Button --}}
                    <button
                        wire:click="fetchDokter"
                        wire:loading.attr="disabled"
                        wire:target="fetchDokter"
                        class="btn bg-[#0d6efd] text-white font-bold text-xs uppercase tracking-widest h-10 px-6 shadow-md hover:bg-[#0b5ed7] hover:translate-y-[-2px] transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="fetchDokter">
                            <i class="ri-download-cloud-2-line mr-1"></i> Ambil Data
                        </span>
                        <span wire:loading wire:target="fetchDokter">
                            <i class="ri-loader-4-line mr-1 animate-spin"></i> Mengambil...
                        </span>
                    </button>

                    {{-- Reset Button --}}
                    <button
                        wire:click="resetPage"
                        class="btn bg-white text-gray-500 border border-gray-200 font-bold text-xs uppercase h-10 px-4 shadow-sm hover:bg-gray-50 transition-all active:scale-95"
                        title="Reset"
                    >
                        <i class="ri-refresh-line"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Error Alert --}}
        @if($errorMessage)
        <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3 animate-fade-in">
            <div class="h-8 w-8 rounded-lg bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0 mt-0.5">
                <i class="ri-error-warning-line text-lg"></i>
            </div>
            <div class="flex-1">
                <h6 class="text-xs font-bold text-red-700 uppercase tracking-wider mb-1">Error Response</h6>
                <p class="text-sm text-red-600 font-medium">{{ $errorMessage }}</p>
                @if($rawResponse)
                <details class="mt-2">
                    <summary class="text-xs text-red-500 font-bold uppercase tracking-wider cursor-pointer hover:text-red-700">
                        <i class="ri-code-line"></i> Raw Response
                    </summary>
                    <pre class="mt-2 p-3 bg-red-100/50 rounded-lg text-xs text-red-700 overflow-x-auto max-h-40 font-mono">{{ $rawResponse }}</pre>
                </details>
                @endif
            </div>
        </div>
        @endif

        {{-- Success Alert --}}
        @if($successMessage && !$errorMessage)
        <div class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3 animate-fade-in">
            <div class="h-8 w-8 rounded-lg bg-green-100 flex items-center justify-center text-green-600 flex-shrink-0">
                <i class="ri-checkbox-circle-line text-lg"></i>
            </div>
            <div>
                <h6 class="text-xs font-bold text-green-700 uppercase tracking-wider mb-1">Berhasil</h6>
                <p class="text-sm text-green-600 font-medium">{{ $successMessage }}</p>
                @if($responseMetaCode)
                <div class="flex items-center gap-4 mt-2">
                    <span class="text-[10px] font-bold text-green-500 uppercase tracking-widest">
                        <i class="ri-hashtag"></i> Code: {{ $responseMetaCode }}
                    </span>
                    <span class="text-[10px] font-bold text-green-500 uppercase tracking-widest">
                        <i class="ri-message-2-line"></i> {{ $responseMetaMessage }}
                    </span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Table --}}
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px] w-12">#</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">Kode Dokter</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">Nama Dokter</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($dokterList as $index => $dokter)
                        <tr class="hover:bg-blue-50/30 transition-colors" wire:key="dokter-{{ $index }}">
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-blue-50 text-blue-600 text-[10px] font-bold">
                                    {{ $start + $index + 1 }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-[#f3f6f9] rounded-lg border border-gray-100">
                                    <i class="ri-barcode-line text-[#0d6efd] text-sm"></i>
                                    <span class="font-bold text-gray-800 text-xs tracking-wider">{{ $dokter['kdDokter'] ?? '-' }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white text-[10px] font-bold shadow-sm">
                                        {{ strtoupper(substr($dokter['nmDokter'] ?? 'D', 0, 1)) }}
                                    </div>
                                    <span class="font-semibold text-gray-800">{{ $dokter['nmDokter'] ?? '-' }}</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center mb-4">
                                        <i class="ri-stethoscope-line text-3xl text-blue-300"></i>
                                    </div>
                                    <p class="text-gray-400 font-bold text-sm mb-1">Belum Ada Data Dokter</p>
                                    <p class="text-gray-300 font-medium text-xs">Klik tombol <strong class="text-blue-500">"Ambil Data"</strong> untuk mengambil data dokter dari PCare BPJS.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if(count($dokterList) > 0)
            <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4 pt-4 border-t border-gray-100">
                <div class="text-xs text-gray-500 font-medium">
                    Menampilkan data <strong class="text-gray-700">{{ $start + 1 }}</strong> - <strong class="text-gray-700">{{ $start + count($dokterList) }}</strong>
                    dari total <strong class="text-[#0d6efd]">{{ $totalDokter }}</strong> dokter
                </div>
                <div class="flex items-center gap-2">
                    <button
                        wire:click="prevPage"
                        @if($start <= 0) disabled @endif
                        class="h-9 px-4 rounded-lg text-xs font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-2
                            {{ $start > 0 ? 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 active:scale-95' : 'bg-gray-100 text-gray-300 cursor-not-allowed border border-gray-100' }}"
                    >
                        <i class="ri-arrow-left-s-line"></i> Sebelumnya
                    </button>
                    <span class="h-9 px-4 rounded-lg bg-[#0d6efd] text-white text-xs font-bold flex items-center shadow-md">
                        {{ floor($start / $limit) + 1 }}
                    </span>
                    <button
                        wire:click="nextPage"
                        @if(count($dokterList) < $limit) disabled @endif
                        class="h-9 px-4 rounded-lg text-xs font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-2
                            {{ count($dokterList) >= $limit ? 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 active:scale-95' : 'bg-gray-100 text-gray-300 cursor-not-allowed border border-gray-100' }}"
                    >
                        Berikutnya <i class="ri-arrow-right-s-line"></i>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- API Info Card --}}
    <div class="card shadow-sm rounded-xl mt-6 border border-gray-100 overflow-hidden">
        <div class="p-5 bg-[#f3f6f9]/50 border-b border-gray-100">
            <h5 class="text-xs font-bold text-gray-600 uppercase tracking-widest flex items-center gap-2">
                <i class="ri-information-line text-lg text-[#0d6efd]"></i> Informasi Endpoint API
            </h5>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Endpoint</p>
                    <code class="text-xs text-[#0d6efd] font-mono bg-blue-50 px-3 py-1.5 rounded-lg block break-all">{Base URL}/dokter/{start}/{limit}</code>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Method / Content-Type</p>
                    <div class="flex items-center gap-3">
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-xs font-bold">GET</span>
                        <span class="text-[10px] font-medium text-gray-500">application/json; charset=utf-8</span>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Parameter 1 (start)</p>
                    <p class="text-xs text-gray-700 font-medium">Row data awal yang akan ditampilkan</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Parameter 2 (limit)</p>
                    <p class="text-xs text-gray-700 font-medium">Limit jumlah data yang akan ditampilkan</p>
                </div>
            </div>

            <div class="mt-4 bg-gray-50 rounded-xl p-4 border border-gray-100">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Headers Required</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">
                    <div class="bg-white p-2.5 rounded-lg border border-gray-100 shadow-sm">
                        <span class="text-[10px] font-bold text-[#0d6efd] block">X-cons-id</span>
                        <span class="text-[10px] text-gray-400">Consumer ID</span>
                    </div>
                    <div class="bg-white p-2.5 rounded-lg border border-gray-100 shadow-sm">
                        <span class="text-[10px] font-bold text-[#0d6efd] block">X-timestamp</span>
                        <span class="text-[10px] text-gray-400">UTC Unix Timestamp</span>
                    </div>
                    <div class="bg-white p-2.5 rounded-lg border border-gray-100 shadow-sm">
                        <span class="text-[10px] font-bold text-[#0d6efd] block">X-signature</span>
                        <span class="text-[10px] text-gray-400">HMAC-SHA256 + Base64</span>
                    </div>
                    <div class="bg-white p-2.5 rounded-lg border border-gray-100 shadow-sm">
                        <span class="text-[10px] font-bold text-[#0d6efd] block">X-authorization</span>
                        <span class="text-[10px] text-gray-400">Basic Auth (User:Pass:KdApp)</span>
                    </div>
                    <div class="bg-white p-2.5 rounded-lg border border-gray-100 shadow-sm">
                        <span class="text-[10px] font-bold text-[#0d6efd] block">user_key</span>
                        <span class="text-[10px] text-gray-400">User Key BPJS</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
