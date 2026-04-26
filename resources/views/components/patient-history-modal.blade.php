@props(['show', 'currentPasien', 'pasienHistoryData', 'latestOdontogramState', 'dentalCategories', 'selectedPasienId'])

@if($show)
<div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-6 lg:p-8" x-data="{ show: @entangle($attributes->wire('model')) }" x-show="show" x-cloak>
    <!-- Overlay -->
    <div class="fixed inset-0 transition-opacity bg-black/60 shadow-2xl backdrop-blur-sm" wire:click="closeRiwayatModal" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
    
    <!-- Modal Box -->
    <div class="relative inline-block w-full max-w-6xl max-h-full overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl z-10 flex flex-col"
         x-show="show" x-transition:enter="ease-out duration-400" x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 flex items-center justify-between border-b border-white/10">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white text-2xl shadow-inner">
                        <i class="ri-history-line"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white leading-none">Riwayat Rekam Medis</h3>
                        <p class="text-xs text-blue-100 font-bold mt-1 opacity-80 uppercase tracking-widest">{{ $currentPasien?->nama_pasien }} ({{ $currentPasien?->no_rm }})</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($selectedPasienId)
                    <a href="{{ URL::signedRoute('laporan.kunjungan.print-riwayat', $selectedPasienId) }}" target="_blank"
                       class="hidden sm:flex items-center gap-2 bg-white/20 hover:bg-white text-white hover:text-indigo-700 px-4 py-2 rounded-xl text-xs font-black transition-all shadow-lg border border-white/10">
                        <i class="ri-printer-line"></i> CETAK RIWAYAT
                    </a>
                    @endif
                    <button wire:click="closeRiwayatModal" class="text-white/60 hover:text-white transition-colors">
                        <i class="ri-close-circle-fill text-3xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6 max-h-[75vh] overflow-y-auto custom-scrollbar bg-gray-50/30">
                @if($currentPasien)
                <!-- Basic Patient Info Cards for Mobile -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 sm:hidden">
                    <div class="p-3 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <span class="text-[9px] font-black text-gray-400 uppercase">Pasien</span>
                        <p class="text-xs font-bold text-gray-700">{{ $currentPasien->nama_pasien }}</p>
                    </div>
                    <div class="p-3 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <span class="text-[9px] font-black text-gray-400 uppercase">RM</span>
                        <p class="text-xs font-bold text-gray-700">{{ $currentPasien->no_rm }}</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- History Timeline / Table -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                            <h4 class="text-sm font-black text-[#405189] flex items-center gap-2">
                                <i class="ri-calendar-todo-line"></i> TIMELINE KUNJUNGAN
                            </h4>
                            <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-[10px] font-black uppercase">{{ count($pasienHistoryData) }} Total Kunjungan</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[1000px]">
                                <thead>
                                    <tr class="bg-gray-50/30">
                                        <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Tgl & Dokter</th>
                                        <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pemeriksaan Awal (Vitals)</th>
                                        <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Notes (SOAP)</th>
                                        <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Diagnosis & Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($pasienHistoryData as $history)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-5 py-4 align-top">
                                            <div class="font-black text-[#2c3e50] text-[13px] leading-tight">{{ date('d M Y', strtotime($history['pendaftaran']->created_at)) }}</div>
                                            <div class="flex items-center gap-1.5 mt-1 text-gray-500 text-[11px] font-bold">
                                                <i class="ri-user-star-line text-indigo-400"></i>
                                                {{ $history['pendaftaran']->dokter->nama_dokter ?? '-' }}
                                            </div>
                                            <div class="mt-2 font-mono text-[10px] font-bold text-indigo-400 bg-indigo-50 px-2 py-0.5 rounded-md inline-block">{{ $history['pendaftaran']->nomor_kunjungan }}</div>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                                                <div class="flex items-center gap-2 col-span-2 mb-1 pb-1 border-b border-gray-50">
                                                    <span class="text-[10px] font-bold text-gray-400 w-8">KSD</span>
                                                    <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['kesadaran'] ?: '-' }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-8">TD</span>
                                                    <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['td'] ?: '-' }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-8">SUHU</span>
                                                    <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['suhu'] ?: '-' }}°C</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-8">NADI</span>
                                                    <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['nadi'] ?: '-' }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-8">BB</span>
                                                    <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['bb'] ?: '-' }} kg</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-8">TB</span>
                                                    <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['tb'] ?: '-' }} cm</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-8">LP</span>
                                                    <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['lp'] ?: '-' }} cm</span>
                                                </div>
                                            </div>
                                            @if($history['clinical']['pemeriksaan_awal']['alergi'])
                                            <div class="mt-3 p-2 bg-red-50 rounded-lg border border-red-100 flex items-start gap-2 max-w-[200px]">
                                                <i class="ri-error-warning-fill text-red-500 text-xs mt-0.5"></i>
                                                <div>
                                                    <span class="text-[9px] font-black text-red-600 uppercase block leading-none">Alergi</span>
                                                    <p class="text-[10px] font-bold text-red-700 leading-tight">{{ trim(($history['clinical']['pemeriksaan_awal']['alergi_master'] ?? '') . ' ' . ($history['clinical']['pemeriksaan_awal']['alergi'] ?? '')) }}</p>
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 align-top max-w-[300px]">
                                            <div class="space-y-2">
                                                @foreach(['subjective' => 'S', 'objective' => 'O', 'assessment' => 'A', 'planning' => 'P'] as $key => $label)
                                                <div class="flex gap-2">
                                                    <div class="w-5 h-5 rounded-md bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-500 shrink-0">{{ $label }}</div>
                                                    <p class="text-[11px] font-medium text-gray-600 leading-relaxed">{{ $history['clinical']['soap']->$key ?? '-' }}</p>
                                                </div>
                                                @endforeach
                                                @if(!empty($history['clinical']['soap']->rekomendasi_diet))
                                                <div class="flex gap-2 mt-2 pt-2 border-t border-gray-100">
                                                    <div class="w-5 h-5 rounded-md bg-emerald-50 flex items-center justify-center text-[10px] font-bold text-emerald-500 shrink-0"><i class="ri-restaurant-line"></i></div>
                                                    <p class="text-[11px] font-medium text-gray-600 leading-relaxed">{{ $history['clinical']['soap']->rekomendasi_diet }}</p>
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <div class="space-y-3">
                                                <div>
                                                    <div class="text-[9px] font-black text-indigo-500 uppercase flex items-center gap-1 mb-1.5"><i class="ri-microscope-line"></i> Diagnosis</div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @forelse($history['clinical']['diagnoses'] as $diag)
                                                        <div class="px-2 py-1 bg-indigo-50 rounded-lg border border-indigo-100">
                                                            <span class="text-[10px] font-black text-indigo-600 block">{{ $diag->kode_diagnosa }}</span>
                                                            <span class="text-[10px] font-bold text-gray-600 leading-none">{{ $diag->nama_diagnosa }}</span>
                                                        </div>
                                                        @empty
                                                        <span class="text-[10px] font-bold text-gray-400 italic">Tidak ada diagnosis</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                                @if(count($history['clinical']['odontogram_visit']) > 0)
                                                <div>
                                                    <div class="text-[9px] font-black text-orange-500 uppercase flex items-center gap-1 mb-1.5"><i class="ri-tooth-line"></i> Gigi Diperiksa</div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach($history['clinical']['odontogram_visit'] as $gv)
                                                        <div class="px-2 py-1 bg-orange-50 rounded-lg border border-orange-100 flex items-center gap-2">
                                                            <div class="w-3 h-3 rounded-sm border border-black/10" style="background-color: {{ $gv->warna ?: '#ccc' }}"></div>
                                                            <div class="flex flex-col">
                                                                <span class="text-[10px] font-black text-orange-700">Gigi {{ $gv->nomor_gigi }} ({{ $gv->bagian }})</span>
                                                                <span class="text-[9px] font-bold text-orange-600 leading-none">{{ $gv->nama_kategori ?: '-' }}</span>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @endif
                                                <div>
                                                    <div class="text-[9px] font-black text-emerald-500 uppercase flex items-center gap-1 mb-1.5"><i class="ri-capsule-line"></i> Obat / Resep</div>
                                                    <div class="space-y-1">
                                                        @forelse($history['clinical']['obat'] as $o)
                                                        <div class="text-[11px] font-bold text-gray-700">• {{ $o->nama_obat }} <span class="text-gray-400 font-medium">({{ $o->dosis }})</span></div>
                                                        @empty
                                                        <span class="text-[10px] font-bold text-gray-400 italic">Tidak ada resep</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Odontogram and OHI-S in horizontal layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-8">
                        <!-- Odontogram Overview -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full">
                            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                                <h4 class="text-sm font-black text-[#405189] flex items-center gap-2">
                                    <i class="ri-tooth-line"></i> STATUS ODONTOGRAM TERKINI
                                </h4>
                            </div>
                            <div class="p-6 flex-grow flex items-center justify-center bg-white overflow-auto">
                                <div class="scale-[0.8] origin-center">
                                    <div class="flex flex-col gap-6 select-none" style="min-width: 600px;">
                                        @php
                                            $toothRows = [
                                                'AdultTop' => [[18,17,16,15,14,13,12,11], [21,22,23,24,25,26,27,28]],
                                                'ChildTop' => [[55,54,53,52,51], [61,62,63,64,65]],
                                                'ChildBot' => [[85,84,83,82,81], [71,72,73,74,75]],
                                                'AdultBot' => [[48,47,46,45,44,43,42,41], [31,32,33,34,35,36,37,38]]
                                            ];
                                        @endphp

                                        @foreach($toothRows as $type => $halves)
                                        <div class="flex justify-center gap-4 {{ str_contains($type, 'Child') ? 'opacity-80 scale-90' : '' }}">
                                            @foreach($halves as $teeth)
                                            <div class="flex gap-1.5">
                                                @foreach($teeth as $t)
                                                <div class="flex flex-col items-center gap-1 {{ str_contains($type, 'Bot') ? 'flex-col-reverse' : '' }}">
                                                    <span class="text-[9px] font-black {{ str_contains($type, 'Child') ? 'text-gray-300' : 'text-gray-400' }}">{{ $t }}</span>
                                                    <div class="w-6 h-6 lg:w-8 lg:h-8 drop-shadow-sm">
                                                        <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                            <path fill="{{ $latestOdontogramState[$t.'-T']['color'] ?? 'white' }}" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                            <path fill="{{ $latestOdontogramState[$t.'-R']['color'] ?? 'white' }}" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                            <path fill="{{ $latestOdontogramState[$t.'-B']['color'] ?? 'white' }}" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                            <path fill="{{ $latestOdontogramState[$t.'-L']['color'] ?? 'white' }}" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                            <path fill="{{ $latestOdontogramState[$t.'-C']['color'] ?? 'white' }}" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                            @endforeach
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Color Legend -->
                            <div class="px-5 py-4 border-t border-gray-50 bg-gray-50/30">
                                <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 text-center">LEGENDA KONDISI GIGI</div>
                                <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                                    @foreach($dentalCategories as $cat)
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-sm border border-black/10 shrink-0" style="background-color: {{ $cat->warna }}"></div>
                                        <span class="text-[10px] font-bold text-gray-600 uppercase">{{ $cat->nama_kategori }}</span>
                                    </div>
                                    @endforeach
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-sm border border-gray-200 bg-white shadow-inner shrink-0"></div>
                                        <span class="text-[10px] font-bold text-gray-600 uppercase">NORMAL</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- latest OHI-S Stats -->
                        @php
                            $latestOhis = $pasienHistoryData[0]['clinical']['ohis'] ?? null;
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full">
                            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                                <h4 class="text-sm font-black text-purple-700 flex items-center gap-2">
                                    <i class="ri-health-book-line"></i> KESIMPULAN OHI-S (KUNJUNGAN TERAKHIR)
                                </h4>
                            </div>
                            <div class="p-6 flex-grow flex flex-col justify-center gap-6">
                                @if($latestOhis)
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-5 bg-blue-50/50 rounded-2xl border border-blue-100 text-center">
                                        <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest block mb-2">Total Debris Index</span>
                                        <span class="text-3xl font-black text-blue-700">{{ $latestOhis->di_total }}</span>
                                    </div>
                                    <div class="p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100 text-center">
                                        <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-2">Total Calculus Index</span>
                                        <span class="text-3xl font-black text-indigo-700">{{ $latestOhis->ci_total }}</span>
                                    </div>
                                    <div class="p-5 bg-purple-50 rounded-2xl border border-purple-100 border-2 text-center col-span-2">
                                        <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-2">SKOR OHI-S KESSELURUHAN</span>
                                        <div class="flex items-center justify-center gap-6">
                                            <span class="text-5xl font-black text-purple-700">{{ $latestOhis->ohis_total }}</span>
                                            <div class="h-10 w-[1px] bg-purple-200"></div>
                                            <div class="flex flex-col items-start">
                                                <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest">KATEGORI</span>
                                                <span class="text-xl font-black 
                                                    @if($latestOhis->kategori == 'Baik') text-emerald-600 
                                                    @elseif($latestOhis->kategori == 'Sedang') text-amber-600 
                                                    @else text-red-600 @endif">{{ strtoupper($latestOhis->kategori) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="text-center py-10">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="ri-health-book-line text-3xl text-gray-200"></i>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400 italic">Belum ada data OHI-S tercatat</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between sm:justify-start gap-4">
                @if($selectedPasienId)
                <a href="{{ URL::signedRoute('laporan.kunjungan.print-riwayat', $selectedPasienId) }}" target="_blank"
                   class="sm:hidden flex flex-grow items-center justify-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-xs font-black transition-all shadow-lg active:scale-95">
                    <i class="ri-printer-line"></i> CETAK RIWAYAT
                </a>
                @endif
            </div>
    </div>
</div>
@endif
