<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrxScreening extends Model
{
    use HasFactory;

    protected $table = 'trx_screening';

    protected $fillable = [
        'pendaftaran_id',
        'pasien_id',
        'survei_id',
        'jawaban',
        'keterangan',
    ];

    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'pendaftaran_id');
    }

    public function pasien()
    {
        return $this->belongsTo(MstPasien::class, 'pasien_id');
    }

    public function survei()
    {
        return $this->belongsTo(MstSurvei::class, 'survei_id');
    }
}
