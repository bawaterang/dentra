<?php

namespace App\Modules\Bridging\Http\Livewire;

use Livewire\Component;
use App\Modules\Bridging\Services\BpjsPcareService;

class DokumentasiApiPage extends Component
{
    // Selected endpoint
    public $selectedEndpoint = 'dokter';
    public $searchEndpoint = '';

    // Parameters
    public $paramStart = 0;
    public $paramLimit = 20;
    public $paramKeyword = '';
    public $paramNoKartu = '';
    public $paramNik = '';
    public $paramNoKunjungan = '';
    public $paramIsRawatInap = false;
    public $paramKdTkp = '10';
    public $paramTanggal = '';
    public $paramKodePoli = '';

    // Spesialis parameters
    public $paramKdSpesialis = '';
    public $paramKdSubSpesialis = '';
    public $paramKdSarana = '';
    public $paramTglEstRujuk = '';

    // Kunjungan body (single JSON textarea)
    public $kunjunganBodyJson = '';
    public $paramNoKunjunganBpjs = '';

    // Obat, Pendaftaran & Tindakan parameters
    public $obatBodyJson = '';
    public $pendaftaranBodyJson = '';
    public $tindakanBodyJson = '';
    public $paramKdObatSK = '';
    public $paramNoUrut = '';
    public $paramTglDaftar = '';
    public $paramKdTindakanSK = '';

    // Antrean parameters
    public $antreanBodyJson = '';

    // Patient search for auto-fill
    public $searchPasienQuery = '';
    public $foundPasiens = [];

    // Response state
    public $responseData = [];
    public $responseColumns = [];
    public $totalData = 0;
    public $isLoading = false;
    public $errorMessage = '';
    public $successMessage = '';
    public $rawResponse = '';
    public $lastFetched = '';
    public $responseTime = 0;
    public $responseMetaCode;
    public $responseMetaMessage;

