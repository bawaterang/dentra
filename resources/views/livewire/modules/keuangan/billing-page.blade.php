<div>
    <!-- Modern Page Header -->
    <div class="page-header mb-6">
        <div class="page-header-title">
            <div class="page-header-icon">
                <i class="ri-bank-card-2-line"></i>
            </div>
            <div>
                <h1>Kasir & Billing</h1>
                <div class="page-header-breadcrumb">
                    <a href="{{ route('dashboard.index') }}">Dashboard</a>
                    <span class="sep">/</span>
                    <a href="#">Keuangan</a>
                    <span class="sep">/</span>
                    <span class="current">Billing</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Left: Filtering & Patient List -->
        <div class="space-y-6">
            <!-- Card: Filter Data -->
            <div class="card shadow-sm border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7]">
                    <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0"><i class="ri-calendar-line mr-1"></i>Pilih Tanggal</h6>
                </div>
                <div class="p-4">
                    <input type="date" wire:model.live="selectedDate" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all text-center font-semibold">
                    <div class="mt-3 text-center">
                        <p class="text-xs text-[#878a99]">Tanggal dipilih:</p>
                        <p class="font-bold text-[#405189] text-sm">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Patient List Card -->
            <div class="card shadow-sm overflow-hidden flex-1 border-t-2 border-[#405189]">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0 flex items-center gap-2">
                        <i class="ri-group-line"></i> Daftar Pasien
                    </h6>
                    <span class="badge bg-[#405189] text-white rounded-full px-2 text-[10px]">{{ count($pasienList) }}</span>
                </div>
                <div class="p-4 bg-white">
                    <div class="max-h-[500px] overflow-y-auto space-y-2 p-1">
                        @forelse($pasienList as $pasien)
                            @php
                                $isActive = $selectedPasien && $selectedPasien->nomor_kunjungan === $pasien->nomor_kunjungan;
                                $isLunas = $pasien->billing_status === 'Lunas';
                            @endphp
                            <button wire:click="selectPasien('{{ $pasien->nomor_kunjungan }}')" 
                                class="w-full text-left p-3 rounded-xl border transition-all duration-200 relative overflow-hidden {{ $isActive ? 'bg-[#405189] border-[#405189] shadow-md ring-2 ring-[#405189]/20' : 'bg-white border-gray-100 hover:border-[#405189] hover:bg-gray-50 shadow-sm' }}">
                                
                                <div class="flex flex-col gap-1">
                                    <h6 class="text-sm font-black m-0 pr-8 {{ $isActive ? 'text-white' : 'text-[#495057]' }}">
                                        {{ $pasien->nama_pasien ?? '-' }}
                                    </h6>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-lg {{ $isActive ? 'bg-white/20 text-white' : 'bg-[#405189]/10 text-[#405189]' }}">
                                            #{{ $pasien->nomor_kunjungan }}
                                        </span>
                                        @if($isLunas)
                                            <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-lg bg-green-100 text-green-700">LUNAS</span>
                                        @else
                                            <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-lg bg-red-100 text-red-700">BELUM LUNAS</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="text-center py-10 opacity-40">
                                <i class="ri-user-search-line text-4xl block mb-2"></i>
                                <p class="text-xs font-bold">Tidak ada pasien di tanggal ini</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Transaction Forms -->
        <div class="lg:col-span-3 space-y-6">
            @if($selectedPasien)
                <!-- Patient Bio Card -->
                <div class="card shadow-md border-0 rounded-3xl overflow-hidden bg-white relative">
                    <div class="flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-gray-100">
                        <div class="p-6 md:p-8 flex-1 bg-gradient-to-br from-white to-gray-50/50">
                            <div class="flex items-start gap-5">
                                <div class="h-20 w-20 rounded-3xl bg-gradient-to-tr from-[#405189] to-[#299cdb] flex items-center justify-center text-3xl font-black text-white shadow-lg shadow-blue-200">
                                    {{ strtoupper(substr($selectedPasien->nama_pasien ?? 'P', 0, 1)) }}
                                </div>
                                <div class="flex-1 space-y-2">
                                    <h2 class="text-2xl font-black text-[#405189] tracking-tight leading-tight m-0">{{ $selectedPasien->nama_pasien }}</h2>
                                    <div class="flex flex-wrap items-center gap-3 text-gray-500">
                                        <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-barcode-line text-[#405189]"></i> RM: {{ $selectedPasien->no_rm }}</span>
                                        <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-hashtag text-[#405189]"></i> {{ $selectedPasien->nomor_kunjungan }}</span>
                                        <span class="text-xs font-bold flex items-center gap-1.5"><i class="ri-calendar-event-line text-[#405189]"></i> Umur: {{ \Carbon\Carbon::parse($selectedPasien->tanggal_lahir)->age }} Thn</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-gray-50/30 md:w-80 flex flex-col justify-center items-center">
                            @php $isSelectedLunas = DB::table('trx_billing')->where('nomor_kunjungan', $selectedPasien->nomor_kunjungan)->where('status', 'Lunas')->exists(); @endphp
                            @if($isSelectedLunas)
                                <div class="flex flex-col items-center justify-center space-y-2 text-green-600">
                                    <i class="ri-checkbox-circle-fill text-5xl"></i>
                                    <h5 class="font-black tracking-widest uppercase m-0">LUNAS</h5>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center space-y-2 text-red-500">
                                    <i class="ri-close-circle-fill text-5xl"></i>
                                    <h5 class="font-black tracking-widest uppercase m-0">BELUM LUNAS</h5>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6" >
                    <!-- Left col: Detail Tagihan -->
                    <div class="card shadow-md border-0 rounded-3xl overflow-hidden bg-white" style="border-color: #b30aa8ff;">
                        <div class="p-6 md:p-8">
                            <h5 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-6 flex items-center gap-2 pb-4 border-b border-gray-100">
                                <i class="ri-list-check"></i> Rincian Tagihan Tindakan
                            </h5>
                            
                            <div class="space-y-4 mb-6 min-h-[150px]">
                                @forelse($tindakanList as $tindakan)
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors gap-1 sm:gap-2">
                                        <div class="flex items-center gap-3">
                                            <div class="shrink-0 w-8 h-8 rounded-full bg-[#405189]/10 text-[#405189] flex items-center justify-center">
                                                <i class="ri-hand-heart-line"></i>
                                            </div>
                                            <p class="font-bold text-gray-800 text-sm mb-0 leading-tight">{{ $tindakan->nama_tindakan }}</p>
                                        </div>
                                        <div class="font-black text-[#405189] text-base pl-11 sm:pl-0 sm:text-right">
                                            Rp {{ number_format($tindakan->biaya, 0, ',', '.') }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-6">
                                        <p class="text-gray-400 text-sm font-medium">Belum ada rincian tindakan medis.</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-4 px-5 rounded-2xl bg-[#405189]/5 border border-[#405189]/10 mt-4 gap-2">
                                <h4 class="font-black text-[#405189] uppercase tracking-tight text-sm mb-0 text-left">Total Tagihan</h4>
                                <h3 class="font-black text-xl sm:text-2xl text-[#405189] mb-0 text-left sm:text-right">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Right col: Form Pembayaran -->
                    <div class="card shadow-md border-0 rounded-3xl overflow-hidden bg-white" style="border-color: #0ab39c;">
                        <div class="p-6 md:p-8 bg-gray-50/50 h-full">
                            <h5 class="text-sm font-black text-[#405189] uppercase tracking-widest mb-6 flex items-center gap-2 pb-4 border-b border-gray-100">
                                <i class="ri-wallet-3-line"></i> Form Pembayaran Kasir
                            </h5>
                            
                            <form wire:submit.prevent="saveBilling">
                                <div class="mb-5">
                                    <label class="form-label font-bold text-gray-700 text-sm mb-2">Nominal Dibayar (Rp) <span class="text-red-500">*</span></label>
                                    <div class="relative w-full overflow-hidden rounded-xl">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 font-bold text-gray-400">Rp</span>
                                        <input type="text" wire:model.live.debounce.500ms="totalBayar" 
                                            class="form-control w-full pl-12 sm:pl-14 pr-4 sm:pr-6 h-14 text-xl sm:text-2xl font-black bg-white focus:bg-white text-gray-800 border-2 border-gray-200 focus:border-[#405189] focus:ring-0 transition-all text-right rounded-xl" 
                                            placeholder="0" required {{ $isSelectedLunas ? 'disabled' : '' }}>
                                    </div>
                                </div>

                                <div class="space-y-4 mb-8">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-white p-4 rounded-xl border border-gray-100 shadow-sm transition-all hover:border-[#0ab39c]/30 gap-1 sm:gap-0">
                                        <span class="font-bold text-gray-500 text-left">Uang Kembalian</span>
                                        <span class="font-black text-lg sm:text-xl text-[#0ab39c] text-left sm:text-right">Rp {{ number_format(max(0, $kembalian), 0, ',', '.') }}</span>
                                    </div>
                                    @if($hutang > 0)
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-red-50 p-4 rounded-xl border border-red-100 shadow-sm gap-1 sm:gap-0">
                                        <span class="font-bold text-red-500 flex items-center gap-1 text-left"><i class="ri-error-warning-line"></i> Sisa Hutang</span>
                                        <span class="font-black text-lg sm:text-xl text-red-600 text-left sm:text-right">Rp {{ number_format($hutang, 0, ',', '.') }}</span>
                                    </div>
                                    @endif
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                    @if(!$isSelectedLunas)
                                        <button type="submit" class="btn h-14 w-full sm:flex-1 rounded-xl font-bold text-xs sm:text-sm tracking-widest uppercase text-white shadow-md bg-[#0d6efd] hover:bg-[#0b5ed7] hover:-translate-y-0.5 transition-all flex items-center justify-center">
                                            <i class="ri-save-line mr-1 sm:mr-2"></i> SIMPAN PEMBAYARAN
                                        </button>
                                    @endif
                                    <button type="button" class="btn w-full sm:w-auto h-14 px-4 sm:px-6 rounded-xl bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white flex items-center justify-center transition-all font-bold text-xs sm:text-sm tracking-widest" title="Cetak Kuitansi" onclick="window.print()">
                                        <i class="ri-printer-line text-lg mr-1 sm:mr-2"></i> CETAK KUITANSI
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Kuitansi Print Template (Hidden on Screen, Visible on Print) -->
                <div id="print-kuitansi" class="hidden bg-white w-full h-screen text-black font-sans box-border" style="padding: 2cm;">
                    <div class="flex justify-between items-start border-b-4 border-double border-gray-900 pb-4 mb-6">
                        <div>
                            <h2 class="font-black text-2xl uppercase tracking-widest mb-1">Klinik Gigi Velzon</h2>
                            <p class="text-sm font-medium">Jl. Kesehatan No. 123, Kota Medika<br>Telp: (021) 1234-5678</p>
                        </div>
                        <div class="text-right">
                            <h1 class="text-3xl font-black tracking-widest uppercase mb-1">KUITANSI</h1>
                            <p class="text-sm font-bold">No. Faktur: {{ \DB::table('trx_billing')->where('nomor_kunjungan', $selectedPasien->nomor_kunjungan)->value('no_faktur') ?? 'Belum Lunas' }}</p>
                        </div>
                    </div>

                    <table class="w-full text-base mb-8 border-collapse">
                        <tr>
                            <td class="w-48 py-3 align-top font-bold text-gray-700 font-serif">Telah terima dari</td>
                            <td class="w-4 py-3 align-top font-bold text-gray-700">:</td>
                            <td class="py-3 font-black text-xl border-b border-dotted border-gray-400 capitalize">{{ strtolower($selectedPasien->nama_pasien) }}</td>
                        </tr>
                        <tr>
                            <td class="py-3 align-top font-bold text-gray-700 font-serif">Banyaknya uang</td>
                            <td class="py-3 align-top font-bold text-gray-700">:</td>
                            <td class="py-3">
                                <div class="bg-gray-100 font-bold px-4 py-3 italic border border-gray-300 text-lg capitalize" style="background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                    === {{ strtolower($this->terbilang($totalTagihan)) }} rupiah ===
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3 align-top font-bold text-gray-700 font-serif">Untuk pembayaran</td>
                            <td class="py-3 align-top font-bold text-gray-700">:</td>
                            <td class="py-3 text-lg font-medium border-b border-dotted border-gray-400">
                                Tindakan Medis & Perawatan Gigi (RM: {{ $selectedPasien->no_rm }})<br>
                                @foreach($tindakanList as $idx => $tindakan)
                                    <span class="text-sm text-gray-600 block mt-1">- {{ $tindakan->nama_tindakan }}</span>
                                @endforeach
                            </td>
                        </tr>
                    </table>

                    <div class="flex justify-between items-end mt-16 pb-12">
                        <div class="bg-gray-50 border-4 border-gray-900 px-6 py-4 font-black text-2xl tracking-tighter" style="background-color: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                            Rp {{ number_format($totalTagihan, 0, ',', '.') }}
                        </div>
                        <div class="text-center w-64">
                            <p class="mb-20 font-medium">Tempat, {{ date('d F Y') }}</p>
                            <p class="font-bold border-b-2 border-gray-900 pb-1 mb-1 text-lg">{{ auth()->user()->username ?? 'Kasir / Petugas' }}</p>
                            <p class="text-xs font-bold uppercase text-gray-500 tracking-widest">Tanda Tangan & Nama Terang</p>
                        </div>
                    </div>
                </div>

                <style>
                    @media print {
                        body * { visibility: hidden; }
                        .app-menu, .topbar, .page-header, .module-nav { display: none !important; }
                        #print-kuitansi { 
                            display: block !important; 
                            position: absolute; 
                            left: 0; 
                            top: 0; 
                            width: 100%; 
                            visibility: visible;
                        }
                        #print-kuitansi * { 
                            visibility: visible; 
                        }
                        .page-content {
                            padding: 0 !important;
                            margin: 0 !important;
                        }
                    }
                </style>

            @else
                <!-- Empty Target CTA -->
                <div class="card h-full flex flex-col items-center justify-center p-12 text-center opacity-40 min-h-[600px] border-2 border-dashed border-gray-200 rounded-[2rem] bg-gray-50/30">
                    <i class="ri-bank-card-2-line text-[100px] text-[#405189] mb-6"></i>
                    <h2 class="text-2xl font-black text-[#405189]">Modul Kasir & Billing</h2>
                    <p class="text-gray-500 max-w-sm mt-2 font-medium">Pilih pasien dari daftar di sebelah kiri untuk melihat rincian tagihan tindakan medis dan memproses pembayaran.</p>
                </div>
            @endif
        </div>
    </div>
</div>
