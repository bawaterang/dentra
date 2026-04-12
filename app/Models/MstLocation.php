<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstLocation extends Model
{
    use HasFactory;

    protected $table = 'mst_location';

    protected $fillable = [
        'organization_id',
        'location_id',
        'location_name',
        'description',

        'longitude',
        'latitude',
        'status',
    ];
}