    /**
     * Definisi semua endpoint PCare BPJS yang tersedia
     */
    public function getEndpointDefinitionsProperty(): array
    {
        return [
            // === REFERENSI ===
            'dokter' => [
                'label' => 'Data Dokter',
                'category' => 'Referensi',
                'icon' => 'ri-stethoscope-line',
                'color' => '#0d6efd',
                'method' => 'GET',
                'endpoint' => '{Base URL}/dokter/{start}/{limit}',
                'description' => 'Mengambil data dokter yang terdaftar di faskes.',
                'params' => ['start', 'limit'],
                'response_fields' => ['kdDokter' => 'Kode Dokter', 'nmDokter' => 'Nama Dokter'],
            ],
            'add_antrean' => [
                'label' => 'Tambah Antrean',
                'category' => 'Antrean',
                'icon' => 'ri-user-add-line',
                'color' => '#0ab39c',
                'method' => 'POST',
                'endpoint' => '{Base URL}/antrean/add',
                'description' => 'Menambahkan data antrean baru ke sistem BPJS.',
                'params' => ['antrean_body'],
                'response_fields' => ['message' => 'Status'],
            ],
            'update_status_antrean' => [
                'label' => 'Update Status Antrean',
                'category' => 'Antrean',
                'icon' => 'ri-user-follow-line',
                'color' => '#3577f1',
                'method' => 'POST',
                'endpoint' => '{Base URL}/antrean/add',
                'description' => 'Update status kehadiran antrean (hadir/tidak hadir).',
                'params' => ['antrean_body'],
                'response_fields' => ['message' => 'Status'],
            ],
            'batal_antrean' => [
                'label' => 'Batal Antrean',
                'category' => 'Antrean',
                'icon' => 'ri-user-unfollow-line',
                'color' => '#f06548',
                'method' => 'POST',
                'endpoint' => '{Base URL}/antrean/batal',
                'description' => 'Mambatalkan antrean yang sudah terdaftar.',
                'params' => ['antrean_body'],
                'response_fields' => ['message' => 'Status'],
            ],
            'poli' => [
                'label' => 'Data Poli',
                'category' => 'Referensi',
                'icon' => 'ri-hospital-line',
                'color' => '#0ab39c',
                'method' => 'GET',
                'endpoint' => '{Base URL}/poli/fktp/{start}/{limit}',
                'description' => 'Mengambil data poli yang tersedia di FKTP.',
                'params' => ['start', 'limit'],
                'response_fields' => ['kdPoli' => 'Kode Poli', 'nmPoli' => 'Nama Poli'],
            ],
            'diagnosa' => [
                'label' => 'Data Diagnosa (ICD-10)',
                'category' => 'Referensi',
                'icon' => 'ri-heart-pulse-line',
                'color' => '#f06548',
                'method' => 'GET',
                'endpoint' => '{Base URL}/diagnosa/{keyword}/{start}/{limit}',
                'description' => 'Mencari data diagnosa berdasarkan kode ICD-10 atau nama penyakit dengan paginasi.',
                'params' => ['keyword', 'start', 'limit'],
                'response_fields' => ['kdDiag' => 'Kode Diagnosa', 'nmDiag' => 'Nama Diagnosa'],
            ],
            'kesadaran' => [
                'label' => 'Data Kesadaran',
                'category' => 'Referensi',
                'icon' => 'ri-psychotherapy-line',
                'color' => '#405189',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kesadaran',
                'description' => 'Mengambil data referensi tingkat kesadaran pasien.',
                'params' => [],
                'response_fields' => ['kdSadar' => 'Kode Kesadaran', 'nmSadar' => 'Nama Kesadaran'],
            ],
            'spesialis' => [
                'label' => 'Data Spesialis',
                'category' => 'Referensi',
                'icon' => 'ri-medal-line',
                'color' => '#ebab0c',
                'method' => 'GET',
                'endpoint' => '{Base URL}/spesialis',
                'description' => 'Mengambil data referensi spesialis.',
                'params' => [],
                'response_fields' => ['kdSpesialis' => 'Kode Spesialis', 'nmSpesialis' => 'Nama Spesialis'],
            ],
            'subspesialis' => [
                'label' => 'Data Sub Spesialis',
                'category' => 'Referensi',
                'icon' => 'ri-medal-2-line',
                'color' => '#fd7e14',
                'method' => 'GET',
                'endpoint' => '{Base URL}/spesialis/{kdSpesialis}/subspesialis',
                'description' => 'Mengambil referensi sub spesialis berdasarkan kode spesialis.',
                'params' => ['kdSpesialis'],
                'response_fields' => ['kdSubSpesialis' => 'Kd Sub Spesialis', 'nmSubSpesialis' => 'Nm Sub Spesialis', 'kdPoliRujuk' => 'Poli Rujuk'],
            ],
            'sarana' => [
                'label' => 'Data Sarana',
                'category' => 'Referensi',
                'icon' => 'ri-building-line',
                'color' => '#198754',
                'method' => 'GET',
                'endpoint' => '{Base URL}/spesialis/sarana',
                'description' => 'Mengambil referensi sarana untuk rujukan.',
                'params' => [],
                'response_fields' => ['kdSarana' => 'Kd Sarana', 'nmSarana' => 'Nm Sarana'],
            ],
            'khusus' => [
                'label' => 'Data Khusus',
                'category' => 'Referensi',
                'icon' => 'ri-star-line',
                'color' => '#dc3545',
                'method' => 'GET',
                'endpoint' => '{Base URL}/spesialis/khusus',
                'description' => 'Mengambil referensi rujukan khusus (HD, dll).',
                'params' => [],
                'response_fields' => ['kdKhusus' => 'Kd Khusus', 'nmKhusus' => 'Nm Khusus'],
            ],
            'faskes_rujukan_subspesialis' => [
                'label' => 'Faskes Rujukan Sub Spesialis',
                'category' => 'Referensi',
                'icon' => 'ri-hospital-line',
                'color' => '#6f42c1',
                'method' => 'GET',
                'endpoint' => '{Base URL}/spesialis/rujuk/subspesialis/{kdSubSpesialis}/sarana/{kdSarana}/tglEstRujuk/{tglEstRujuk}',
                'description' => 'Mengambil Faskes Rujukan berdasarkan kriteria rujukan sub spesialis.',
                'params' => ['kdSubSpesialis', 'kdSarana', 'tglEstRujuk'],
                'response_fields' => ['kdppk' => 'Kd PPK', 'nmppk' => 'Nama PPK', 'kelas' => 'Kelas', 'jadwal' => 'Jadwal', 'distance' => 'Jarak'],
            ],
            'statuspulang' => [
                'label' => 'Status Pulang',
                'category' => 'Referensi',
                'icon' => 'ri-logout-box-r-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/statuspulang/rawatInap/{isRawatInap}',
                'description' => 'Mengambil data referensi status pulang pasien (Rawat Inap true/false).',
                'params' => ['isRawatInap'],
                'response_fields' => ['kdStatusPulang' => 'Kode Status', 'nmStatusPulang' => 'Nama Status'],
            ],
            'tindakan' => [
                'label' => 'Data Tindakan',
                'category' => 'Referensi',
                'icon' => 'ri-surgical-mask-line',
                'color' => '#f672a7',
                'method' => 'GET',
                'endpoint' => '{Base URL}/tindakan/kdTkp/{kdTkp}/{start}/{limit}',
                'description' => 'Mencari data tindakan/prosedur berdasarkan kode TKP dan paginasi.',
                'params' => ['kdTkp', 'start', 'limit'],
                'response_fields' => ['kdTindakan' => 'Kode Tindakan', 'nmTindakan' => 'Nama Tindakan'],
            ],
            'alergi' => [
                'label' => 'Data Alergi',
                'category' => 'Referensi',
                'icon' => 'ri-virus-line',
                'color' => '#f06548',
                'method' => 'GET',
                'endpoint' => '{Base URL}/alergi/jenis/{kdJenis}',
                'description' => 'Mengambil referensi jenis alergi (01:Makanan, 02:Udara, 03:Obat).',
                'params' => ['keyword'],
                'response_fields' => ['kdAlergi' => 'Kd Alergi', 'nmAlergi' => 'Nm Alergi'],
            ],
            'prognosa' => [
                'label' => 'Data Prognosa',
                'category' => 'Referensi',
                'icon' => 'ri-direction-line',
                'color' => '#0ab39c',
                'method' => 'GET',
                'endpoint' => '{Base URL}/prognosa',
                'description' => 'Mengambil referensi prognosa.',
                'params' => [],
                'response_fields' => ['kdPrognosa' => 'Kd Prognosa', 'nmPrognosa' => 'Nm Prognosa'],
            ],
            'obat' => [
                'label' => 'Data Obat (DPHO)',
                'category' => 'Referensi',
                'icon' => 'ri-capsule-line',
                'color' => '#0d6efd',
                'method' => 'GET',
                'endpoint' => '{Base URL}/obat/dpho/{keyword}/{start}/{limit}',
                'description' => 'Mencari data obat dari Daftar dan Plafon Harga Obat (DPHO) dengan paginasi.',
                'params' => ['keyword', 'start', 'limit'],
                'response_fields' => ['kdObat' => 'Kode Obat', 'nmObat' => 'Nama Obat'],
            ],
            'provider' => [
                'label' => 'Data Provider (Faskes Rujukan)',
                'category' => 'Referensi',
                'icon' => 'ri-building-2-line',
                'color' => '#0ab39c',
                'method' => 'GET',
                'endpoint' => '{Base URL}/provider/{start}/{limit}',
                'description' => 'Mengambil data faskes provider rujukan dengan paginasi.',
                'params' => ['start', 'limit'],
                'response_fields' => ['kdProvider' => 'Kode Provider', 'nmProvider' => 'Nama Provider'],
            ],

            // === PESERTA ===
            'peserta_no_kartu' => [
                'label' => 'Peserta (No. Kartu)',
                'category' => 'Peserta',
                'icon' => 'ri-user-heart-line',
                'color' => '#405189',
                'method' => 'GET',
                'endpoint' => '{Base URL}/peserta/{noKartu}',
                'description' => 'Mengambil data peserta BPJS berdasarkan nomor kartu.',
                'params' => ['noKartu'],
                'response_fields' => ['noKartu' => 'No. Kartu', 'nama' => 'Nama', 'nik' => 'NIK', 'sex' => 'L/P', 'tglLahir' => 'Tgl Lahir', 'kdProviderPst' => 'Kode Provider', 'nmProviderPst' => 'Provider', 'jnsPeserta' => 'Jenis Peserta', 'aktif' => 'Status'],
            ],
            'peserta_nik' => [
                'label' => 'Peserta (NIK)',
                'category' => 'Peserta',
                'icon' => 'ri-user-search-line',
                'color' => '#f06548',
                'method' => 'GET',
                'endpoint' => '{Base URL}/peserta/nik/{nik}',
                'description' => 'Mengambil data peserta BPJS berdasarkan NIK.',
                'params' => ['nik'],
                'response_fields' => ['noKartu' => 'No. Kartu', 'nama' => 'Nama', 'nik' => 'NIK', 'sex' => 'L/P', 'tglLahir' => 'Tgl Lahir', 'kdProviderPst' => 'Kode Provider', 'nmProviderPst' => 'Provider', 'jnsPeserta' => 'Jenis Peserta', 'aktif' => 'Status'],
            ],

            // === KUNJUNGAN ===
            'riwayat_kunjungan' => [
                'label' => 'Riwayat Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-history-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kunjungan/peserta/{noKartu}',
                'description' => 'Mengambil riwayat kunjungan peserta berdasarkan nomor kartu.',
                'params' => ['noKartu'],
                'response_fields' => ['noKunjungan' => 'No. Kunjungan', 'tglKunjungan' => 'Tgl Kunjungan', 'kdPoli' => 'Kode Poli', 'nmPoli' => 'Poli'],
            ],

            // === RUJUKAN ===
            'rujukan' => [
                'label' => 'Rujukan (No. Kunjungan)',
                'category' => 'Rujukan',
                'icon' => 'ri-share-forward-line',
                'color' => '#f672a7',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kunjungan/rujukan/{noKunjungan}',
                'description' => 'Mengambil data rujukan berdasarkan nomor kunjungan.',
                'params' => ['noKunjungan'],
                'response_fields' => ['noRujukan' => 'No. Rujukan', 'kdProviderTujuan' => 'Kd Provider Tujuan', 'nmProviderTujuan' => 'Provider Tujuan'],
            ],
            'rujukan_peserta' => [
                'label' => 'Rujukan (No. Kartu)',
                'category' => 'Rujukan',
                'icon' => 'ri-user-following-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kunjungan/peserta/{noKartu}',
                'description' => 'Mengambil data rujukan/kunjungan berdasarkan nomor kartu peserta.',
                'params' => ['noKartu'],
                'response_fields' => ['noKunjungan' => 'No. Kunjungan', 'tglKunjungan' => 'Tgl Kunjungan', 'noRujukan' => 'No. Rujukan', 'kdProviderTujuan' => 'Kd Tujuan'],
            ],

            // === KUNJUNGAN CRUD ===
            'add_kunjungan' => [
                'label' => 'Tambah Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-add-circle-line',
                'color' => '#0ab39c',
                'method' => 'POST',
                'endpoint' => '{Base URL}/kunjungan',
                'description' => 'Menambahkan data kunjungan baru ke PCare BPJS. Mendukung rujukan Hemodialisa dan Spesialis.',
                'params' => ['kunjungan_body'],
                'response_fields' => ['field' => 'Field', 'message' => 'No. Kunjungan BPJS'],
            ],
            'edit_kunjungan' => [
                'label' => 'Edit Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-edit-2-line',
                'color' => '#f7b84b',
                'method' => 'PUT',
                'endpoint' => '{Base URL}/kunjungan',
                'description' => 'Mengubah data kunjungan yang sudah terdaftar di PCare BPJS.',
                'params' => ['kunjungan_body'],
                'response_fields' => ['message' => 'Message'],
            ],
            'delete_kunjungan' => [
                'label' => 'Hapus Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-delete-bin-line',
                'color' => '#f06548',
                'method' => 'DELETE',
                'endpoint' => '{Base URL}/kunjungan/{noKunjungan}',
                'description' => 'Menghapus data kunjungan dari PCare BPJS berdasarkan nomor kunjungan.',
                'params' => ['noKunjunganBpjs'],
                'response_fields' => ['message' => 'Message'],
            ],

            // === OBAT ===
            'obat_kunjungan' => [
                'label' => 'Obat by Kunjungan',
                'category' => 'Obat',
                'icon' => 'ri-medicine-bottle-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/obat/kunjungan/{noKunjungan}',
                'description' => 'Mengambil daftar obat yang diberikan pada suatu kunjungan.',
                'params' => ['noKunjungan'],
                'response_fields' => ['kdObatSK' => 'Kd Obat SK', 'obat.nmObat' => 'Nm Obat', 'jmlObat' => 'Jml Obat', 'signa1' => 'Signa1', 'signa2' => 'Signa2'],
            ],
            'add_obat' => [
                'label' => 'Tambah Obat',
                'category' => 'Obat',
                'icon' => 'ri-add-circle-line',
                'color' => '#0ab39c',
                'method' => 'POST',
                'endpoint' => '{Base URL}/obat/kunjungan',
                'description' => 'Menambahkan obat ke dalam kunjungan BPJS.',
                'params' => ['obat_body'],
                'response_fields' => ['message' => 'Message'],
            ],
            'delete_obat' => [
                'label' => 'Hapus Obat Kunjungan',
                'category' => 'Obat',
                'icon' => 'ri-delete-bin-line',
                'color' => '#f06548',
                'method' => 'DELETE',
                'endpoint' => '{Base URL}/obat/{kdObatSK}/kunjungan/{noKunjungan}',
                'description' => 'Menghapus obat dari kunjungan BPJS.',
                'params' => ['kdObatSK', 'noKunjungan'],
                'response_fields' => ['message' => 'Message'],
            ],

            // === PENDAFTARAN ===
            'pendaftaran_no_urut' => [
                'label' => 'Pendaftaran by No Urut',
                'category' => 'Pendaftaran',
                'icon' => 'ri-archive-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/pendaftaran/noUrut/{noUrut}/tglDaftar/{tglDaftar}',
                'description' => 'Mengambil data pendaftaran berdasarkan nomor urut dan tanggal daftar.',
                'params' => ['noUrut', 'tglDaftar'],
                'response_fields' => ['noUrut' => 'No Urut', 'tgldaftar' => 'Tgl Daftar', 'peserta.nama' => 'Nama Pasien', 'poli.nmPoli' => 'Poli', 'status' => 'Status'],
            ],
            'pendaftaran_provider' => [
                'label' => 'Pendaftaran Provider',
                'category' => 'Pendaftaran',
                'icon' => 'ri-hospital-line',
                'color' => '#3577f1',
                'method' => 'GET',
                'endpoint' => '{Base URL}/pendaftaran/tglDaftar/{tglDaftar}/{start}/{limit}',
                'description' => 'Mengambil semua pasien yang mendaftar ke provider pada tanggal tertentu.',
                'params' => ['tglDaftar', 'start', 'limit'],
                'response_fields' => ['noUrut' => 'No Urut', 'tgldaftar' => 'Tgl Daftar', 'peserta.nama' => 'Nama Pasien', 'poli.nmPoli' => 'Poli', 'status' => 'Status'],
            ],
            'add_pendaftaran' => [
                'label' => 'Tambah Pendaftaran',
                'category' => 'Pendaftaran',
                'icon' => 'ri-add-circle-line',
                'color' => '#0ab39c',
                'method' => 'POST',
                'endpoint' => '{Base URL}/pendaftaran',
                'description' => 'Menambahkan data pendaftaran peserta BPJS ke PCare.',
                'params' => ['pendaftaran_body'],
                'response_fields' => ['message' => 'Message'],
            ],
            'delete_pendaftaran' => [
                'label' => 'Hapus Pendaftaran',
                'category' => 'Pendaftaran',
                'icon' => 'ri-delete-bin-line',
                'color' => '#f06548',
                'method' => 'DELETE',
                'endpoint' => '{Base URL}/pendaftaran/peserta/{noKartu}/tglDaftar/{tglDaftar}/noUrut/{noUrut}/kdPoli/{kdPoli}',
                'description' => 'Menghapus pendaftaran BPJS.',
                'params' => ['noKartu', 'tglDaftar', 'noUrut', 'kodepoli'],
                'response_fields' => ['message' => 'Message'],
            ],

            // === TINDAKAN ===
            'tindakan_kunjungan' => [
                'label' => 'Tindakan Kunjungan',
                'category' => 'Tindakan',
                'icon' => 'ri-syringe-line',
                'color' => '#f672a7',
                'method' => 'GET',
                'endpoint' => '{Base URL}/tindakan/kunjungan/{noKunjungan}',
                'description' => 'Mengambil data tindakan/prosedur yang diberikan pada suatu kunjungan.',
                'params' => ['noKunjungan'],
                'response_fields' => ['kdTindakanSK' => 'Kd Tindakan SK', 'kdTindakan' => 'Kd Tindakan', 'nmTindakan' => 'Nm Tindakan', 'biaya' => 'Biaya', 'hasil' => 'Hasil'],
            ],
            'add_tindakan' => [
                'label' => 'Tambah Tindakan',
                'category' => 'Tindakan',
                'icon' => 'ri-add-circle-line',
                'color' => '#0ab39c',
                'method' => 'POST',
                'endpoint' => '{Base URL}/tindakan',
                'description' => 'Menambahkan data tindakan pada kunjungan.',
                'params' => ['tindakan_body'],
                'response_fields' => ['message' => 'Message'],
            ],
            'edit_tindakan' => [
                'label' => 'Edit Tindakan',
                'category' => 'Tindakan',
                'icon' => 'ri-edit-2-line',
                'color' => '#f7b84b',
                'method' => 'PUT',
                'endpoint' => '{Base URL}/tindakan',
                'description' => 'Mengubah data tindakan pada kunjungan.',
                'params' => ['tindakan_body'],
                'response_fields' => ['message' => 'Message'],
            ],
            'delete_tindakan' => [
                'label' => 'Hapus Tindakan',
                'category' => 'Tindakan',
                'icon' => 'ri-delete-bin-line',
                'color' => '#f06548',
                'method' => 'DELETE',
                'endpoint' => '{Base URL}/tindakan/{kdTindakanSK}/kunjungan/{noKunjungan}',
                'description' => 'Menghapus data tindakan/prosedur dari kunjungan BPJS.',
                'params' => ['kdTindakanSK', 'noKunjungan'],
                'response_fields' => ['message' => 'Message'],
            ],


            // === LAINNYA ===
            'skrinning_peserta' => [
                'label' => 'Skrinning Peserta',
                'category' => 'Skrinning',
                'icon' => 'ri-health-book-line',
                'color' => '#4b38b3',
                'method' => 'GET',
                'endpoint' => '{Base URL}/skrinning/peserta/{noKartuOrNama}/{start}/{limit}',
                'description' => 'Rekapitulasi Skrining Riwayat Kesehatan per peserta.',
                'params' => ['keyword', 'start', 'limit'],
                'response_fields' => ['noKunjungan' => 'No Kunjungan', 'tglKunjungan' => 'Tgl Kunjungan', 'peserta.nama' => 'Nama'],
            ],
            'prolanis_dm' => [
                'label' => 'Prolanis DM',
                'category' => 'Skrinning',
                'icon' => 'ri-heart-pulse-line',
                'color' => '#f06548',
                'method' => 'GET',
                'endpoint' => '{Base URL}/skrinning/prolanis/dm/{noKartuOrNama}/{start}/{limit}',
                'description' => 'Data peserta prolanis DM.',
                'params' => ['keyword', 'start', 'limit'],
                'response_fields' => ['noKartu' => 'No Kartu', 'nama' => 'Nama'],
            ],
            'prolanis_ht' => [
                'label' => 'Prolanis HT',
                'category' => 'Skrinning',
                'icon' => 'ri-temp-hot-line',
                'color' => '#405189',
                'method' => 'GET',
                'endpoint' => '{Base URL}/skrinning/prolanis/ht/{noKartuOrNama}/{start}/{limit}',
                'description' => 'Data peserta prolanis HT.',
                'params' => ['keyword', 'start', 'limit'],
                'response_fields' => ['noKartu' => 'No Kartu', 'nama' => 'Nama'],
            ],
            'mcu' => [
                'label' => 'Data MCU',
                'category' => 'Lainnya',
                'icon' => 'ri-file-list-3-line',
                'color' => '#405189',
                'method' => 'GET',
                'endpoint' => '{Base URL}/MCU/kunjungan/{noKunjungan}',
                'description' => 'Mengambil data Medical Check Up (MCU) peserta berdasarkan nomor kunjungan.',
                'params' => ['noKunjungan'],
                'response_fields' => ['noKartu' => 'No. Kartu', 'nama' => 'Nama'],
            ],

            // === ANTREAN ONLINE ===
            'antrean_poli' => [
                'label' => 'Referensi Poli (Antrol)',
                'category' => 'Antrean',
                'icon' => 'ri-hospital-line',
                'color' => '#878a99',
                'method' => 'GET',
                'endpoint' => '{Base URL Antrean}/ref/poli/tanggal/{tanggal}',
                'description' => 'Mengambil referensi poli antrean online berdasarkan tanggal.',
                'params' => ['tanggal'],
                'response_fields' => ['nmpoli' => 'Nama Poli', 'nmsubspesialis' => 'Nama Sub Spesialis', 'kdpoli' => 'Kode Poli', 'kdsubspesialis' => 'Kode Sub Spesialis'],
            ],
            'antrean_dokter' => [
                'label' => 'Referensi Dokter (Antrol)',
                'category' => 'Antrean',
                'icon' => 'ri-stethoscope-line',
                'color' => '#878a99',
                'method' => 'GET',
                'endpoint' => '{Base URL Antrean}/ref/dokter/kodepoli/{kodepoli}/tanggal/{tanggal}',
                'description' => 'Mengambil referensi jadwal dokter antrean online.',
                'params' => ['kodepoli', 'tanggal'],
                'response_fields' => ['namadokter' => 'Nama Dokter', 'kodedokter' => 'Kode Dokter', 'jadwal' => 'Jadwal', 'kapasitaspasien' => 'Kapasitas'],
            ],
        ];
    }

