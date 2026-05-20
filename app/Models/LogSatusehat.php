<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogSatusehat extends Model
{
    use HasFactory;

    protected $table = 'log_satusehat';
    protected $fillable = ['request_json', 'response_json', 'status'];
}
