<?php

namespace App\Modules\Master\Http\Livewire;

use Livewire\Component;

class AsuransiPage extends Component
{
    public $items = [['A001', 'BPJS Kesehatan', 'Pemerintah', '0%', 'Aktif'], ['A002', 'Prudential', 'Swasta', '10%', 'Aktif'], ['A003', 'Allianz', 'Swasta', '5%', 'Tidak Aktif']];

    public function render()
    {
        return <<<'HTML'
        <div x-data x-init="
            $nextTick(() => {
                const tableId = '#asuransiTable';
                if ($.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().destroy();
                }
                const table = $(tableId).DataTable({
                    scrollX: true,
                    dom: 'lrtip',
                    language: {
                        lengthMenu: '_MENU_',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                        paginate: {
                            previous: '<i class=ri-arrow-left-s-line></i>',
                            next: '<i class=ri-arrow-right-s-line></i>'
                        }
                    }
                });
                $('#customSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });
            })
        ">
            <!-- Page Header -->
            <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
                <div>
                    <h4 class="mb-1 text-lg font-bold text-[#495057] uppercase tracking-wider">Asuransi</h4>
                    <nav class="flex text-sm text-[#878a99]" aria-label="Breadcrumb">
                        <ol class="inline-flex list-none items-center p-0">
                            <li class="flex items-center">
                                <a href="/dashboard" wire:navigate class="hover:text-[#405189]">Master</a>
                                <i class="ri-arrow-right-s-line mx-2"></i>
                            </li>
                            <li class="text-[#495057] font-semibold">Data Asuransi</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Summary Widgets -->
            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                
                <div class="card">
                    <div class="flex items-center p-6 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-subtle text-info">
                            <i class="ri-shield-check-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-sm">Total Rekan</p>
                            <h4 class="mb-0 font-bold text-xl text-[#495057]">15</h4>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="flex items-center p-6 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-subtle text-primary">
                            <i class="ri-government-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-sm">Pemerintah</p>
                            <h4 class="mb-0 font-bold text-xl text-[#495057]">1</h4>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="flex items-center p-6 gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-subtle text-success">
                            <i class="ri-building-line text-xl"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-[#878a99] font-medium text-sm">Swasta</p>
                            <h4 class="mb-0 font-bold text-xl text-[#495057]">14</h4>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Action Button Area -->
            <div class="mb-3 flex flex-col sm:flex-row justify-end">
                <button class="btn btn-primary w-full sm:w-auto h-10 sm:h-9 px-4 sm:px-6 shadow-sm flex items-center justify-center sm:justify-start gap-2 transition-all hover:translate-y-[-2px]">
                    <i class="ri-add-line text-lg sm:text-base"></i>
                    <span class="font-semibold text-sm sm:text-sm">Tambah Asuransi</span>
                </button>
            </div>

            <div class="card overflow-hidden">
                <div class="flex flex-col border-b border-[#eff2f7]">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-4 pt-5 pb-5 px-5">
                        <div class="flex flex-row items-center gap-2 w-full sm:w-auto">
                            <div class="relative flex-grow">
                                <input type="text" id="customSearch" class="h-9 w-full sm:w-60 rounded bg-[#f3f6f9] border border-[#f3f6f9] pl-9 pr-3 text-sm outline-none focus:border-[#405189] transition-all" placeholder="Search...">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-[#878a99]"></i>
                            </div>
                            <button class="btn btn-info flex h-9 w-10 sm:w-auto items-center justify-center p-0 sm:px-3" title="Filters">
                                <i class="ri-filter-3-line"></i>
                                <span class="ml-1 hidden sm:inline">Filters</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-5 pb-4">
                        <ul class="nav-pills-custom">
                            <li class="nav-item">
                                <a class="nav-link active active-pill-primary">
                                    <i class="ri-database-2-line"></i>
                                    <span>Semua Data</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active-pill-success">
                                    <i class="ri-checkbox-circle-line"></i>
                                    <span>Aktif</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active-pill-danger">
                                    <i class="ri-close-circle-line"></i>
                                    <span>Tidak Aktif</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card-body p-6">
                    <table id="asuransiTable" class="display w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Asuransi</th>
                                <th>Tipe</th>
                                <th>Diskon</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                @foreach($item as $key => $val)
                                    @if(is_array($item) && array_key_last($item) == $key)
                                        <td>
                                            @php
                                                $badgeClass = 'bg-info-subtle';
                                                if(in_array($val, ['Aktif', 'Normal', 'Success'])) $badgeClass = 'bg-success-subtle';
                                                if(in_array($val, ['Tidak Aktif', 'Non-Aktif', 'Cuti', 'Stok Habis', 'Failed', 'Karies'])) $badgeClass = 'bg-danger-subtle';
                                                if(in_array($val, ['Stok Rendah', 'Pending', 'Impacted'])) $badgeClass = 'bg-warning-subtle';
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $val }}</span>
                                        </td>
                                    @else
                                        <td>{{ $val }}</td>
                                    @endif
                                @endforeach
                                <td>
                                    <div class="flex gap-2">
                                        <button class="flex h-7 w-7 items-center justify-center rounded bg-[#405189]/10 text-[#405189] hover:bg-[#405189] hover:text-white transition-all">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="flex h-7 w-7 items-center justify-center rounded bg-[#f06548]/10 text-[#f06548] hover:bg-[#f06548] hover:text-white transition-all">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        HTML;
    }
}
