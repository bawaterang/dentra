<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\TrxBackupLog;
use Illuminate\Support\Facades\File;


class BackupPage extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = ['search'];

    #[Computed]
    public function backupList()
    {
        $query = TrxBackupLog::with('creator');

        if (! empty($this->search)) {
            $query->where('filename', 'like', '%'.$this->search.'%');
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function updatedSearch()
    {
        $this->resetPage();
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
                // No longer needed for DataTables, but kept for compatibility if any other JS is needed
            }
        }">
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .backup-row:hover {
                    background-color: #d8dce1ff !important;
                    transition: all 0.3s ease;
                }
                .action-btn-soft {
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: all 0.2s ease;
                }
                .search-focus-glow:focus {
                    box-shadow: 0 0 0 4px rgba(64, 81, 137, 0.15);
                    border-color: #f6f7fbff;
                }
                .pagination-custom nav span.relative.z-0 { 
                    display: flex !important; 
                    gap: 4px !important; 
                    flex-wrap: wrap !important;
                    justify-content: center !important;
                }
                .pagination-custom nav a, 
                .pagination-custom nav span[aria-disabled="true"] span,
                .pagination-custom nav span[aria-current="page"] span {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    min-width: 38px !important;
                    height: 38px !important;
                    padding: 0 12px !important;
                    border-radius: 8px !important;
                    border: 1px solid #767070ff !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    transition: all 0.2s ease-in-out !important;
                    background-color: #ffffff !important;
                    color: #475569 !important;
                    text-decoration: none !important;
                }
                .pagination-custom nav a:hover {
                    background-color: #f1f5f9 !important;
                    border-color: #405189 !important;
                    color: #405189 !important;
                    transform: translateY(-1px) !important;
                }
                .pagination-custom nav p.text-sm {
                    display: none !important;
                }
                .pagination-custom nav > div:last-child > div:first-child {
                    display: none !important;
                }
                .pagination-custom [aria-current="page"], 
                .pagination-custom [aria-current="page"] *,
                .pagination-custom .active,
                .pagination-custom .active * {
                    background-color: #405189 !important;
                    color: #ffffff !important;
                    border-color: #405189 !important;
                    box-shadow: 0 4px 10px rgba(64, 81, 137, 0.3) !important;
                    z-index: 10 !important;
                }
            </style>

            
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
                <div class="p-4 border-b border-[#eff2f7] bg-[#f3f6f9]/30 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <h6 class="mb-0 text-sm font-bold text-gray-600 uppercase tracking-wider"><i class="ri-history-line mr-2"></i>Riwayat Backup Database</h6>
                    
                    <div class="relative w-full lg:w-96">
                        <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" wire:model.live.debounce.300ms="search" 
                               class="w-full bg-white border border-gray-200 rounded-xl py-2 pl-12 pr-4 text-xs font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                               placeholder="Cari file backup...">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama File</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Ukuran</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Dibuat Oleh</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Tanggal & Waktu</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($this->backupList as $backup)
                                <tr wire:key="backup-{{ $backup->id }}" class="backup-row transition-all duration-200">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center shadow-inner">
                                                <i class="ri-file-zip-line text-lg"></i>
                                            </div>
                                            <span class="text-sm font-bold text-[#405189]">{{ $backup->filename }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 text-[#405189] text-[11px] font-black border border-indigo-100 uppercase tracking-tight">
                                            {{ $backup->size }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-[#405189] text-white flex items-center justify-center text-[10px] font-black shadow-sm">
                                                {{ strtoupper(substr($backup->creator->full_name ?? 'S', 0, 1)) }}
                                            </div>
                                            <span class="text-xs text-gray-700 font-bold tracking-tight">{{ $backup->creator->full_name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-[11px] text-gray-500 font-bold uppercase tracking-tight">{{ $backup->created_at->format('d M Y, H:i:s') }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <button wire:click="downloadBackup({{ $backup->id }})" class="action-btn-soft bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white shadow-sm" title="Download">
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
                                            " class="action-btn-soft bg-amber-50 text-amber-500 hover:bg-amber-500 hover:text-white shadow-sm" title="Restore">
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
                                            " class="action-btn-soft bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white shadow-sm" title="Hapus">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-20 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                                <i class="ri-database-2-line text-6xl text-gray-200"></i>
                                            </div>
                                            <p class="text-xl font-black text-gray-400">Belum Ada Riwayat Backup</p>
                                            <p class="text-xs text-gray-300 mt-1 uppercase tracking-widest font-bold">Silakan buat backup pertama Anda menggunakan tombol di atas</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($this->backupList->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->backupList->firstItem() }} - {{ $this->backupList->lastItem() }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->backupList->total()) }}</span> 
                            <span class="hidden sm:inline">log backup ditemukan</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->backupList->links() }}
                    </div>
                </div>
                @endif
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
