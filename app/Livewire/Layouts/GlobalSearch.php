<?php

namespace App\Livewire\Layouts;

use Livewire\Component;
use App\Services\GlobalSearchService;

class GlobalSearch extends Component
{
    /**
     * The search query string, bound with debounce from the view.
     */
    public string $query = '';

    /**
     * Grouped search results.
     *
     * @var array<string, \Illuminate\Support\Collection>
     */
    public array $results = [];

    /**
     * Whether the dropdown is currently visible.
     */
    public bool $showResults = false;

    /**
     * Total count of all results across categories.
     */
    public int $totalResults = 0;

    /**
     * Category labels for display.
     */
    protected array $categoryLabels = [
        'pasien'    => 'Pasien',
        'kunjungan' => 'Kunjungan',
        'dokter'    => 'Dokter',
        'menu'      => 'Menu Navigasi',
    ];

    /**
     * React to changes in the query string.
     */
    public function updatedQuery(): void
    {
        $this->performSearch();
    }

    /**
     * Execute the search via the service.
     */
    protected function performSearch(): void
    {
        if (mb_strlen(trim($this->query)) < 2) {
            $this->results = [];
            $this->showResults = false;
            $this->totalResults = 0;
            return;
        }

        $service = app(GlobalSearchService::class);
        $rawResults = $service->search($this->query);

        // Filter out empty categories and convert collections to arrays for Livewire serialization
        $this->results = collect($rawResults)
            ->filter(fn ($items) => $items->isNotEmpty())
            ->map(fn ($items) => $items->toArray())
            ->toArray();

        $this->totalResults = collect($this->results)->flatten(1)->count();
        $this->showResults = $this->totalResults > 0;
    }

    /**
     * Clear the search and close the dropdown.
     */
    public function clearSearch(): void
    {
        $this->query = '';
        $this->results = [];
        $this->showResults = false;
        $this->totalResults = 0;
    }

    /**
     * Get the human-readable label for a category key.
     */
    public function getCategoryLabel(string $key): string
    {
        return $this->categoryLabels[$key] ?? ucfirst($key);
    }

    public function render()
    {
        return view('livewire.layouts.global-search');
    }
}
