<?php

namespace App\Traits;

trait DynamicKodeGenerator
{
    /**
     * Generate the next code dynamically based on existing data.
     * 
     * If the table is empty, returns null (user must input manually).
     * If the table has ≥1 record, analyzes the last record's code pattern
     * (prefix + numeric suffix) and auto-increments.
     *
     * @param  string  $table     DB table name (e.g. 'mst_poli')
     * @param  string  $column    Column name (e.g. 'kode_poli')
     * @param  bool    $withTrashed  Whether to include soft-deleted records
     * @return string|null  The next code, or null if table is empty
     */
    private function generateDynamicKode(string $table, string $column, bool $withTrashed = true): ?string
    {
        $query = \DB::table($table);

        // Get the last record by the kode column (descending) for best pattern match
        $last = $query->orderByRaw("LENGTH($column) DESC, $column DESC")->first();

        if (!$last || empty($last->$column)) {
            return null; // Table empty → user fills manually
        }

        $lastKode = $last->$column;

        // Extract prefix (non-numeric) and numeric suffix
        // e.g. "POL003" → prefix = "POL", number = "003"
        // e.g. "D00005" → prefix = "D", number = "00005"
        // e.g. "TDK00001" → prefix = "TDK", number = "00001"
        if (preg_match('/^(.*?)(\d+)$/', $lastKode, $matches)) {
            $prefix = $matches[1];
            $numStr = $matches[2];
            $padLength = strlen($numStr);
            $nextNum = (int) $numStr + 1;

            return $prefix . str_pad($nextNum, $padLength, '0', STR_PAD_LEFT);
        }

        // Fallback: if no numeric suffix found, just append "001"
        return $lastKode . '001';
    }

    /**
     * Check if the master table already has data (for determining readonly state).
     */
    private function hasExistingKode(string $table, string $column): bool
    {
        return \DB::table($table)->whereNotNull($column)->where($column, '!=', '')->exists();
    }
}
