<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class CsvEntityMappingService
{
    /**
     * Get the supported entities for CSV Export/Import.
     */
    public static function getEntities()
    {
        return [
            'mst_pasien' => [
                'label' => 'Master Pasien',
                'table' => 'mst_pasien',
                'model' => \App\Models\MstPasien::class,
                'mandatory' => ['no_rm', 'nama_pasien', 'jenis_kelamin']
            ],
            'mst_dokter' => [
                'label' => 'Master Dokter',
                'table' => 'mst_dokter',
                'model' => \App\Models\MstDokter::class,
                'mandatory' => ['nik', 'nama_lengkap', 'jenis_kelamin', 'spesialisasi']
            ],
            'mst_poli' => [
                'label' => 'Master Poliklinik',
                'table' => 'mst_poli',
                'model' => \App\Models\MstPoli::class,
                'mandatory' => ['kode_poli', 'nama_poli']
            ],
            'mst_tindakan' => [
                'label' => 'Master Tindakan',
                'table' => 'mst_tindakan',
                'model' => \App\Models\MstTindakan::class,
                'mandatory' => ['kode_tindakan', 'nama_tindakan', 'kategori']
            ],
            'mst_obat' => [
                'label' => 'Master Obat',
                'table' => 'mst_obat',
                'model' => \App\Models\MstObat::class,
                'mandatory' => ['kode_obat', 'nama_obat']
            ],
            'mst_bmhp' => [
                'label' => 'Master BMHP',
                'table' => 'mst_bmhp',
                'model' => \App\Models\MstBmhp::class,
                'mandatory' => ['kode_bmhp', 'nama_bmhp']
            ],
            'mst_asuransi' => [
                'label' => 'Master Asuransi',
                'table' => 'mst_asuransi',
                'model' => \App\Models\MstAsuransi::class,
                'mandatory' => ['kode_asuransi', 'nama_asuransi']
            ],
            'mst_diagnosis' => [
                'label' => 'Master Diagnosis',
                'table' => 'mst_diagnosis',
                'model' => \App\Models\MstDiagnosis::class,
                'mandatory' => ['kode_diagnosa', 'nama_diagnosa']
            ],
            'mst_kategori_gigi' => [
                'label' => 'Master Gigi',
                'table' => 'mst_kategori_gigi',
                'model' => \App\Models\MstKategoriGigi::class,
                'mandatory' => ['kode_kategori', 'nama_kategori']
            ],
            'mst_tarif' => [
                'label' => 'Master Tarif',
                'table' => 'mst_tarif',
                'model' => \App\Models\MstTarif::class,
                'mandatory' => ['kode_tarif', 'nama_tarif', 'nominal']
            ],
            'mst_survei' => [
                'label' => 'Master Survei',
                'table' => 'mst_survei',
                'model' => \App\Models\MstSurvei::class,
                'mandatory' => ['pertanyaan']
            ],
            'trx_antrian' => [
                'label' => 'Transaksi Antrian',
                'table' => 'trx_antrian',
                'model' => \App\Models\TrxAntrian::class,
                'mandatory' => ['nomor_antrian', 'tanggal', 'poli_id']
            ],
            'trx_pendaftaran' => [
                'label' => 'Transaksi Pendaftaran & Clinical Notes (SOAP)',
                'table' => 'trx_pendaftaran',
                'model' => \App\Models\TrxPendaftaran::class,
                'mandatory' => ['nomor_kunjungan', 'pasien_id', 'poli_id', 'dokter_id']
            ],
            'trx_screening' => [
                'label' => 'Transaksi Screening Pasien',
                'table' => 'trx_screening',
                'model' => \App\Models\TrxScreening::class,
                'mandatory' => ['pendaftaran_id', 'pasien_id', 'survei_id']
            ],
            'trx_diagnosis' => [
                'label' => 'Transaksi Pemeriksaan Diagnosis',
                'table' => 'trx_diagnosis',
                'model' => \App\Models\TrxDiagnosis::class,
                'mandatory' => ['nomor_kunjungan', 'kode_diagnosa']
            ],
            'trx_tindakan_medis' => [
                'label' => 'Transaksi Pemeriksaan Tindakan',
                'table' => 'trx_tindakan',
                'model' => \App\Models\TrxTindakan::class,
                'mandatory' => ['nomor_kunjungan', 'kode_tindakan']
            ],
            'trx_obat' => [
                'label' => 'Transaksi Peresepan Obat',
                'table' => 'trx_obat',
                'model' => null,
                'mandatory' => ['nomor_kunjungan', 'kode_obat']
            ],
            'trx_bmhp' => [
                'label' => 'Transaksi BMHP',
                'table' => 'trx_bmhp',
                'model' => null,
                'mandatory' => ['nomor_kunjungan', 'kode_bmhp']
            ],
            'trx_odontogram' => [
                'label' => 'Data Odontogram',
                'table' => 'trx_odontogram',
                'model' => null,
                'mandatory' => ['nomor_kunjungan', 'nomor_gigi', 'bagian']
            ],
            'trx_pemeriksaan' => [
                'label' => 'Transaksi Pemeriksaan',
                'table' => 'trx_pemeriksaan',
                'model' => null, // Let's use null so it uses DB::table
                'mandatory' => ['nomor_kunjungan', 'kode_dokter']
            ],
            'trx_ohis' => [
                'label' => 'Data OHIS',
                'table' => 'trx_ohis',
                'model' => null,
                'mandatory' => ['no_rm']
            ],
        ];
    }

    /**
     * Get the dynamic columns for a particular model, skipping common non-fillable fields.
     */
    public static function getColumnsForEntity($entityKey)
    {
        $entities = self::getEntities();
        if (!isset($entities[$entityKey])) {
            return [];
        }

        $tableName = $entities[$entityKey]['table'];
        
        $allColumns = Schema::getColumnListing($tableName);
        $ignoreColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'];
        
        $validColumns = array_diff($allColumns, $ignoreColumns);
        return array_values($validColumns);
    }
}
