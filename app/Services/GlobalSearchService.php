<?php

namespace App\Services;

use App\Models\MstPasien;
use App\Models\MstDokter;
use App\Models\TrxPendaftaran;
use App\Models\MstMenu;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * Maximum results per category.
     */
    protected int $limit = 5;

    /**
     * Perform a global search across all searchable entities.
     *
     * @param string $query The search term (min 2 characters).
     * @return array<string, Collection> Grouped results by category.
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        return [
            'pasien'      => $this->searchPasien($query),
            'kunjungan'   => $this->searchKunjungan($query),
            'dokter'      => $this->searchDokter($query),
            'menu'        => $this->searchMenu($query),
        ];
    }

    /**
     * Search patients by name, RM number, or NIK.
     */
    protected function searchPasien(string $query): Collection
    {
        return MstPasien::query()
            ->where(function ($q) use ($query) {
                $q->where('nama_pasien', 'like', "%{$query}%")
                  ->orWhere('no_rm', 'like', "%{$query}%")
                  ->orWhere('nik', 'like', "%{$query}%")
                  ->orWhere('no_telepon', 'like', "%{$query}%");
            })
            ->select('id', 'no_rm', 'nama_pasien', 'nik', 'no_telepon', 'jenis_kelamin')
            ->orderBy('nama_pasien')
            ->limit($this->limit)
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'title'       => $p->nama_pasien,
                'subtitle'    => "RM: {$p->no_rm}" . ($p->nik ? " · NIK: {$p->nik}" : ''),
                'icon'        => 'ri-user-heart-line',
                'icon_bg'     => 'bg-primary-soft',
                'url'         => route('master.pasien', ['search' => $p->nama_pasien]),
                'meta'        => $p->jenis_kelamin ?: '-',
            ]);
    }

    /**
     * Search visits/registrations by visit number, patient name, or doctor name.
     */
    protected function searchKunjungan(string $query): Collection
    {
        return TrxPendaftaran::query()
            ->with(['pasien:id,nama_pasien,no_rm', 'dokter:id,nama_dokter', 'poli:id,nama_poli'])
            ->where(function ($q) use ($query) {
                $q->where('nomor_kunjungan', 'like', "%{$query}%")
                  ->orWhereHas('pasien', fn ($sub) =>
                      $sub->where('nama_pasien', 'like', "%{$query}%")
                          ->orWhere('no_rm', 'like', "%{$query}%")
                  );
            })
            ->orderByDesc('created_at')
            ->limit($this->limit)
            ->get()
            ->map(function ($k) {
                $visitDate = $k->created_at ? $k->created_at->format('Y-m-d') : now()->format('Y-m-d');
                $patientName = $k->pasien?->nama_pasien ?? '';

                return [
                    'id'          => $k->id,
                    'title'       => $patientName ?: '-',
                    'subtitle'    => "No. Kunjungan: {$k->nomor_kunjungan}",
                    'icon'        => 'ri-stethoscope-line',
                    'icon_bg'     => 'bg-success-soft',
                    'url'         => route('laporan.kunjungan', [
                        'periodType'   => 'DAILY',
                        'selectedDate' => $visitDate,
                        'search'       => $patientName,
                    ]),
                    'meta'        => ($k->dokter?->nama_dokter ?? '-') . ' · ' . ($k->poli?->nama_poli ?? '-'),
                ];
            });
    }

    /**
     * Search doctors by name, code, or specialization.
     */
    protected function searchDokter(string $query): Collection
    {
        return MstDokter::query()
            ->where(function ($q) use ($query) {
                $q->where('nama_dokter', 'like', "%{$query}%")
                  ->orWhere('kode_dokter', 'like', "%{$query}%")
                  ->orWhere('spesialisasi', 'like', "%{$query}%");
            })
            ->select('id', 'kode_dokter', 'nama_dokter', 'spesialisasi')
            ->orderBy('nama_dokter')
            ->limit($this->limit)
            ->get()
            ->map(fn ($d) => [
                'id'          => $d->id,
                'title'       => $d->nama_dokter,
                'subtitle'    => "Kode: {$d->kode_dokter}" . ($d->spesialisasi ? " · {$d->spesialisasi}" : ''),
                'icon'        => 'ri-nurse-line',
                'icon_bg'     => 'bg-info-soft',
                'url'         => route('master.dokter', ['search' => $d->nama_dokter]),
                'meta'        => $d->spesialisasi ?? 'Dokter Umum',
            ]);
    }

    /**
     * Search application menus/navigation by name.
     */
    protected function searchMenu(string $query): Collection
    {
        return MstMenu::query()
            ->where('is_active', true)
            ->where('menu_name', 'like', "%{$query}%")
            ->select('id', 'menu_name', 'menu_link', 'menu_icon')
            ->orderBy('order_no')
            ->limit($this->limit)
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'title'       => $m->menu_name,
                'subtitle'    => 'Navigasi Menu',
                'icon'        => $m->menu_icon ?: 'ri-menu-line',
                'icon_bg'     => 'bg-warning-soft',
                'url'         => $m->menu_link ?: '#',
                'meta'        => 'Menu',
            ]);
    }
}
