<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxPasienBpjs extends Model
{
    protected $table = 'trx_pasien_bpjs';
    protected $fillable = ['no_kartu','nik','nama','pisa','sex','tgl_lahir','kd_provider','nm_provider','kd_cabang','nm_cabang','tgl_cetak_kartu','jns_kelas','jns_peserta','status_peserta','no_rm'];
}
