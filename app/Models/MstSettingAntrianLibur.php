<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MstSettingAntrianLibur extends Model
{
    use HasFactory;

    protected $table = 'mst_setting_antrian_libur';

    protected $fillable = [
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
    ];

    /**
     * Check if a given date is a holiday.
     */
    public static function isHoliday($date)
    {
        $checkDate = Carbon::parse($date)->format('Y-m-d');
        
        return self::where('tanggal_mulai', '<=', $checkDate)
            ->where('tanggal_selesai', '>=', $checkDate)
            ->exists();
    }
}
