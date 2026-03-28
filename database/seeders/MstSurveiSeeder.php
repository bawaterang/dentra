<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MstSurvei;

class MstSurveiSeeder extends Seeder
{
    public function run(): void
    {
        $pertanyaan = [
            'Apakah anda mempunyai riwayat alergi obat?',
            'Apakah anda mempunyai riwayat penyakit jantung?',
            'Apakah anda mempunyai riwayat penyakit diabetes?',
            'Apakah anda mempunyai riwayat penyakit saraf?',
            'Apakah anda mempunyai riwayat penyakit paru?',
            'Apakah anda mempunyai riwayat penyakit hipertensi?',
            'Apakah anda sedang demam / tidak enak badan?',
            'Apakah anda sedang pusing / sakit kepala?',
            'Apakah anda sedang batuk?',
            'Apakah anda sedang pilek / flu?',
            'Apakah anda sedang sesak nafas?',
            'Apakah anda mempunyai riwayat penyakit asma?',
            'Apakah anda mempunyai riwayat penyakit maag?',
            'Apakah anda sudah vaksin Covid-19?',
            'Apakah anda sedang konsumsi obat saat ini?',
        ];

        foreach ($pertanyaan as $p) {
            MstSurvei::firstOrCreate(
                ['pertanyaan' => $p],
                ['jenis_survei' => 'screening', 'status' => 'Aktif']
            );
        }
    }
}
