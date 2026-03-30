<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstJadwalDokter extends Model
{
    use HasFactory;
    
    protected $table = 'mst_jadwal_dokter';

    protected $fillable = [
        'kode_dokter', 'hari', 'jam_mulai', 'jam_selesai', 'status_kehadiran', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function dokter()
    {
        return $this->belongsTo(MstDokter::class, 'kode_dokter', 'kode_dokter');
    }
}
