<?php

namespace App\Filament\Widgets\Concerns;

use App\Enums\VonageProduct;
use App\Models\VonageReportRecord;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Applies the dashboard's period and product filters to report record queries.
 */
trait QueriesFilteredReportRecords
{
    use InteractsWithPageFilters;

    /**
     * Fixed categorical colour per product, so a series keeps its colour when filtered.
     *
     * @var array<string, string>
     */
    protected const PRODUCT_COLORS = [
        'SMS' => '#2a78d6',
        'MESSAGES' => '#eb6834',
        'VOICE-CALL' => '#1baf7a',
    ];

    protected function periodDays(): int
    {
        return (int) ($this->pageFilters['period'] ?? 30);
    }

    protected function periodStart(): Carbon
    {
        return now()->subDays($this->periodDays() - 1)->startOfDay();
    }

    protected function selectedProduct(): ?VonageProduct
    {
        return VonageProduct::tryFrom((string) ($this->pageFilters['product'] ?? ''));
    }

    /**
     * @return Builder<VonageReportRecord>
     */
    protected function filteredQuery(): Builder
    {
        return VonageReportRecord::query()
            ->where('occurred_at', '>=', $this->periodStart())
            ->when($this->selectedProduct(), fn (Builder $query, VonageProduct $product): Builder => $query->where('product', $product));
    }

    /**
     * @return list<string>
     */
    protected function periodDates(): array
    {
        return collect(range(0, $this->periodDays() - 1))
            ->map(fn (int $offset): string => $this->periodStart()->addDays($offset)->toDateString())
            ->all();
    }
}
