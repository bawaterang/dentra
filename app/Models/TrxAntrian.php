<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrxAntrian extends Model
{
    use HasFactory;

    protected $table = 'trx_antrian';

    protected $fillable = [
        'nomor_antrian',
        'tanggal_antrian',
        'jenis_antrian',
        'pasien_id',
        'nama_pasien_input_manual',
        'no_telepon_manual',
        'nik_manual',
        'kode_dokter',
        'kode_poli',
        'asuransi',
        'no_asuransi',
        'time_slot',
        'status',
        'waktu_panggil',
        'waktu_hadir',
    ];

    protected $casts = [
        'tanggal_antrian' => 'date',
        'waktu_panggil' => 'datetime',
        'waktu_hadir' => 'datetime',
    ];

    public function pasien()
    {
        return $this->belongsTo(MstPasien::class, 'pasien_id');
    }

    public function pendaftaran()
    {
        return $this->hasOne(TrxPendaftaran::class, 'antrian_id');
    }

    public function poli()
    {
        return $this->belongsTo(MstPoli::class, 'kode_poli', 'kode_poli');
    }

    public function dokter()
    {
        return $this->belongsTo(MstDokter::class, 'kode_dokter', 'kode_dokter');
    }

    public function getNamaPasienAttribute()
    {
        return $this->pasien?->nama_pasien ?? $this->nama_pasien_input_manual ?? '-';
    }
}
