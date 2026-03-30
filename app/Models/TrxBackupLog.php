<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxBackupLog extends Model
{
    protected $table = 'trx_backup_log';
    protected $fillable = ['filename', 'size', 'disk', 'status', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
