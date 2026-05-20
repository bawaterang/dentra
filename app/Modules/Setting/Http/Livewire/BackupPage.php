<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\TrxBackupLog;
use Illuminate\Support\Facades\File;
use App\Services\CsvEntityMappingService;
use App\Exports\DynamicCsvExport;
use App\Imports\DynamicCsvImport;
use Maatwebsite\Excel\Facades\Excel;


class BackupPage extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $selectedEntity = 'mst_pasien';
    public $importFile;

    protected $queryString = ['search'];

    #[Computed]
    public function entityOptions()
    {
        $entities = CsvEntityMappingService::getEntities();
        $options = [];
        foreach ($entities as $key => $data) {
            $options[] = [
                'value' => $key,
                'label' => $data['label']
            ];
        }
        return $options;
    }

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

            $mysqldumpPath = env('DB_MYSQLDUMP_PATH', 'mysqldump');
            
            $command = sprintf(
                '%s --user=%s --password=%s --host=%s %s > %s',
                $mysqldumpPath,
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

            $mysqlPath = env('DB_MYSQL_PATH', 'mysql');

            $command = sprintf(
                '%s --user=%s --password=%s --host=%s %s < %s',
                $mysqlPath,
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

    public function downloadTemplate()
    {
        $entities = CsvEntityMappingService::getEntities();
        if (!isset($entities[$this->selectedEntity])) return;

        $label = $entities[$this->selectedEntity]['label'];
        $filename = 'Template_' . str_replace(' ', '_', $label) . '.csv';

        return Excel::download(new DynamicCsvExport($this->selectedEntity, true), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportDataCsv()
    {
        $entities = CsvEntityMappingService::getEntities();
        if (!isset($entities[$this->selectedEntity])) return;

        $label = $entities[$this->selectedEntity]['label'];
        $filename = 'Data_' . str_replace(' ', '_', $label) . '_' . date('Ymd_His') . '.csv';

        return Excel::download(new DynamicCsvExport($this->selectedEntity, false), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    public function importDataCsv()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'selectedEntity' => 'required'
        ]);

        try {
            $import = new DynamicCsvImport($this->selectedEntity);
            Excel::import($import, $this->importFile->getRealPath(), null, \Maatwebsite\Excel\Excel::CSV);

            $msg = "Berhasil mengimpor {$import->successCount} baris data.";
            if (count($import->errorExceptions) > 0) {
                // If there are specific rows with error
                // We'll just show the first few to avoid too long messages
                $errorMsg = implode("<br>", array_slice($import->errorExceptions, 0, 5));
                if (count($import->errorExceptions) > 5) {
                    $errorMsg .= "<br>...dan " . (count($import->errorExceptions) - 5) . " error lainnya.";
                }
                $this->dispatch('alert', ['type' => 'warning', 'message' => $msg . '<br><br><b>Peringatan:</b><br>' . $errorMsg]);
            } else {
                $this->dispatch('alert', ['type' => 'success', 'message' => $msg]);
            }

            $this->reset('importFile');
            // Randomize component ID or similar to clear file input if needed, handled by Livewire automatically mostly.
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal mengimpor CSV: ' . $e->getMessage()]);
        }
    }


    public function render()
    {
        return view('livewire.modules.setting.backup-page');
    }
}
