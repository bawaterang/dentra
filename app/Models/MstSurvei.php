<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MstSurvei extends Model
{
    use HasFactory;

    protected $table = 'mst_survei';

    protected $fillable = [
        'pertanyaan',
        'jenis_survei',
        'status',
    ];

    public function screenings()
    {
        return $this->hasMany(TrxScreening::class, 'survei_id');
    }
}
