<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use App\Models\TrxBackupLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupPage extends Component
{
    public $backups = [];

    public function mount()
    {
        $this->loadBackups();
    }

    public function loadBackups()
    {
        $this->backups = TrxBackupLog::with('creator')->orderBy('created_at', 'desc')->get();
    }

    public function createBackup()
    {
        try {
            $filename = 'backup-' . date('Y-m-d-His') . '.sql';
            $path = storage_path('app/backups/' . $filename);

            // Ensure directory exists
            if (!File::exists(storage_path('app/backups'))) {
                File::makeDirectory(storage_path('app/backups'), 0755, true);
            }

            $dbConnection = config('database.default');
            $dbConfig = config("database.connections.{$dbConnection}");

            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($path)
            );

            // Add socket if present
            if (!empty($dbConfig['unix_socket'])) {
                $command = str_replace("--host=" . escapeshellarg($dbConfig['host']), "--socket=" . escapeshellarg($dbConfig['unix_socket']), $command);
            }

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception("Gagal menjalankan mysqldump. Pastikan mysqldump terinstall.");
            }

            $size = File::size($path);
            $sizeFormatted = $this->formatBytes($size);

            TrxBackupLog::create([
                'filename' => $filename,
                'size' => $sizeFormatted,
                'disk' => 'local',
                'status' => 'Success',
                'created_by' => auth()->id()
            ]);

            $this->loadBackups();
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Backup database berhasil dibuat!']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Backup Gagal: ' . $e->getMessage()]);
        }
    }

    public function deleteBackup($id)
    {
        $backup = TrxBackupLog::findOrFail($id);
        $path = storage_path('app/backups/' . $backup->filename);

        if (File::exists($path)) {
            File::delete($path);
        }

        $backup->delete();
        $this->loadBackups();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'File backup berhasil dihapus!']);
    }

    public function restoreDatabase($id)
    {
        try {
            $backup = TrxBackupLog::findOrFail($id);
            $path = storage_path('app/backups/' . $backup->filename);

            if (!File::exists($path)) {
                throw new \Exception("File backup tidak ditemukan di server.");
            }

            $dbConnection = config('database.default');
            $dbConfig = config("database.connections.{$dbConnection}");

            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s %s < %s',
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($path)
            );

            if (!empty($dbConfig['unix_socket'])) {
                $command = str_replace("--host=" . escapeshellarg($dbConfig['host']), "--socket=" . escapeshellarg($dbConfig['unix_socket']), $command);
            }

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception("Gagal menjalankan restore mysql.");
            }

            $this->dispatch('alert', ['type' => 'success', 'message' => 'Database berhasil direstore dari ' . $backup->filename]);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Restore Gagal: ' . $e->getMessage()]);
        }
    }

    public function downloadBackup($id)
    {
        $backup = TrxBackupLog::findOrFail($id);
        $path = storage_path('app/backups/' . $backup->filename);

        if (!File::exists($path)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'File tidak ditemukan!']);
            return;
        }

        return response()->download($path);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function render()
    {
        return <<<'HTML'
        <div x-data="{ 
            initDataTable() {
                const t='#backupTable';
                if($.fn.DataTable.isDataTable(t)){$(t).DataTable().destroy()}
                $(t).DataTable({
                    scrollX:false,
                    dom:'lrtip',
                    order: [[ 3, 'desc' ]],
                    language:{
                        lengthMenu:'_MENU_',
                        info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                        infoFiltered:'(disaring dari total _MAX_ data)',
                        zeroRecords:'Tidak ada data yang ditemukan',
                        emptyTable:'Belum ada riwayat backup',
                        paginate:{
                            previous:'<i class=ri-arrow-left-s-line></i>',
                            next:'<i class=ri-arrow-right-s-line></i>'
                        }
                    }
                });
            },
            init() {
                $nextTick(()=>this.initDataTable());
            }
        }" 
        @refresh-table.window="$nextTick(()=>initDataTable())"
        x-init="initDataTable()">
            
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon">
                        <i class="ri-database-2-line"></i>
                    </div>
                    <h1>Backup Database</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Pengaturan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Backup Database</span>
                </div>
            </div>

            <div class="card mb-6 overflow-hidden border-t-2 border-[#405189]">
                <div class="p-6 bg-[#f3f6f9]/50 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 bg-indigo-100 rounded-2xl flex items-center justify-center text-[#405189]">
                            <i class="ri-folder-zip-line text-4xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-[#495057] mb-1">Backup Manual Database</h4>
                            <p class="text-sm text-gray-500 max-w-md">Klik tombol di samping untuk membuat salinan database saat ini. File akan disimpan secara lokal di server.</p>
                        </div>
                    </div>
                    <button wire:click="createBackup" wire:loading.attr="disabled" class="btn btn-primary h-12 px-8 flex items-center gap-3 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 shadow-md">
                        <i wire:loading.remove wire:target="createBackup" class="ri-speed-mini-line text-xl"></i>
                        <svg wire:loading wire:target="createBackup" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="font-bold uppercase tracking-wider text-xs">Jalankan Backup Sekarang</span>
                    </button>
                </div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#0ab39c]">
                <div class="p-4 border-b border-[#eff2f7] bg-[#f3f6f9]/30">
                    <h6 class="mb-0 text-sm font-bold text-gray-600 uppercase tracking-wider"><i class="ri-history-line mr-2"></i>Riwayat Backup Database</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="backupTable" class="table align-middle table-nowrap mb-0 w-full">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Nama File</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Ukuran</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Dibuat Oleh</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider">Tanggal & Waktu</th>
                                    <th class="font-semibold text-xs uppercase tracking-wider !text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                <tr wire:key="backup-{{ $backup->id }}" class="hover:bg-gray-50/50 transition-colors">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <i class="ri-file-zip-line text-orange-500 text-lg"></i>
                                            <span class="text-sm font-bold text-[#495057]">{{ $backup->filename }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-indigo-subtle text-indigo px-2 py-1">{{ $backup->size }}</span></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-[#405189] text-white flex items-center justify-center text-[10px] font-bold">
                                                {{ strtoupper(substr($backup->creator->full_name ?? 'S', 0, 1)) }}
                                            </div>
                                            <span class="text-xs text-gray-700 font-medium">{{ $backup->creator->full_name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td><span class="text-xs text-gray-500">{{ $backup->created_at->format('d M Y, H:i:s') }}</span></td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-2">
                                            <button wire:click="downloadBackup({{ $backup->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Download">
                                                <i class="ri-download-2-line"></i>
                                            </button>
                                            <button @click="
                                                Swal.fire({
                                                    title: 'Restore Database?',
                                                    text: 'Database saat ini akan ditimpa dengan data dari file ini. Tindakan ini tidak dapat dibatalkan!',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#f7b84b',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: 'Ya, Restore!',
                                                    cancelButtonText: 'Batal',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.restoreDatabase({{ $backup->id }})
                                                    }
                                                })
                                            " class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white transition-all shadow-sm" title="Restore">
                                                <i class="ri-restart-line"></i>
                                            </button>
                                            <button @click="
                                                Swal.fire({
                                                    title: 'Hapus Backup?',
                                                    text: 'File backup akan dihapus permanen dari server.',
                                                    icon: 'error',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#f06548',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: 'Ya, Hapus!',
                                                    cancelButtonText: 'Batal',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.deleteBackup({{ $backup->id }})
                                                    }
                                                })
                                            " class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Hapus">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 p-4 rounded-xl bg-blue-50 border border-blue-100 flex items-start gap-4">
                <i class="ri-information-line text-blue-500 text-xl"></i>
                <div>
                    <h6 class="text-sm font-bold text-blue-700 mb-1">Tips Keamanan</h6>
                    <p class="text-xs text-blue-600/80 leading-relaxed">Disarankan untuk melakukan backup secara berkala sebelum melakukan update sistem besar atau penghapusan data masal. Selalu download file backup ke penyimpanan offline Anda untuk keamanan ekstra.</p>
                </div>
            </div>
        </div>
        HTML;
    }
}