    /**
     * Computed: Grouped endpoints by category
     */
    public function getGroupedEndpointsProperty(): array
    {
        $grouped = [];
        foreach ($this->endpointDefinitions as $key => $ep) {
            $grouped[$ep['category']][$key] = $ep;
        }
        return $grouped;
    }

    /**
     * Computed: Current endpoint definition
     */
    public function getCurrentEndpointProperty(): ?array
    {
        return $this->endpointDefinitions[$this->selectedEndpoint] ?? null;
    }

    /**
     * Reset state when switching endpoint
     */
    public function updatedSelectedEndpoint()
    {
        $this->responseData = [];
        $this->responseColumns = [];
        $this->totalData = 0;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->rawResponse = '';
        $this->responseMetaCode = null;
        $this->responseMetaMessage = null;
        $this->lastFetched = '';
        $this->responseTime = 0;
    }

    /**
     * Execute the selected endpoint
     */
    public function executeEndpoint()
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->rawResponse = '';
        $this->responseData = [];

        try {
            $service = new BpjsPcareService();

            if (!$service->isConfigured()) {
                $this->errorMessage = 'Konfigurasi BPJS belum lengkap. Silakan isi ConsID, Secret Key, dan Base URL PCare di halaman Setting API.';
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
                $this->isLoading = false;
                return;
            }

            $startTime = microtime(true);

            $result = match($this->selectedEndpoint) {
                'dokter' => $service->getDokter((int) $this->paramStart, (int) $this->paramLimit),
                'poli' => $service->getPoli((int) $this->paramStart, (int) $this->paramLimit),
                'diagnosa' => $service->getDiagnosa($this->paramKeyword ?: 'A00', (int) $this->paramStart, (int) $this->paramLimit),
                'kesadaran' => $service->getKesadaran(),
                'spesialis' => $service->getSpesialis(),
                'subspesialis' => $service->getSubSpesialis($this->paramKdSpesialis),
                'sarana' => $service->getSarana(),
                'khusus' => $service->getKhusus(),
                'faskes_rujukan_subspesialis' => $service->getFaskesRujukanSubSpesialis($this->paramKdSubSpesialis, $this->paramKdSarana, $this->paramTglEstRujuk ?: now()->format('d-m-Y')),
                'statuspulang' => $service->getStatusPulang((bool) $this->paramIsRawatInap),
                'tindakan' => $service->getTindakan($this->paramKdTkp, (int) $this->paramStart, (int) $this->paramLimit),
                'tindakan_kunjungan' => $service->getTindakanByKunjungan($this->paramNoKunjungan),
                'add_tindakan' => $service->addTindakan($this->parseTindakanBody()),
                'edit_tindakan' => $service->editTindakan($this->parseTindakanBody()),
                'delete_tindakan' => $service->deleteTindakan($this->paramKdTindakanSK, $this->paramNoKunjungan),
                'alergi' => $service->getAlergi($this->paramKeyword ?: '01'),
                'prognosa' => $service->getPrognosa(),
                'skrinning_peserta' => $service->getSkrinningPeserta($this->paramKeyword ?: 'PARAM', (int) $this->paramStart, (int) $this->paramLimit),
                'prolanis_dm' => $service->getProlanisDM($this->paramKeyword ?: 'PARAM', (int) $this->paramStart, (int) $this->paramLimit),
                'prolanis_ht' => $service->getProlanisHT($this->paramKeyword ?: 'PARAM', (int) $this->paramStart, (int) $this->paramLimit),
                'obat' => $service->getObat($this->paramKeyword ?: 'PARAM', (int) $this->paramStart, (int) $this->paramLimit),
                'provider' => $service->getProvider((int) $this->paramStart, (int) $this->paramLimit),
                'peserta_no_kartu' => $service->getPesertaByNoKartu($this->paramNoKartu),
                'peserta_nik' => $service->getPesertaByNik($this->paramNik),
                'riwayat_kunjungan' => $service->getRiwayatKunjungan($this->paramNoKartu),
                'rujukan' => $service->getRujukan($this->paramNoKunjungan),
                'rujukan_peserta' => $service->getRujukanByNoKartu($this->paramNoKartu),
                'mcu' => $service->getMcu($this->paramNoKunjungan),
                'add_kunjungan' => $service->addKunjungan($this->parseKunjunganBody()),
                'edit_kunjungan' => $service->editKunjungan($this->parseKunjunganBody()),
                'delete_kunjungan' => $service->deleteKunjungan($this->paramNoKunjunganBpjs),
                'antrean_poli' => $service->getRefPoliAntrean($this->paramTanggal ?: now()->format('Y-m-d')),
                'antrean_dokter' => $service->getRefDokterAntrean($this->paramKodePoli, $this->paramTanggal ?: now()->format('Y-m-d')),
                'add_antrean' => $service->addAntrean($this->parseAntreanBody()),
                'update_status_antrean' => $service->updateStatusAntrean($this->parseAntreanBody()),
                'batal_antrean' => $service->batalAntrean($this->parseAntreanBody()),
                'obat_kunjungan' => $service->getObatByKunjungan($this->paramNoKunjungan),
                'add_obat' => $service->addObat($this->parseObatBody()),
                'delete_obat' => $service->deleteObatKunjungan($this->paramKdObatSK, $this->paramNoKunjungan),
                'pendaftaran_no_urut' => $service->getPendaftaranByNoUrut($this->paramNoUrut, $this->paramTglDaftar ?: now()->format('d-m-Y')),
                'pendaftaran_provider' => $service->getPendaftaranProvider($this->paramTglDaftar ?: now()->format('d-m-Y'), (int) $this->paramStart, (int) $this->paramLimit),
                'add_pendaftaran' => $service->addPendaftaran($this->parsePendaftaranBody()),
                'delete_pendaftaran' => $service->deletePendaftaran($this->paramNoKartu, $this->paramTglDaftar ?: now()->format('d-m-Y'), $this->paramNoUrut, $this->paramKodePoli),
                'antrean_poli' => $service->getRefPoliAntrean($this->paramTanggal ?: now()->format('Y-m-d')),
                'antrean_dokter' => $service->getRefDokterAntrean($this->paramKodePoli, $this->paramTanggal ?: now()->format('Y-m-d')),
                default => ['success' => false, 'data' => null, 'metadata' => ['code' => 400, 'message' => 'Endpoint tidak dikenali.'], 'raw' => null],
            };

            $this->responseTime = round((microtime(true) - $startTime) * 1000);
            $this->responseMetaCode = $result['metadata']['code'] ?? null;
            $this->responseMetaMessage = $result['metadata']['message'] ?? null;

            if ($result['success']) {
                $data = $result['data'];

                if (is_array($data)) {
                    // Handle nested structure: { count: N, list: [...] }
                    if (isset($data['list'])) {
                        $this->responseData = $data['list'];
                        $this->totalData = $data['count'] ?? count($data['list']);
                    } elseif (isset($data[0]) && is_array($data[0])) {
                        // Direct array of items
                        $this->responseData = $data;
                        $this->totalData = count($data);
                    } else {
                        // Single object - wrap in array
                        $this->responseData = [$data];
                        $this->totalData = 1;
                    }

                    // Auto detect columns
                    if (!empty($this->responseData)) {
                        $first = $this->responseData[0];
                        if (is_array($first)) {
                            $this->responseColumns = array_keys($first);
                        }
                    }
                } else {
                    $this->responseData = [];
                    $this->totalData = 0;
                }

                $this->lastFetched = now()->format('d M Y H:i:s');
                $this->successMessage = "Berhasil! Ditemukan {$this->totalData} data. ({$this->responseTime}ms)";
                $this->dispatch('alert', ['type' => 'success', 'message' => $this->successMessage]);
            } else {
                $this->errorMessage = 'Gagal: [' . ($result['metadata']['code'] ?? '?') . '] ' . ($result['metadata']['message'] ?? 'Unknown error');
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);

                if ($result['raw']) {
                    $this->rawResponse = is_string($result['raw']) ? $result['raw'] : json_encode($result['raw'], JSON_PRETTY_PRINT);
                }
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Exception: ' . $e->getMessage();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
        }

