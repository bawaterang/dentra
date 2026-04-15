<div x-data="{ modalOpen: @entangle('showLogDetail') }"
     x-init="$watch('modalOpen', value => {
         if (value) document.body.classList.add('overflow-hidden');
         else document.body.classList.remove('overflow-hidden');
     }); $cleanup(() => document.body.classList.remove('overflow-hidden'));"
>
    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon"><i class="ri-signal-tower-line"></i></div>
            <h1>API Monitoring</h1>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
            <span class="sep">/</span>
            <span>Bridging</span>
            <span class="sep">/</span>
            <span>API Monitoring</span>
        </div>
    </div>

    {{-- TAB NAVIGATION --}}
    <div class="mt-6 flex items-center gap-3 flex-wrap">
        <button wire:click="switchTab('satusehat')"
            class="group relative px-6 py-3 rounded-xl text-sm font-extrabold uppercase tracking-widest transition-all duration-300 shadow-sm
            {{ $activeTab === 'satusehat' ? 'bg-gradient-to-r from-[#0ab39c] to-[#099885] text-white shadow-lg shadow-[#0ab39c]/30 scale-[1.02]' : 'bg-white text-[#495057] hover:bg-[#f3f6f9] border border-gray-200' }}">
            <i class="ri-heart-pulse-line mr-2"></i> SatuSehat
            @if($satusehatResult && $satusehatResult['is_up'])
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-400 ml-2 animate-pulse shadow-sm shadow-green-400/50"></span>
            @elseif($satusehatResult && !$satusehatResult['is_up'])
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-400 ml-2 animate-pulse shadow-sm shadow-red-400/50"></span>
            @else
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-300 ml-2"></span>
            @endif
        </button>
        <button wire:click="switchTab('bpjs')"
            class="group relative px-6 py-3 rounded-xl text-sm font-extrabold uppercase tracking-widest transition-all duration-300 shadow-sm
            {{ $activeTab === 'bpjs' ? 'bg-gradient-to-r from-[#0d6efd] to-[#0b5ed7] text-white shadow-lg shadow-[#0d6efd]/30 scale-[1.02]' : 'bg-white text-[#495057] hover:bg-[#f3f6f9] border border-gray-200' }}">
            <i class="ri-shield-user-line mr-2"></i> BPJS
            @if($bpjsResult && $bpjsResult['is_up'])
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-400 ml-2 animate-pulse shadow-sm shadow-green-400/50"></span>
            @elseif($bpjsResult && !$bpjsResult['is_up'])
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-400 ml-2 animate-pulse shadow-sm shadow-red-400/50"></span>
            @else
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-300 ml-2"></span>
            @endif
        </button>

        <div class="ml-auto flex items-center gap-2">
            <button wire:click="checkAll" wire:loading.attr="disabled"
                class="px-5 py-3 rounded-xl text-xs font-extrabold uppercase tracking-widest bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/30 hover:shadow-xl hover:translate-y-[-2px] transition-all duration-300 active:scale-95 disabled:opacity-60">
                <span wire:loading.remove wire:target="checkAll">
                    <i class="ri-refresh-line mr-1.5"></i> Cek Semua API
                </span>
                <span wire:loading wire:target="checkAll">
                    <i class="ri-loader-4-line mr-1.5 animate-spin"></i> Memeriksa...
                </span>
            </button>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- SATUSEHAT TAB --}}
    {{-- ================================================================ --}}
    @if($activeTab === 'satusehat')
    <div class="mt-6 space-y-6" wire:key="tab-satusehat">

        {{-- ACTION BAR --}}
        <div class="flex items-center gap-3 flex-wrap">
            <button wire:click="checkSatuSehat" wire:loading.attr="disabled" wire:target="checkSatuSehat"
                class="px-6 py-3 rounded-xl text-xs font-extrabold uppercase tracking-widest bg-gradient-to-r from-[#0ab39c] to-[#099885] text-white shadow-lg shadow-[#0ab39c]/25 hover:shadow-xl hover:translate-y-[-2px] transition-all duration-300 active:scale-95 disabled:opacity-60">
                <span wire:loading.remove wire:target="checkSatuSehat">
                    <i class="ri-play-circle-line mr-1.5"></i> Cek Koneksi
                </span>
                <span wire:loading wire:target="checkSatuSehat">
                    <i class="ri-loader-4-line mr-1.5 animate-spin"></i> Memeriksa Koneksi...
                </span>
            </button>
            @if($satusehatResult)
                <span class="text-[10px] text-gray-400 font-medium">
                    <i class="ri-time-line"></i> Terakhir dicek: {{ isset($satusehatResult['created_at']) ? \Carbon\Carbon::parse($satusehatResult['created_at'])->diffForHumans() : 'Baru saja' }}
                </span>
            @endif
        </div>

        {{-- ============================== --}}
        {{-- SECTION 1: STATUS & UPTIME --}}
        {{-- ============================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Main Status Card --}}
            <div class="lg:col-span-2 card shadow-sm rounded-xl border-t-4 border-[#0ab39c] overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 flex justify-between items-center">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-signal-wifi-line mr-1.5 text-[#0ab39c]"></i> Status & Konektivitas
                    </h5>
                    <select wire:model="ssEndpoint" class="text-[11px] font-bold bg-white border border-gray-200 text-gray-600 py-1.5 px-3 rounded-lg focus:ring-[#0ab39c] focus:border-[#0ab39c] outline-none shadow-sm cursor-pointer transition-all">
                        <option value="organization">GET /Organization</option>
                        <option value="location">GET /Location</option>
                        <option value="practitioner">GET /Practitioner</option>
                        <option value="patient">GET /Patient</option>
                        <option value="encounter">GET /Encounter</option>
                        <option value="condition">GET /Condition</option>
                        <option value="observation">GET /Observation</option>
                        <option value="procedure">GET /Procedure</option>
                        <option value="composition">GET /Composition</option>
                    </select>
                </div>
                <div class="p-5">
                    @if($satusehatResult)
                        <div class="flex items-start gap-5">
                            {{-- Big Status Indicator --}}
                            <div class="flex-shrink-0">
                                @if($satusehatResult['is_up'])
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-lg shadow-green-500/30 animate-pulse">
                                        <i class="ri-check-line text-white text-3xl"></i>
                                    </div>
                                @else
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center shadow-lg shadow-red-500/30 animate-pulse">
                                        <i class="ri-close-line text-white text-3xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl font-black {{ $satusehatResult['is_up'] ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $satusehatResult['is_up'] ? 'API AKTIF' : 'API DOWN' }}
                                    </span>
                                    @if($satusehatResult['http_status_code'])
                                        <span class="px-3 py-1 rounded-lg text-[10px] font-extrabold tracking-wider
                                            {{ $satusehatResult['http_status_code'] >= 200 && $satusehatResult['http_status_code'] < 300 ? 'bg-emerald-100 text-emerald-700' : '' }}
                                            {{ $satusehatResult['http_status_code'] >= 300 && $satusehatResult['http_status_code'] < 400 ? 'bg-yellow-100 text-yellow-700' : '' }}
                                            {{ $satusehatResult['http_status_code'] >= 400 && $satusehatResult['http_status_code'] < 500 ? 'bg-orange-100 text-orange-700' : '' }}
                                            {{ $satusehatResult['http_status_code'] >= 500 ? 'bg-red-100 text-red-700' : '' }}">
                                            HTTP {{ $satusehatResult['http_status_code'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 font-medium space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-link text-[#0ab39c]"></i>
                                        <span class="font-bold text-gray-600">Endpoint:</span>
                                        <code class="bg-gray-100 px-2 py-0.5 rounded text-[10px] text-gray-600 break-all">{{ $satusehatResult['endpoint_url'] ?? '-' }}</code>
                                    </div>
                                    @if($satusehatResult['error_message'])
                                        <div class="flex items-start gap-2 mt-2 p-3 bg-red-50 border border-red-100 rounded-lg">
                                            <i class="ri-error-warning-line text-red-500 mt-0.5"></i>
                                            <span class="text-red-600 text-[11px] font-semibold">{{ $satusehatResult['error_message'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                                <i class="ri-signal-tower-line text-2xl text-gray-300"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-400">Belum ada data pengecekan</p>
                            <p class="text-xs text-gray-300 mt-1">Klik "Cek Koneksi SatuSehat" untuk memulai</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Uptime Stats Card --}}
            <div class="card shadow-sm rounded-xl border-t-4 border-emerald-400 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-bar-chart-box-line mr-1.5 text-emerald-500"></i> Uptime (7 Hari)
                    </h5>
                </div>
                <div class="p-5 space-y-4">
                    {{-- Uptime Percentage Ring --}}
                    <div class="text-center">
                        @php $uptimePct = $satusehatStats['uptime_percentage'] ?? 0; @endphp
                        <div class="relative inline-flex items-center justify-center w-28 h-28">
                            <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="52" fill="none" stroke="#f3f4f6" stroke-width="10"/>
                                <circle cx="60" cy="60" r="52" fill="none"
                                    stroke="{{ $uptimePct >= 90 ? '#10b981' : ($uptimePct >= 50 ? '#f59e0b' : '#ef4444') }}"
                                    stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="{{ $uptimePct * 3.267 }} 326.7"
                                    class="transition-all duration-1000"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-black {{ $uptimePct >= 90 ? 'text-emerald-600' : ($uptimePct >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ $uptimePct }}%
                                </span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase">Uptime</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-lg font-black text-[#495057]">{{ $satusehatStats['total_checks'] ?? 0 }}</div>
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Total Cek</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-lg font-black text-red-500">{{ $satusehatStats['error_rate'] ?? 0 }}%</div>
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Error Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================== --}}
        {{-- SECTION 2: PERFORMANCE --}}
        {{-- ============================== --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Response Time --}}
            <div class="card shadow-sm rounded-xl overflow-hidden border-l-4 border-violet-400">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Waktu Respons</span>
                        <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center">
                            <i class="ri-speed-line text-violet-500 text-lg"></i>
                        </div>
                    </div>
                    @php
                        $rt = $satusehatResult['response_time_ms'] ?? null;
                        $rtColor = $rt === null ? 'text-gray-300' : ($rt < 1000 ? 'text-emerald-600' : ($rt < 3000 ? 'text-amber-600' : 'text-red-600'));
                    @endphp
                    <div class="text-3xl font-black {{ $rtColor }}">
                        {{ $rt !== null ? number_format($rt) : '—' }}
                        <span class="text-sm font-bold text-gray-400">ms</span>
                    </div>
                    @if($rt !== null)
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-700
                                {{ $rt < 1000 ? 'bg-emerald-400' : ($rt < 3000 ? 'bg-amber-400' : 'bg-red-400') }}"
                                style="width: {{ min(100, ($rt / 5000) * 100) }}%"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-[8px] text-gray-300 font-bold">0ms</span>
                            <span class="text-[8px] text-gray-300 font-bold">5000ms</span>
                        </div>
                    @endif
                    <div class="mt-2 text-[10px] text-gray-400 font-medium">
                        Rata-rata 7 hari: <span class="font-bold text-gray-600">{{ $satusehatStats['avg_response_time'] ?? 0 }}ms</span>
                    </div>
                </div>
            </div>

            {{-- CPU Usage --}}
            <div class="card shadow-sm rounded-xl overflow-hidden border-l-4 border-cyan-400">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">CPU Usage</span>
                        <div class="w-9 h-9 rounded-xl bg-cyan-100 flex items-center justify-center">
                            <i class="ri-cpu-line text-cyan-500 text-lg"></i>
                        </div>
                    </div>
                    @php $cpu = $satusehatResult['cpu_usage'] ?? null; @endphp
                    <div class="text-3xl font-black {{ $cpu !== null ? ($cpu < 50 ? 'text-emerald-600' : ($cpu < 80 ? 'text-amber-600' : 'text-red-600')) : 'text-gray-300' }}">
                        {{ $cpu !== null ? $cpu : '—' }}
                        <span class="text-sm font-bold text-gray-400">%</span>
                    </div>
                    @if($cpu !== null)
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-700
                                {{ $cpu < 50 ? 'bg-emerald-400' : ($cpu < 80 ? 'bg-amber-400' : 'bg-red-400') }}"
                                style="width: {{ min(100, $cpu) }}%"></div>
                        </div>
                    @endif
                    <div class="mt-2 text-[10px] text-gray-400 font-medium">Penggunaan CPU server saat request</div>
                </div>
            </div>

            {{-- Memory Usage --}}
            <div class="card shadow-sm rounded-xl overflow-hidden border-l-4 border-amber-400">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Memory Usage</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center">
                            <i class="ri-database-2-line text-amber-500 text-lg"></i>
                        </div>
                    </div>
                    @php $mem = $satusehatResult['memory_usage_mb'] ?? null; @endphp
                    <div class="text-3xl font-black {{ $mem !== null ? ($mem < 64 ? 'text-emerald-600' : ($mem < 128 ? 'text-amber-600' : 'text-red-600')) : 'text-gray-300' }}">
                        {{ $mem !== null ? number_format($mem, 1) : '—' }}
                        <span class="text-sm font-bold text-gray-400">MB</span>
                    </div>
                    @if($mem !== null)
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-700
                                {{ $mem < 64 ? 'bg-emerald-400' : ($mem < 128 ? 'bg-amber-400' : 'bg-red-400') }}"
                                style="width: {{ min(100, ($mem / 256) * 100) }}%"></div>
                        </div>
                    @endif
                    <div class="mt-2 text-[10px] text-gray-400 font-medium">Penggunaan memori PHP saat request</div>
                </div>
            </div>
        </div>

        {{-- ============================== --}}
        {{-- SECTION 3: SECURITY & AUTH --}}
        {{-- ============================== --}}
        <div class="card shadow-sm rounded-xl border-t-4 border-indigo-400 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50">
                <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                    <i class="ri-shield-keyhole-line mr-1.5 text-indigo-500"></i> Keamanan & Autentikasi
                </h5>
            </div>
            <div class="p-5">
                @if($satusehatResult)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        {{-- Token Status --}}
                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                            <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Status Token</div>
                            @php $tokenStatus = $satusehatResult['token_status'] ?? 'error'; @endphp
                            <div class="flex items-center gap-3">
                                @if($tokenStatus === 'valid')
                                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                                        <i class="ri-shield-check-line text-emerald-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-emerald-600">VALID</div>
                                        <div class="text-[10px] text-gray-400">Token berhasil divalidasi</div>
                                    </div>
                                @elseif($tokenStatus === 'invalid')
                                    <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                                        <i class="ri-shield-cross-line text-red-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-red-600">INVALID</div>
                                        <div class="text-[10px] text-gray-400">Kredensial tidak valid</div>
                                    </div>
                                @elseif($tokenStatus === 'expired')
                                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                                        <i class="ri-timer-line text-amber-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-amber-600">EXPIRED</div>
                                        <div class="text-[10px] text-gray-400">Token sudah kadaluarsa</div>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center">
                                        <i class="ri-error-warning-line text-gray-400 text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-gray-500">ERROR</div>
                                        <div class="text-[10px] text-gray-400">Tidak dapat memverifikasi</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Error Rate --}}
                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                            <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Tingkat Kesalahan (7 Hari)</div>
                            @php $errRate = $satusehatStats['error_rate'] ?? 0; @endphp
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl {{ $errRate < 5 ? 'bg-emerald-100' : ($errRate < 20 ? 'bg-amber-100' : 'bg-red-100') }} flex items-center justify-center">
                                    <i class="ri-bug-line {{ $errRate < 5 ? 'text-emerald-500' : ($errRate < 20 ? 'text-amber-500' : 'text-red-500') }} text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-black {{ $errRate < 5 ? 'text-emerald-600' : ($errRate < 20 ? 'text-amber-600' : 'text-red-600') }}">{{ $errRate }}%</div>
                                    <div class="text-[10px] text-gray-400">dari {{ $satusehatStats['total_checks'] ?? 0 }} pengecekan</div>
                                </div>
                            </div>
                        </div>

                        {{-- Recent Errors --}}
                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                            <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Error Terakhir</div>
                            @php
                                $recentErrors = $satusehatLogs->where('is_up', false)->take(3);
                            @endphp
                            @if($recentErrors->count() > 0)
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    @foreach($recentErrors as $err)
                                        <div class="text-[10px] p-2 bg-red-50 rounded-lg border border-red-100">
                                            <div class="font-bold text-red-600">{{ \Illuminate\Support\Str::limit($err->error_message ?? 'Unknown', 60) }}</div>
                                            <div class="text-red-400 mt-0.5">{{ $err->created_at->diffForHumans() }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <i class="ri-check-double-line text-emerald-400 text-xl"></i>
                                    <p class="text-[10px] text-gray-400 mt-1 font-medium">Tidak ada error</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="ri-shield-keyhole-line text-3xl text-gray-200"></i>
                        <p class="text-sm text-gray-400 mt-2 font-medium">Lakukan pengecekan untuk melihat informasi keamanan</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================== --}}
        {{-- SECTION 4: CONTEXT INFO --}}
        {{-- ============================== --}}
        {{-- Request & Response Headers --}}
        @if($satusehatResult)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="card shadow-sm rounded-xl overflow-hidden" x-data="{ open: false }">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 cursor-pointer flex items-center justify-between" @click="open = !open">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-upload-2-line mr-1.5 text-blue-500"></i> Request Headers
                    </h5>
                    <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </div>
                <div x-show="open" x-transition class="p-4 bg-gray-900 overflow-x-auto">
                    <pre class="text-[11px] text-green-400 font-mono leading-relaxed whitespace-pre-wrap">@if(is_array($satusehatResult['request_headers'] ?? null))@foreach($satusehatResult['request_headers'] as $key => $val)
<span class="text-cyan-400">{{ $key }}</span>: <span class="text-yellow-300">{{ $val }}</span>
@endforeach @else
<span class="text-gray-500">— Tidak tersedia —</span>
@endif</pre>
                </div>
            </div>

            <div class="card shadow-sm rounded-xl overflow-hidden" x-data="{ open: false }">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 cursor-pointer flex items-center justify-between" @click="open = !open">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-download-2-line mr-1.5 text-orange-500"></i> Response Headers
                    </h5>
                    <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </div>
                <div x-show="open" x-transition class="p-4 bg-gray-900 overflow-x-auto">
                    <pre class="text-[11px] text-green-400 font-mono leading-relaxed whitespace-pre-wrap">@if(is_array($satusehatResult['response_headers'] ?? null))@foreach($satusehatResult['response_headers'] as $key => $val)
<span class="text-cyan-400">{{ $key }}</span>: <span class="text-yellow-300">{{ $val }}</span>
@endforeach @else
<span class="text-gray-500">— Tidak tersedia —</span>
@endif</pre>
                </div>
            </div>
        </div>
        @endif

        {{-- Historical Logs --}}
        <div class="card shadow-sm rounded-xl overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 flex items-center justify-between">
                <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                    <i class="ri-history-line mr-1.5 text-gray-500"></i> Log Historis SatuSehat
                </h5>
                @if($satusehatLogs->count() > 0)
                    <button wire:click="clearLogs('satusehat')"
                        wire:confirm="Yakin ingin menghapus semua log SatuSehat?"
                        class="text-[10px] text-red-500 font-bold hover:text-red-700 transition-colors uppercase tracking-wider">
                        <i class="ri-delete-bin-line mr-0.5"></i> Hapus Semua
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto">
                @if($satusehatLogs->count() > 0)
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-left">Waktu</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Status</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">HTTP</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Latency</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Token</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-left">Endpoint</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Oleh</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($satusehatLogs as $log)
                                <tr class="hover:bg-gray-50/50 transition-colors border-b border-gray-50/80" wire:key="ss-log-{{ $log->id }}">
                                    <td class="px-4 py-2.5 text-[10px] text-gray-600 font-medium whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if($log->is_up)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-black">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> UP
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[9px] font-black">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> DOWN
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-[10px] font-bold {{ ($log->http_status_code ?? 0) < 300 ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $log->http_status_code ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-[10px] font-bold {{ ($log->response_time_ms ?? 0) < 1000 ? 'text-emerald-600' : (($log->response_time_ms ?? 0) < 3000 ? 'text-amber-600' : 'text-red-600') }}">
                                            {{ $log->response_time_ms ? number_format($log->response_time_ms) . 'ms' : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @php $ts = $log->token_status; @endphp
                                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md
                                            {{ $ts === 'valid' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                            {{ $ts === 'invalid' ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $ts === 'expired' ? 'bg-amber-100 text-amber-700' : '' }}
                                            {{ $ts === 'error' ? 'bg-gray-100 text-gray-600' : '' }}">
                                            {{ $ts ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-[10px] text-gray-500 font-mono max-w-[200px] truncate" title="{{ $log->endpoint_url }}">
                                        {{ $log->endpoint_url }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-[10px] text-gray-500 font-medium">
                                        {{ $log->checked_by ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <button wire:click="viewLogDetail({{ $log->id }})"
                                            class="text-[10px] text-[#0ab39c] font-bold hover:text-[#099885] transition-colors">
                                            <i class="ri-eye-line"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-10">
                        <i class="ri-inbox-line text-3xl text-gray-200"></i>
                        <p class="text-sm font-bold text-gray-400 mt-2">Belum Ada Log Pengecekan</p>
                        <p class="text-xs text-gray-300 mt-1">Log akan muncul setelah Anda melakukan pengecekan koneksi</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- BPJS TAB --}}
    {{-- ================================================================ --}}
    @if($activeTab === 'bpjs')
    <div class="mt-6 space-y-6" wire:key="tab-bpjs">

        {{-- ACTION BAR --}}
        <div class="flex items-center gap-3 flex-wrap">
            <button wire:click="checkBpjs" wire:loading.attr="disabled" wire:target="checkBpjs"
                class="px-6 py-3 rounded-xl text-xs font-extrabold uppercase tracking-widest bg-gradient-to-r from-[#0d6efd] to-[#0b5ed7] text-white shadow-lg shadow-[#0d6efd]/25 hover:shadow-xl hover:translate-y-[-2px] transition-all duration-300 active:scale-95 disabled:opacity-60">
                <span wire:loading.remove wire:target="checkBpjs">
                    <i class="ri-play-circle-line mr-1.5"></i> Cek Koneksi
                </span>
                <span wire:loading wire:target="checkBpjs">
                    <i class="ri-loader-4-line mr-1.5 animate-spin"></i> Memeriksa Koneksi...
                </span>
            </button>
            @if($bpjsResult)
                <span class="text-[10px] text-gray-400 font-medium">
                    <i class="ri-time-line"></i> Terakhir dicek: {{ isset($bpjsResult['created_at']) ? \Carbon\Carbon::parse($bpjsResult['created_at'])->diffForHumans() : 'Baru saja' }}
                </span>
            @endif
        </div>

        {{-- SECTION 1: STATUS & UPTIME --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Main Status --}}
            <div class="lg:col-span-2 card shadow-sm rounded-xl border-t-4 border-[#0d6efd] overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 flex justify-between items-center">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-signal-wifi-line mr-1.5 text-[#0d6efd]"></i> Status & Konektivitas
                    </h5>
                    <select wire:model="bpjsEndpoint" class="text-[11px] font-bold bg-white border border-gray-200 text-gray-600 py-1.5 px-3 rounded-lg focus:ring-[#0d6efd] focus:border-[#0d6efd] outline-none shadow-sm cursor-pointer transition-all">
                        <option value="referensi_poli">Referensi Poli</option>
                        <option value="referensi_faskes">Referensi Faskes</option>
                        <option value="referensi_dokter">Referensi Dokter</option>
                        <option value="referensi_diagnosa">Referensi Diagnosa</option>
                    </select>
                </div>
                <div class="p-5">
                    @if($bpjsResult)
                        <div class="flex items-start gap-5">
                            <div class="flex-shrink-0">
                                @if($bpjsResult['is_up'])
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-lg shadow-green-500/30 animate-pulse">
                                        <i class="ri-check-line text-white text-3xl"></i>
                                    </div>
                                @else
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center shadow-lg shadow-red-500/30 animate-pulse">
                                        <i class="ri-close-line text-white text-3xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl font-black {{ $bpjsResult['is_up'] ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $bpjsResult['is_up'] ? 'API AKTIF' : 'API DOWN' }}
                                    </span>
                                    @if($bpjsResult['http_status_code'])
                                        <span class="px-3 py-1 rounded-lg text-[10px] font-extrabold tracking-wider
                                            {{ $bpjsResult['http_status_code'] >= 200 && $bpjsResult['http_status_code'] < 300 ? 'bg-emerald-100 text-emerald-700' : '' }}
                                            {{ $bpjsResult['http_status_code'] >= 300 && $bpjsResult['http_status_code'] < 400 ? 'bg-yellow-100 text-yellow-700' : '' }}
                                            {{ $bpjsResult['http_status_code'] >= 400 && $bpjsResult['http_status_code'] < 500 ? 'bg-orange-100 text-orange-700' : '' }}
                                            {{ $bpjsResult['http_status_code'] >= 500 ? 'bg-red-100 text-red-700' : '' }}">
                                            HTTP {{ $bpjsResult['http_status_code'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 font-medium space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-link text-[#0d6efd]"></i>
                                        <span class="font-bold text-gray-600">Endpoint:</span>
                                        <code class="bg-gray-100 px-2 py-0.5 rounded text-[10px] text-gray-600 break-all">{{ $bpjsResult['endpoint_url'] ?? '-' }}</code>
                                    </div>
                                    @if($bpjsResult['error_message'])
                                        <div class="flex items-start gap-2 mt-2 p-3 bg-red-50 border border-red-100 rounded-lg">
                                            <i class="ri-error-warning-line text-red-500 mt-0.5"></i>
                                            <span class="text-red-600 text-[11px] font-semibold">{{ $bpjsResult['error_message'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                                <i class="ri-signal-tower-line text-2xl text-gray-300"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-400">Belum ada data pengecekan</p>
                            <p class="text-xs text-gray-300 mt-1">Klik "Cek Koneksi BPJS" untuk memulai</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Uptime Card --}}
            <div class="card shadow-sm rounded-xl border-t-4 border-blue-400 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-bar-chart-box-line mr-1.5 text-blue-500"></i> Uptime (7 Hari)
                    </h5>
                </div>
                <div class="p-5 space-y-4">
                    <div class="text-center">
                        @php $bpjsUptime = $bpjsStats['uptime_percentage'] ?? 0; @endphp
                        <div class="relative inline-flex items-center justify-center w-28 h-28">
                            <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="52" fill="none" stroke="#f3f4f6" stroke-width="10"/>
                                <circle cx="60" cy="60" r="52" fill="none"
                                    stroke="{{ $bpjsUptime >= 90 ? '#3b82f6' : ($bpjsUptime >= 50 ? '#f59e0b' : '#ef4444') }}"
                                    stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="{{ $bpjsUptime * 3.267 }} 326.7"
                                    class="transition-all duration-1000"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-black {{ $bpjsUptime >= 90 ? 'text-blue-600' : ($bpjsUptime >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ $bpjsUptime }}%
                                </span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase">Uptime</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-lg font-black text-[#495057]">{{ $bpjsStats['total_checks'] ?? 0 }}</div>
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Total Cek</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-lg font-black text-red-500">{{ $bpjsStats['error_rate'] ?? 0 }}%</div>
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Error Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: PERFORMANCE --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="card shadow-sm rounded-xl overflow-hidden border-l-4 border-violet-400">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Waktu Respons</span>
                        <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center">
                            <i class="ri-speed-line text-violet-500 text-lg"></i>
                        </div>
                    </div>
                    @php
                        $brt = $bpjsResult['response_time_ms'] ?? null;
                        $brtColor = $brt === null ? 'text-gray-300' : ($brt < 1000 ? 'text-emerald-600' : ($brt < 3000 ? 'text-amber-600' : 'text-red-600'));
                    @endphp
                    <div class="text-3xl font-black {{ $brtColor }}">
                        {{ $brt !== null ? number_format($brt) : '—' }}
                        <span class="text-sm font-bold text-gray-400">ms</span>
                    </div>
                    @if($brt !== null)
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-700
                                {{ $brt < 1000 ? 'bg-emerald-400' : ($brt < 3000 ? 'bg-amber-400' : 'bg-red-400') }}"
                                style="width: {{ min(100, ($brt / 5000) * 100) }}%"></div>
                        </div>
                    @endif
                    <div class="mt-2 text-[10px] text-gray-400 font-medium">
                        Rata-rata 7 hari: <span class="font-bold text-gray-600">{{ $bpjsStats['avg_response_time'] ?? 0 }}ms</span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm rounded-xl overflow-hidden border-l-4 border-cyan-400">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">CPU Usage</span>
                        <div class="w-9 h-9 rounded-xl bg-cyan-100 flex items-center justify-center">
                            <i class="ri-cpu-line text-cyan-500 text-lg"></i>
                        </div>
                    </div>
                    @php $bcpu = $bpjsResult['cpu_usage'] ?? null; @endphp
                    <div class="text-3xl font-black {{ $bcpu !== null ? ($bcpu < 50 ? 'text-emerald-600' : ($bcpu < 80 ? 'text-amber-600' : 'text-red-600')) : 'text-gray-300' }}">
                        {{ $bcpu !== null ? $bcpu : '—' }}
                        <span class="text-sm font-bold text-gray-400">%</span>
                    </div>
                    @if($bcpu !== null)
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-700
                                {{ $bcpu < 50 ? 'bg-emerald-400' : ($bcpu < 80 ? 'bg-amber-400' : 'bg-red-400') }}"
                                style="width: {{ min(100, $bcpu) }}%"></div>
                        </div>
                    @endif
                    <div class="mt-2 text-[10px] text-gray-400 font-medium">Penggunaan CPU server saat request</div>
                </div>
            </div>

            <div class="card shadow-sm rounded-xl overflow-hidden border-l-4 border-amber-400">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Memory Usage</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center">
                            <i class="ri-database-2-line text-amber-500 text-lg"></i>
                        </div>
                    </div>
                    @php $bmem = $bpjsResult['memory_usage_mb'] ?? null; @endphp
                    <div class="text-3xl font-black {{ $bmem !== null ? ($bmem < 64 ? 'text-emerald-600' : ($bmem < 128 ? 'text-amber-600' : 'text-red-600')) : 'text-gray-300' }}">
                        {{ $bmem !== null ? number_format($bmem, 1) : '—' }}
                        <span class="text-sm font-bold text-gray-400">MB</span>
                    </div>
                    @if($bmem !== null)
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-700
                                {{ $bmem < 64 ? 'bg-emerald-400' : ($bmem < 128 ? 'bg-amber-400' : 'bg-red-400') }}"
                                style="width: {{ min(100, ($bmem / 256) * 100) }}%"></div>
                        </div>
                    @endif
                    <div class="mt-2 text-[10px] text-gray-400 font-medium">Penggunaan memori PHP saat request</div>
                </div>
            </div>
        </div>

        {{-- SECTION 3: SECURITY & AUTH --}}
        <div class="card shadow-sm rounded-xl border-t-4 border-indigo-400 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50">
                <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                    <i class="ri-shield-keyhole-line mr-1.5 text-indigo-500"></i> Keamanan & Autentikasi
                </h5>
            </div>
            <div class="p-5">
                @if($bpjsResult)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                            <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Status Kredensial</div>
                            @php $bTokenStatus = $bpjsResult['token_status'] ?? 'error'; @endphp
                            <div class="flex items-center gap-3">
                                @if($bTokenStatus === 'valid')
                                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center"><i class="ri-shield-check-line text-emerald-500 text-xl"></i></div>
                                    <div>
                                        <div class="text-sm font-black text-emerald-600">VALID</div>
                                        <div class="text-[10px] text-gray-400">Signature & Auth berhasil</div>
                                    </div>
                                @elseif($bTokenStatus === 'invalid')
                                    <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center"><i class="ri-shield-cross-line text-red-500 text-xl"></i></div>
                                    <div>
                                        <div class="text-sm font-black text-red-600">INVALID</div>
                                        <div class="text-[10px] text-gray-400">ConsID/SecretKey tidak valid</div>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center"><i class="ri-error-warning-line text-gray-400 text-xl"></i></div>
                                    <div>
                                        <div class="text-sm font-black text-gray-500">ERROR</div>
                                        <div class="text-[10px] text-gray-400">Tidak dapat memverifikasi</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                            <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Tingkat Kesalahan (7 Hari)</div>
                            @php $bErrRate = $bpjsStats['error_rate'] ?? 0; @endphp
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl {{ $bErrRate < 5 ? 'bg-emerald-100' : ($bErrRate < 20 ? 'bg-amber-100' : 'bg-red-100') }} flex items-center justify-center">
                                    <i class="ri-bug-line {{ $bErrRate < 5 ? 'text-emerald-500' : ($bErrRate < 20 ? 'text-amber-500' : 'text-red-500') }} text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-black {{ $bErrRate < 5 ? 'text-emerald-600' : ($bErrRate < 20 ? 'text-amber-600' : 'text-red-600') }}">{{ $bErrRate }}%</div>
                                    <div class="text-[10px] text-gray-400">dari {{ $bpjsStats['total_checks'] ?? 0 }} pengecekan</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                            <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Error Terakhir</div>
                            @php $bRecentErrors = $bpjsLogs->where('is_up', false)->take(3); @endphp
                            @if($bRecentErrors->count() > 0)
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    @foreach($bRecentErrors as $be)
                                        <div class="text-[10px] p-2 bg-red-50 rounded-lg border border-red-100">
                                            <div class="font-bold text-red-600">{{ \Illuminate\Support\Str::limit($be->error_message ?? 'Unknown', 60) }}</div>
                                            <div class="text-red-400 mt-0.5">{{ $be->created_at->diffForHumans() }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <i class="ri-check-double-line text-emerald-400 text-xl"></i>
                                    <p class="text-[10px] text-gray-400 mt-1 font-medium">Tidak ada error</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="ri-shield-keyhole-line text-3xl text-gray-200"></i>
                        <p class="text-sm text-gray-400 mt-2 font-medium">Lakukan pengecekan untuk melihat informasi keamanan</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- SECTION 4: CONTEXT (Headers + Log) --}}
        @if($bpjsResult)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="card shadow-sm rounded-xl overflow-hidden" x-data="{ open: false }">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 cursor-pointer flex items-center justify-between" @click="open = !open">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-upload-2-line mr-1.5 text-blue-500"></i> Request Headers
                    </h5>
                    <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </div>
                <div x-show="open" x-transition class="p-4 bg-gray-900 overflow-x-auto">
                    <pre class="text-[11px] text-green-400 font-mono leading-relaxed whitespace-pre-wrap">@if(is_array($bpjsResult['request_headers'] ?? null))@foreach($bpjsResult['request_headers'] as $key => $val)
<span class="text-cyan-400">{{ $key }}</span>: <span class="text-yellow-300">{{ $val }}</span>
@endforeach @else
<span class="text-gray-500">— Tidak tersedia —</span>
@endif</pre>
                </div>
            </div>

            <div class="card shadow-sm rounded-xl overflow-hidden" x-data="{ open: false }">
                <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 cursor-pointer flex items-center justify-between" @click="open = !open">
                    <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                        <i class="ri-download-2-line mr-1.5 text-orange-500"></i> Response Headers
                    </h5>
                    <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </div>
                <div x-show="open" x-transition class="p-4 bg-gray-900 overflow-x-auto">
                    <pre class="text-[11px] text-green-400 font-mono leading-relaxed whitespace-pre-wrap">@if(is_array($bpjsResult['response_headers'] ?? null))@foreach($bpjsResult['response_headers'] as $key => $val)
<span class="text-cyan-400">{{ $key }}</span>: <span class="text-yellow-300">{{ $val }}</span>
@endforeach @else
<span class="text-gray-500">— Tidak tersedia —</span>
@endif</pre>
                </div>
            </div>
        </div>
        @endif

        {{-- Log Historis BPJS --}}
        <div class="card shadow-sm rounded-xl overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-[#f3f6f9]/50 flex items-center justify-between">
                <h5 class="text-[11px] font-extrabold text-[#495057] m-0 uppercase tracking-widest">
                    <i class="ri-history-line mr-1.5 text-gray-500"></i> Log Historis BPJS
                </h5>
                @if($bpjsLogs->count() > 0)
                    <button wire:click="clearLogs('bpjs')"
                        wire:confirm="Yakin ingin menghapus semua log BPJS?"
                        class="text-[10px] text-red-500 font-bold hover:text-red-700 transition-colors uppercase tracking-wider">
                        <i class="ri-delete-bin-line mr-0.5"></i> Hapus Semua
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto">
                @if($bpjsLogs->count() > 0)
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-left">Waktu</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Status</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">HTTP</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Latency</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Kredensial</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-left">Endpoint</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Oleh</th>
                                <th class="px-4 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bpjsLogs as $blog)
                                <tr class="hover:bg-gray-50/50 transition-colors border-b border-gray-50/80" wire:key="bpjs-log-{{ $blog->id }}">
                                    <td class="px-4 py-2.5 text-[10px] text-gray-600 font-medium whitespace-nowrap">{{ $blog->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if($blog->is_up)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-black">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> UP
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[9px] font-black">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> DOWN
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-[10px] font-bold {{ ($blog->http_status_code ?? 0) < 300 ? 'text-emerald-600' : 'text-red-600' }}">{{ $blog->http_status_code ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-[10px] font-bold {{ ($blog->response_time_ms ?? 0) < 1000 ? 'text-emerald-600' : (($blog->response_time_ms ?? 0) < 3000 ? 'text-amber-600' : 'text-red-600') }}">
                                            {{ $blog->response_time_ms ? number_format($blog->response_time_ms) . 'ms' : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @php $bts = $blog->token_status; @endphp
                                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md
                                            {{ $bts === 'valid' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                            {{ $bts === 'invalid' ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $bts === 'error' ? 'bg-gray-100 text-gray-600' : '' }}">
                                            {{ $bts ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-[10px] text-gray-500 font-mono max-w-[200px] truncate" title="{{ $blog->endpoint_url }}">{{ $blog->endpoint_url }}</td>
                                    <td class="px-4 py-2.5 text-center text-[10px] text-gray-500 font-medium">{{ $blog->checked_by ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <button wire:click="viewLogDetail({{ $blog->id }})"
                                            class="text-[10px] text-[#0d6efd] font-bold hover:text-[#0b5ed7] transition-colors">
                                            <i class="ri-eye-line"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-10">
                        <i class="ri-inbox-line text-3xl text-gray-200"></i>
                        <p class="text-sm font-bold text-gray-400 mt-2">Belum Ada Log Pengecekan</p>
                        <p class="text-xs text-gray-300 mt-1">Log akan muncul setelah Anda melakukan pengecekan koneksi</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- LOG DETAIL MODAL --}}
    {{-- ================================================================ --}}
    @if($showLogDetail && $logDetail)
    <div class="fixed inset-0 z-[2000] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeLogDetail"></div>
        <div class="relative w-full max-w-3xl max-h-[85vh] overflow-y-auto bg-white rounded-2xl shadow-2xl animate-in zoom-in-95">
            {{-- Modal Header --}}
            <div class="sticky top-0 z-10 p-5 border-b border-gray-100 bg-white/95 backdrop-blur flex items-center justify-between rounded-t-2xl">
                <div>
                    <h3 class="text-sm font-black text-[#495057] uppercase tracking-wider">
                        <i class="ri-file-list-3-line mr-1.5 {{ $logDetail->api_type === 'satusehat' ? 'text-[#0ab39c]' : 'text-[#0d6efd]' }}"></i>
                        Detail Log {{ strtoupper($logDetail->api_type) }}
                    </h3>
                    <p class="text-[10px] text-gray-400 mt-0.5 font-medium">{{ $logDetail->created_at->format('d F Y, H:i:s') }}</p>
                </div>
                <button wire:click="closeLogDetail" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <i class="ri-close-line text-gray-500"></i>
                </button>
            </div>

            <div class="p-5 space-y-5">
                {{-- Quick Stats --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 rounded-xl {{ $logDetail->is_up ? 'bg-emerald-50 border border-emerald-100' : 'bg-red-50 border border-red-100' }} text-center">
                        <div class="text-xs font-black {{ $logDetail->is_up ? 'text-emerald-600' : 'text-red-600' }}">{{ $logDetail->is_up ? 'UP' : 'DOWN' }}</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Status</div>
                    </div>
                    <div class="p-3 rounded-xl bg-blue-50 border border-blue-100 text-center">
                        <div class="text-xs font-black text-blue-600">HTTP {{ $logDetail->http_status_code ?? '—' }}</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Status Code</div>
                    </div>
                    <div class="p-3 rounded-xl bg-violet-50 border border-violet-100 text-center">
                        <div class="text-xs font-black text-violet-600">{{ $logDetail->response_time_ms ? number_format($logDetail->response_time_ms) . 'ms' : '—' }}</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Latency</div>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-100 text-center">
                        @php $mts = $logDetail->token_status ?? 'error'; @endphp
                        <div class="text-xs font-black {{ $mts === 'valid' ? 'text-emerald-600' : ($mts === 'invalid' ? 'text-red-600' : 'text-amber-600') }}">{{ strtoupper($mts) }}</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Token</div>
                    </div>
                </div>

                {{-- Endpoint --}}
                <div>
                    <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2">Endpoint URL</div>
                    <code class="block w-full p-3 bg-gray-50 rounded-xl text-[11px] text-gray-600 font-mono break-all border border-gray-100">{{ $logDetail->endpoint_url }}</code>
                </div>

                {{-- Error Message --}}
                @if($logDetail->error_message)
                <div>
                    <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2">Error Message</div>
                    <div class="p-3 bg-red-50 rounded-xl border border-red-100 text-[11px] text-red-600 font-semibold">{{ $logDetail->error_message }}</div>
                </div>
                @endif

                {{-- Resource Usage --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2">CPU Usage</div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-sm font-black text-gray-700">{{ $logDetail->cpu_usage ?? '—' }}%</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2">Memory Usage</div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-sm font-black text-gray-700">{{ $logDetail->memory_usage_mb ? number_format($logDetail->memory_usage_mb, 1) . ' MB' : '—' }}</div>
                    </div>
                </div>

                {{-- Request Headers --}}
                <div>
                    <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2">Request Headers</div>
                    <div class="p-4 bg-gray-900 rounded-xl overflow-x-auto">
                        <pre class="text-[11px] text-green-400 font-mono leading-relaxed whitespace-pre-wrap">@if(is_array($logDetail->request_headers) && count($logDetail->request_headers) > 0)@foreach($logDetail->request_headers as $k => $v)
<span class="text-cyan-400">{{ $k }}</span>: <span class="text-yellow-300">{{ $v }}</span>
@endforeach @else
<span class="text-gray-500">— Tidak tersedia —</span>
@endif</pre>
                    </div>
                </div>

                {{-- Response Headers --}}
                <div>
                    <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2">Response Headers</div>
                    <div class="p-4 bg-gray-900 rounded-xl overflow-x-auto">
                        <pre class="text-[11px] text-green-400 font-mono leading-relaxed whitespace-pre-wrap">@if(is_array($logDetail->response_headers) && count($logDetail->response_headers) > 0)@foreach($logDetail->response_headers as $k => $v)
<span class="text-cyan-400">{{ $k }}</span>: <span class="text-yellow-300">{{ $v }}</span>
@endforeach @else
<span class="text-gray-500">— Tidak tersedia —</span>
@endif</pre>
                    </div>
                </div>

                {{-- Response Body --}}
                @if($logDetail->response_body)
                <div>
                    <div class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2">Response Body (Truncated)</div>
                    <div class="p-4 bg-gray-900 rounded-xl overflow-x-auto max-h-64 overflow-y-auto">
                        <pre class="text-[11px] text-green-400 font-mono leading-relaxed whitespace-pre-wrap">{{ $logDetail->response_body }}</pre>
                    </div>
                </div>
                @endif

                {{-- Meta --}}
                <div class="text-[10px] text-gray-400 font-medium pt-3 border-t border-gray-100 flex items-center justify-between">
                    <span><i class="ri-user-line mr-1"></i> Dicek oleh: {{ $logDetail->checked_by ?? 'System' }}</span>
                    <span><i class="ri-time-line mr-1"></i> {{ $logDetail->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
