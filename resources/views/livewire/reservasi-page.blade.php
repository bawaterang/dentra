<div x-data="{
    contextMenuOpen: false,
    contextMenuX: 0,
    contextMenuY: 0,
    contextMenuDate: '',
    openContextMenu(e, date) {
        this.contextMenuDate = date;
        this.contextMenuOpen = true;
        this.contextMenuX = e.clientX;
        this.contextMenuY = e.clientY;
    },
    closeContextMenu() {
        this.contextMenuOpen = false;
    }
}" @click.outside="closeContextMenu()" @scroll.window="closeContextMenu()">
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                <i class="ri-calendar-check-line"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Reservasi Jadwal</h1>
                <p class="text-xs text-[#878a99] font-medium mt-0.5">Kelola reservasi jadwal pasien secara fleksibel.</p>
            </div>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
            <span class="sep text-gray-300">/</span>
            <span class="text-gray-400 font-medium">Admisi</span>
            <span class="sep text-gray-300">/</span>
            <span class="text-[#405189] font-bold"><i class="align-middle mr-1"></i>Reservasi</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Calendar Section -->
        <div class="lg:col-span-8">
            <div class="card border-t-2 border-[#405189] shadow-sm">
                <div class="p-6">
                    <!-- Calendar Header (FullCalendar Style) -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
                        <div class="flex items-center justify-between md:justify-start w-full md:w-auto gap-2 order-2 md:order-1">
                            <div class="flex rounded-md shadow-sm" role="group">
                                <button type="button" wire:click="prevMonth" class="px-3 py-1.5 text-sm font-medium text-white bg-[#007bff] border border-[#007bff] rounded-l-md hover:bg-blue-600 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:bg-blue-600">
                                    <i class="ri-arrow-left-s-line"></i>
                                </button>
                                <button type="button" wire:click="nextMonth" class="px-3 py-1.5 text-sm font-medium text-white bg-[#007bff] border border-[#007bff] rounded-r-md hover:bg-blue-600 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:bg-blue-600">
                                    <i class="ri-arrow-right-s-line"></i>
                                </button>
                            </div>
                            <button type="button" wire:click="goToToday" class="px-4 py-1.5 text-sm font-medium text-white bg-[#007bff] border border-[#007bff] rounded-md hover:bg-blue-600 shadow-sm transition-colors">
                                today
                            </button>
                        </div>
                        <h2 class="text-xl md:text-2xl font-medium text-gray-800 tracking-wide order-1 md:order-2">{{ $monthName }}</h2>
                        <div class="flex rounded-md shadow-sm w-full md:w-auto justify-center order-3" role="group">
                            <button type="button" wire:click="setView('month')" class="flex-1 md:flex-none px-4 py-1.5 text-sm font-medium {{ $calendarView === 'month' ? 'bg-[#007bff] text-white border-[#007bff]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }} border rounded-l-md focus:z-10 focus:ring-2 focus:ring-blue-500 transition-colors">
                                month
                            </button>
                            <button type="button" wire:click="setView('week')" class="flex-1 md:flex-none px-4 py-1.5 text-sm font-medium {{ $calendarView === 'week' ? 'bg-[#007bff] text-white border-[#007bff]' : 'bg-white text-gray-700 border-t border-b border-gray-300 hover:bg-gray-50' }} focus:z-10 focus:ring-2 focus:ring-blue-500 transition-colors">
                                week
                            </button>
                            <button type="button" wire:click="setView('day')" class="flex-1 md:flex-none px-4 py-1.5 text-sm font-medium {{ $calendarView === 'day' ? 'bg-[#007bff] text-white border-[#007bff]' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }} border rounded-r-md focus:z-10 focus:ring-2 focus:ring-blue-500 transition-colors">
                                day
                            </button>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="border border-gray-300 bg-white shadow-sm">
                        <div class="grid bg-white border-b border-gray-300" style="grid-template-columns: repeat({{ $calendarView === 'day' ? 1 : 7 }}, minmax(0, 1fr));">
                            @if($calendarView === 'day')
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center">{{ Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</div>
                            @else
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center border-r border-gray-300">Sun</div>
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center border-r border-gray-300">Mon</div>
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center border-r border-gray-300">Tue</div>
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center border-r border-gray-300">Wed</div>
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center border-r border-gray-300">Thu</div>
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center border-r border-gray-300">Fri</div>
                                <div class="text-[13px] font-bold text-gray-800 py-3 text-center">Sat</div>
                            @endif
                        </div>
                        <div class="grid bg-gray-300 gap-px" style="grid-template-columns: repeat({{ $calendarView === 'day' ? 1 : 7 }}, minmax(0, 1fr));">
                            @foreach($this->calendarDays as $day)
                                <button 
                                    wire:click="selectDate('{{ $day['date'] }}')"
                                    wire:dblclick="openModalForDate('{{ $day['date'] }}')"
                                    @contextmenu.prevent="openContextMenu($event, '{{ $day['date'] }}')"
                                    class="relative h-20 md:h-32 w-full flex flex-col items-end justify-start p-1 md:p-2 transition-all outline-none bg-white hover:bg-blue-50
                                        {{ $day['isSelected'] ? 'bg-blue-50 ring-inset ring-2 ring-blue-500 z-10' : '' }}
                                    "
                                >
                                    <span class="text-[14px] {{ !$day['isCurrentMonth'] ? 'text-gray-300' : 'text-gray-700' }} mb-1
                                        {{ $day['isToday'] && !$day['isSelected'] ? 'bg-blue-500 text-white w-6 h-6 flex items-center justify-center rounded-full font-bold shadow-sm' : '' }}
                                        {{ $day['isSelected'] && !$day['isToday'] ? 'font-bold text-blue-600' : '' }}
                                    ">{{ $day['day'] }}</span>
                                    
                                    @if($day['reservasiCount'] > 0)
                                        <div class="w-full mt-auto text-left">
                                            <span class="block w-full px-2 py-1 rounded text-[11px] font-bold bg-[#007bff] text-white truncate shadow-sm">
                                                {{ $day['reservasiCount'] }} Pasien
                                            </span>
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-blue-50/50 border-t border-blue-100 flex items-center justify-center rounded-b-lg">
                    <span class="text-[11px] text-[#405189] font-medium"><i class="ri-information-line mr-1 text-lg align-middle"></i> <b>Tips:</b> Klik dua kali (Double-click) atau klik kanan pada tanggal kalender untuk membuat reservasi baru.</span>
                </div>
            </div>
        </div>

        <!-- List Section -->
        <div class="lg:col-span-4">
            <div class="card border-t-2 border-[#f7b84b] shadow-sm h-full">
                <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h6 class="text-sm font-bold text-[#f7b84b] m-0 flex items-center gap-2">
                            <i class="ri-list-check-2"></i> Daftar Reservasi
                        </h6>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Menampilkan jadwal untuk <span class="font-bold text-[#405189]">{{ Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</span>
                        </p>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-lg border border-gray-200 text-sm pl-9 pr-3 py-1.5 focus:border-[#405189] transition-all bg-gray-50" placeholder="Cari pasien/kode...">
                        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <div class="p-0 flex-1">
                    @forelse($this->reservasiList as $item)
                        <div class="p-4 border-b border-gray-50 hover:bg-gray-50/50 transition-colors flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                            <div class="flex-shrink-0 w-20 flex flex-col items-center justify-center p-2 rounded-lg bg-indigo-50 border border-indigo-100">
                                <span class="text-sm font-black text-[#405189]">{{ substr($item->time_slot, 0, 5) }}</span>
                                <span class="text-[9px] font-bold text-gray-500 uppercase">WIB</span>
                            </div>
                            
                            <div class="flex-grow">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-mono text-gray-400 bg-gray-100 px-1.5 rounded">{{ $item->kode_reservasi }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest {{ $item->status === 'aktif' ? 'bg-primary-subtle text-[#405189]' : 'bg-success-subtle text-emerald-600' }}">
                                        {{ $item->status }}
                                    </span>
                                </div>
                                <h5 class="text-sm font-bold text-[#2c3e50] mb-0.5">{{ $item->nama_pasien_display }}</h5>
                                <div class="text-xs text-gray-500 flex items-center gap-3">
                                    @if($item->pasien_id)
                                        <span title="Pasien Lama"><i class="ri-user-heart-line text-emerald-500"></i> RM: {{ $item->pasien->no_rm }}</span>
                                    @else
                                        <span title="Pasien Baru"><i class="ri-user-add-line text-orange-500"></i> Pasien Baru ({{ $item->no_telepon_manual }})</span>
                                    @endif
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2 text-[11px]">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                        <i class="ri-hospital-line text-blue-500"></i> {{ $item->poli->nama_poli }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                        <i class="ri-stethoscope-line text-purple-500"></i> {{ $item->dokter->nama_dokter }}
                                    </span>
                                </div>
                                @if($item->keterangan)
                                    <p class="mt-2 text-[11px] text-gray-500 italic flex gap-1"><i class="ri-chat-1-line text-gray-400"></i> "{{ $item->keterangan }}"</p>
                                @endif
                            </div>

                            <div class="flex sm:flex-col items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                                @if($item->status === 'aktif')
                                    <button wire:click="prosesKeAntrian({{ $item->id }})" class="btn bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white px-3 py-1.5 text-xs font-bold rounded flex-1 sm:flex-none whitespace-nowrap transition-colors border border-emerald-100">
                                        <i class="ri-arrow-right-circle-line"></i> Proses
                                    </button>
                                    
                                    <button @click="Swal.fire({title:'Batalkan Reservasi?',text:'Apakah Anda yakin ingin membatalkan reservasi pasien ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#878a99',confirmButtonText:'Ya, Batalkan',cancelButtonText:'Kembali',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.batalReservasi({{ $item->id }})}})" class="btn bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white px-3 py-1.5 text-xs font-bold rounded flex-1 sm:flex-none whitespace-nowrap transition-colors border border-rose-100">
                                        <i class="ri-close-line"></i> Batal
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-10 flex flex-col items-center justify-center text-center text-gray-500">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                <i class="ri-calendar-close-line text-3xl text-gray-300"></i>
                            </div>
                            <h6 class="font-bold text-gray-400">Tidak Ada Reservasi</h6>
                            <p class="text-xs text-gray-400 mt-1">Belum ada jadwal reservasi untuk tanggal ini.</p>
                        </div>
                    @endforelse
                </div>
                @if($this->reservasiList->hasPages())
                    <div class="p-3 border-t border-gray-100 bg-gray-50/50">
                        {{ $this->reservasiList->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div x-show="contextMenuOpen" 
         class="fixed z-[1060] bg-white rounded-lg shadow-lg border border-gray-100 py-1 min-w-[160px] transform origin-top-left"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         :style="`top: ${contextMenuY}px; left: ${contextMenuX}px;`"
         style="display: none;">
        <button @click="$wire.openModalForDate(contextMenuDate); closeContextMenu()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-[#405189] flex items-center gap-2 transition-colors">
            <i class="ri-calendar-event-line"></i> Buat Reservasi
        </button>
        <button @click="closeContextMenu()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-rose-50 hover:text-rose-600 flex items-center gap-2 transition-colors border-t border-gray-50">
            <i class="ri-close-line"></i> Batal
        </button>
    </div>

    <!-- Modal Reservasi Baru -->
    @if($showModal)
    <div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                <h5 class="text-lg font-bold text-[#495057]"><i class="ri-calendar-event-line mr-2 text-[#405189]"></i>Buat Reservasi Baru</h5>
                <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button>
            </div>
            
            <div class="px-6 py-5 overflow-y-auto flex-1">
                <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 mb-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded bg-white flex items-center justify-center text-[#405189] font-bold text-lg shadow-sm">
                        {{ Carbon\Carbon::parse($selectedDate)->format('d') }}
                    </div>
                    <div>
                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider">Tanggal Reservasi</p>
                        <p class="text-sm font-bold text-[#405189]">{{ Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>

                <!-- Mode Toggle Pasien -->
                <div class="flex items-center gap-3 mb-5">
                    <button type="button" wire:click="$set('modePasien','lama')" class="flex-1 p-3 rounded-lg border transition-all {{ $modePasien === 'lama' ? 'border-[#405189] bg-[#405189]/5' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded bg-white flex items-center justify-center {{ $modePasien === 'lama' ? 'text-[#405189] shadow-sm' : 'text-gray-400' }}"><i class="ri-user-search-line"></i></div>
                            <div class="text-left"><p class="font-bold text-xs {{ $modePasien === 'lama' ? 'text-[#405189]' : 'text-gray-500' }}">Pasien Lama</p></div>
                        </div>
                    </button>
                    <button type="button" wire:click="$set('modePasien','baru')" class="flex-1 p-3 rounded-lg border transition-all {{ $modePasien === 'baru' ? 'border-[#0ab39c] bg-[#0ab39c]/5' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded bg-white flex items-center justify-center {{ $modePasien === 'baru' ? 'text-[#0ab39c] shadow-sm' : 'text-gray-400' }}"><i class="ri-user-add-line"></i></div>
                            <div class="text-left"><p class="font-bold text-xs {{ $modePasien === 'baru' ? 'text-[#0ab39c]' : 'text-gray-500' }}">Pasien Baru</p></div>
                        </div>
                    </button>
                </div>

                @if($modePasien === 'lama')
                    @if($selectedPasienData)
                        <div class="p-3 mb-5 rounded-lg border border-emerald-200 bg-emerald-50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">
                                    {{ substr($selectedPasienData['nama_pasien'], 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-emerald-800 text-sm">{{ $selectedPasienData['nama_pasien'] }}</p>
                                    <p class="text-xs text-emerald-600">{{ $selectedPasienData['no_rm'] }}</p>
                                </div>
                            </div>
                            <button type="button" wire:click="resetSelectedPasien" class="text-xs font-bold text-rose-500 hover:text-rose-700">Ganti</button>
                        </div>
                    @else
                        <div class="mb-5 relative">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Cari Pasien Lama <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live.debounce.300ms="searchPasien" class="w-full rounded-lg border border-gray-300 text-sm pl-9 pr-3 py-2 focus:border-[#405189]" placeholder="Ketik nama, no RM, NIK atau HP...">
                            <i class="ri-search-line absolute left-3 top-[28px] text-gray-400"></i>
                            
                            @if(count($pasienResults) > 0)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    @foreach($pasienResults as $p)
                                        <button type="button" wire:click="pilihPasien({{ $p['id'] }})" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 border-b border-gray-50 last:border-0 flex justify-between items-center">
                                            <span>{{ $p['nama_pasien'] }}</span>
                                            <span class="text-xs text-gray-400">{{ $p['no_rm'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('selectedPasienId') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                        </div>
                    @endif
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Nama Pasien <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nama_pasien" class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:border-[#405189]" placeholder="Nama lengkap">
                            @error('nama_pasien') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">No Telepon <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="no_telepon" class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:border-[#405189]" placeholder="08...">
                            @error('no_telepon') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 mb-1">NIK <span class="font-normal text-gray-400">(Opsional)</span></label>
                            <input type="text" wire:model="nik" class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:border-[#405189]" placeholder="16 digit NIK">
                        </div>
                    </div>
                @endif

                <hr class="border-gray-100 my-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Poli Tujuan <span class="text-red-500">*</span></label>
                        <x-custom-dropdown model="poli_id" :options="$poliList" placeholder="Pilih Poli" searchable="true" live="true" />
                        @error('poli_id') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Dokter <span class="text-red-500">*</span></label>
                        <x-custom-dropdown model="dokter_id" :options="$dokterList" placeholder="Pilih Dokter" searchable="true" live="true" />
                        @error('dokter_id') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Pilih Waktu (Slot) <span class="text-red-500">*</span></label>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        @if($poli_id && $dokter_id)
                            @if(count($availableTimeSlots) > 0)
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                    @foreach($availableTimeSlots as $slot)
                                        <label class="cursor-pointer relative">
                                            <input type="radio" wire:model="time_slot" value="{{ $slot['value'] }}" class="peer sr-only">
                                            <div class="text-center py-2 px-1 text-xs font-bold rounded border border-gray-300 bg-white text-gray-600 peer-checked:bg-[#0ab39c] peer-checked:border-[#0ab39c] peer-checked:text-white hover:border-[#0ab39c] transition-colors">
                                                {{ substr($slot['value'], 0, 5) }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-orange-500 text-sm font-bold flex items-center justify-center gap-2">
                                    <i class="ri-error-warning-line text-lg"></i> Tidak ada slot waktu tersedia pada tanggal ini.
                                </div>
                            @endif
                        @else
                            <div class="text-center py-2 text-gray-400 text-xs">Pilih Poli & Dokter terlebih dahulu.</div>
                        @endif
                    </div>
                    @error('time_slot') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Keterangan / Catatan</label>
                    <textarea wire:model="keterangan" rows="2" class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:border-[#405189]" placeholder="Opsional..."></textarea>
                </div>
            </div>
            
            <div class="px-5 py-4 sm:px-8 sm:py-6 bg-gray-50/80 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-end gap-3 lg:gap-3">
                <button type="button" wire:click="$set('showModal', false)" class="btn bg-orange-500 text-white w-full sm:w-auto px-6 h-10 flex items-center justify-center gap-2 transition-all hover:bg-orange-600 rounded-xl sm:rounded-2xl font-bold">
                    <i class="ri-arrow-go-back-line"></i> Batal
                </button>
                <button type="button" wire:click="saveReservasi" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white w-full sm:w-auto px-8 h-10 shadow-md flex items-center justify-center gap-2 rounded-xl sm:rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-500/10 hover:shadow-blue-500/20 hover:-translate-y-0.5 active:translate-y-0 transition-all group">
                    <svg wire:loading wire:target="saveReservasi" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span wire:loading.remove wire:target="saveReservasi" class="flex items-center gap-2">
                        <i class="ri-save-3-fill text-lg"></i>
                        Simpan Reservasi
                    </span>
                    <span wire:loading wire:target="saveReservasi" class="animate-pulse">Memproses...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