        $this->isLoading = false;
    }

    /**
     * Navigate pagination (for paginated endpoints)
     */
    public function nextPage()
    {
        $this->paramStart += $this->paramLimit;
        $this->executeEndpoint();
    }

    public function prevPage()
    {
        $this->paramStart = max(0, $this->paramStart - $this->paramLimit);
        $this->executeEndpoint();
    }

    /**
     * Parse body request kunjungan dari JSON textarea
     */
    protected function parseKunjunganBody(): array
    {
        if (empty($this->kunjunganBodyJson)) {
            return [];
        }

        $parsed = json_decode($this->kunjunganBodyJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorMessage = 'Format JSON body tidak valid: ' . json_last_error_msg();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
            return [];
        }

        return $parsed;
    }

    /**
     * Search pasien dari aplikasi
     */
    public function searchPasien()
    {
        if (strlen($this->searchPasienQuery) < 2) {
            $this->foundPasiens = [];
            return;
        }

        $this->foundPasiens = \App\Models\TrxPendaftaran::with(['pasien', 'dokter', 'poli', 'diagnoses'])
            ->whereHas('pasien', function ($q) {
                $q->where('nama_pasien', 'like', '%' . $this->searchPasienQuery . '%')
                  ->orWhere('no_rm', 'like', '%' . $this->searchPasienQuery . '%')
                  ->orWhere('no_penjamin', 'like', '%' . $this->searchPasienQuery . '%');
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nomor_kunjungan' => $p->nomor_kunjungan,
                    'nama_pasien' => $p->pasien->nama_pasien ?? '-',
                    'no_rm' => $p->pasien->no_rm ?? '-',
                    'no_kartu' => $p->pasien->no_penjamin ?? '-',
                    'poli' => $p->poli->nama_poli ?? '-',
                    'dokter' => $p->dokter->nama_dokter ?? '-',
                    'tanggal' => $p->created_at ? $p->created_at->format('d-m-Y') : '-',
                ];
            })
            ->toArray();
    }

    /**
     * Select pasien & auto-fill body JSON dari data kunjungan internal
     */
    public function selectPasien(int $pendaftaranId)
    {
        $pendaftaran = \App\Models\TrxPendaftaran::with(['pasien', 'dokter', 'poli', 'diagnoses', 'antrian'])->find($pendaftaranId);

        if (!$pendaftaran) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Data pendaftaran tidak ditemukan.']);
            return;
        }

        $service = new \App\Modules\Bridging\Services\BpjsPcareService();
        $pasien = $pendaftaran->pasien;

        // Populate basic parameters
        $this->paramNoKartu = $pasien->no_penjamin ?? '';
        $this->paramNik = $pasien->nik ?? '';
        $this->paramNoKunjungan = $pendaftaran->nomor_kunjungan ?? '';
        $this->paramTglDaftar = $pendaftaran->created_at ? $pendaftaran->created_at->format('d-m-Y') : now()->format('d-m-Y');
        $this->paramNoUrut = $pendaftaran->antrian->nomor_antrian ?? '';
        $this->paramKodePoli = $pendaftaran->poli->poli_bpjs_id ?? '';

        // Build Real Payloads via Service
        $this->kunjunganBodyJson = json_encode($service->buildKunjunganBody($pendaftaran), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->pendaftaranBodyJson = json_encode($service->buildPendaftaranBody($pendaftaran), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($pendaftaran->antrian) {
            $this->antreanBodyJson = json_encode($service->buildAntreanBody($pendaftaran->antrian), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Jika sedang buka endpoint update status atau batal, timpa dengan payload yang sesuai
            if ($this->selectedEndpoint === 'update_status_antrean') {
                $this->antreanBodyJson = json_encode($service->buildUpdateStatusAntreanBody($pendaftaran->antrian), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif ($this->selectedEndpoint === 'batal_antrean') {
                $this->antreanBodyJson = json_encode($service->buildBatalAntreanBody($pendaftaran->antrian), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }

        // Fill Tindakan if available
        $tindakan = \App\Models\TrxTindakan::where('nomor_kunjungan', $pendaftaran->nomor_kunjungan)->first();
        if ($tindakan) {
            $this->tindakanBodyJson = json_encode($service->buildTindakanBody($tindakan, 'NOMOR-KUNJUNGAN-BPJS'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $this->generateDefaultTindakanBody();
        }

        $this->foundPasiens = [];
        $this->searchPasienQuery = ($pasien->nama_pasien ?? '') . ' - ' . ($pendaftaran->nomor_kunjungan ?? '');

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Payload data untuk ' . ($pasien->nama_pasien ?? '') . ' berhasil digenerate dari database.']);
    }

    /**
     * Generate default JSON template for kunjungan body
     */
    public function generateDefaultBody()
    {
        $body = [
            'noKunjungan' => null,
            'noKartu' => '0000043678034',
            'tglDaftar' => now()->format('d-m-Y'),
            'kdPoli' => null,
            'keluhan' => 'keluhan',
            'kdSadar' => '01',
            'sistole' => 0,
            'diastole' => 0,
            'beratBadan' => 0,
            'tinggiBadan' => 0,
            'respRate' => 0,
            'heartRate' => 0,
            'lingkarPerut' => 0,
            'kdStatusPulang' => '3',
            'tglPulang' => now()->format('d-m-Y'),
            'kdDokter' => '',
            'kdDiag1' => 'A01.0',
            'kdDiag2' => null,
            'kdDiag3' => null,
            'kdPoliRujukInternal' => null,
            'rujukLanjut' => null,
            'kdTacc' => 0,
            'alasanTacc' => null,
            'anamnesa' => 'anamnesa',
            'alergiMakan' => '00',
            'alergiUdara' => '00',
            'alergiObat' => '00',
            'kdPrognosa' => '01',
            'terapiObat' => '',
            'terapiNonObat' => '',
            'bmhp' => '',
            'suhu' => '36,4',
        ];

        $this->kunjunganBodyJson = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse body request obat dari JSON textarea
     */
    protected function parseObatBody(): array
    {
        if (empty($this->obatBodyJson)) {
            return [];
        }

        $parsed = json_decode($this->obatBodyJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorMessage = 'Format JSON body obat tidak valid: ' . json_last_error_msg();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
            return [];
        }

        return $parsed;
    }

    /**
     * Generate default JSON template for obat body
     */
    public function generateDefaultObatBody()
    {
        $body = [
            'kdObatSK' => 0,
            'noKunjungan' => '0114U1630316Y000001',
            'racikan' => true,
            'kdRacikan' => null,
            'obatDPHO' => true,
            'kdObat' => '130199999',
            'signa1' => 3,
            'signa2' => 1,
            'jmlObat' => 10,
            'jmlPermintaan' => 2,
            'nmObatNonDPHO' => 'racikan 1 obat 1',
        ];

        $this->obatBodyJson = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse body request pendaftaran dari JSON textarea
     */
    protected function parsePendaftaranBody(): array
    {
        if (empty($this->pendaftaranBodyJson)) {
            return [];
        }

        $parsed = json_decode($this->pendaftaranBodyJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorMessage = 'Format JSON body pendaftaran tidak valid: ' . json_last_error_msg();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
            return [];
        }

        return $parsed;
    }

    /**
     * Generate default JSON template for pendaftaran body
     */
    public function generateDefaultPendaftaranBody()
    {
        $body = [
            'kdProviderPeserta' => '0114A026',
            'tglDaftar' => now()->format('d-m-Y'),
            'noKartu' => '0001113569638',
            'kdPoli' => '001',
            'keluhan' => null,
            'kunjSakit' => true,
            'sistole' => 0,
            'diastole' => 0,
            'beratBadan' => 0,
            'tinggiBadan' => 0,
            'respRate' => 0,
            'lingkarPerut' => 0,
            'heartRate' => 0,
            'rujukBalik' => 0,
            'kdTkp' => '10',
        ];

        $this->pendaftaranBodyJson = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse body request tindakan dari JSON textarea
     */
    protected function parseTindakanBody(): array
    {
        if (empty($this->tindakanBodyJson)) {
            return [];
        }

        $parsed = json_decode($this->tindakanBodyJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorMessage = 'Format JSON body tindakan tidak valid: ' . json_last_error_msg();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
            return [];
        }

        return $parsed;
    }

    /**
     * Generate default JSON template for tindakan body
     */
    public function generateDefaultTindakanBody()
    {
        $body = [
            'kdTindakanSK' => 0,
            'noKunjungan' => '1301U0070815Y000004',
            'kdTindakan' => '01007',
            'biaya' => 0,
            'keterangan' => null,
            'hasil' => 0,
        ];

        $this->tindakanBodyJson = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse body request antrean dari JSON textarea
     */
    protected function parseAntreanBody(): array
    {
        if (empty($this->antreanBodyJson)) {
            return [];
        }

        $parsed = json_decode($this->antreanBodyJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorMessage = 'Format JSON body antrean tidak valid: ' . json_last_error_msg();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
            return [];
        }

        return $parsed;
    }

    /**
     * Generate default JSON template for antrean body
     */
    public function generateDefaultAntreanBody()
    {
        $endpoint = $this->selectedEndpoint;
        $service = new \App\Modules\Bridging\Services\BpjsPcareService();

        // Cari antrian jika ada pasien yang sedang terpilih
        $antrian = null;
        if (!empty($this->searchPasienQuery)) {
            // Coba ambil ID pendaftaran dari string pencarian (format: "Nama - Nomor Kunjungan")
            $parts = explode(' - ', $this->searchPasienQuery);
            $noKunjungan = end($parts);
            $pendaftaran = \App\Models\TrxPendaftaran::with('antrian')->where('nomor_kunjungan', $noKunjungan)->first();
            $antrian = $pendaftaran->antrian ?? null;
        }

        if ($endpoint === 'add_antrean') {
            $body = $antrian ? $service->buildAntreanBody($antrian) : [
                'nomorkartu' => '0001234567890',
                'nik' => '3201234567890001',
                'nohp' => '081234567890',
                'kodepoli' => 'ANA',
                'namapoli' => 'Poli Anak',
                'norm' => 'RM001',
                'tanggalperiksa' => now()->format('Y-m-d'),
                'kodedokter' => 12345,
                'namadokter' => 'Dr. Budi',
                'jampraktek' => '08:00-12:00',
                'nomorantrean' => 'A-1',
                'angkaantrean' => 1,
                'keterangan' => 'Harap datang 15 menit sebelum jam praktek'
            ];
        } elseif ($endpoint === 'update_status_antrean') {
            $body = $antrian ? $service->buildUpdateStatusAntreanBody($antrian) : [
                'tanggalperiksa' => now()->format('Y-m-d'),
                'kodepoli' => '001',
                'nomorkartu' => '0001234567890',
                'status' => 1,
                'waktu' => (int)(microtime(true) * 1000)
            ];
        } elseif ($endpoint === 'batal_antrean') {
            $body = $antrian ? $service->buildBatalAntreanBody($antrian) : [
                'tanggalperiksa' => now()->format('Y-m-d'),
                'kodepoli' => '001',
                'nomorkartu' => '0001234567890',
                'alasan' => 'Terjadi perubahan jadwal dokter'
            ];
        } else {
            $body = [];
        }

        $this->antreanBodyJson = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function render()
    {
        return view('bridging::livewire.dokumentasi-api-page');
    }
}
