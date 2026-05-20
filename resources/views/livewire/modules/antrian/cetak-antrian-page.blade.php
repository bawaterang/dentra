        <div class="min-h-screen bg-white text-black p-4 flex flex-col items-center" x-data="{ init() { window.print(); setTimeout(() => window.close(), 1000); } }">
            <style>
                @media print {
                    @page { margin: 0; }
                    body * { visibility: hidden; }
                    #printArea, #printArea * { visibility: visible; color: black !important; }
                    #printArea {
                        display: block !important;
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        max-width: 100%;
                        margin: 0;
                        padding: 5mm;
                        font-family: monospace;
                        background: white !important;
                    }
                }
            </style>
            
            <div id="printArea" class="w-full max-w-[80mm] mx-auto hidden mt-10 p-4 border rounded shadow-md print:shadow-none print:border-none print:mt-0 print:p-0" style="display:block;">
                <div class="text-center font-bold text-lg border-b border-dashed border-black pb-2 mb-2">
                    SIGI DENTAL CLINIC
                </div>
                <div class="text-center text-sm mb-1">Nomor Antrian</div>
                <div class="text-center text-5xl font-black my-2">{{ $this->generatedAntrian->nomor_antrian }}</div>
                <div class="text-center text-xs mb-3">{{ \Carbon\Carbon::parse($this->generatedAntrian->tanggal_antrian)->translatedFormat('l, d M Y') }}</div>
                
                <div class="text-sm border-t border-b border-dashed border-black py-2 mb-3 space-y-1">
                    <div class="flex justify-between"><span>Nama:</span><span class="font-bold text-right ml-2 truncate">{{ $this->generatedAntrian->nama_pasien_input_manual }}</span></div>
                    @if($this->generatedAntrian->kode_poli)<div class="flex justify-between"><span>Poli:</span><span class="font-bold text-right ml-2">{{ $this->generatedAntrian->kode_poli }}</span></div>@endif
                    <div class="flex justify-between"><span>Jenis:</span><span class="font-bold text-right ml-2">{{ ucfirst($this->generatedAntrian->jenis_antrian) }}</span></div>
                </div>
                
                <div class="text-center text-[10px]">
                    Simpan tiket ini.<br>Harap menunggu giliran Anda.
                </div>
            </div>
            
            <p class="mt-8 text-gray-400 print:hidden">Sedang mencetak tiket...</p>
        </div>