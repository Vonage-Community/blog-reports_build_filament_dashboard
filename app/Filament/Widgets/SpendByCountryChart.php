<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\QueriesFilteredReportRecords;
use Filament\Widgets\ChartWidget;

class SpendByCountryChart extends ChartWidget
{
    use QueriesFilteredReportRecords;

    protected ?string $heading = 'Spend by country';

    protected ?string $description = 'Top 8 destinations, EUR';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $spend = $this->filteredQuery()
            ->toBase()
            ->whereNotNull('country_name')
            ->selectRaw('country_name, sum(total_price) as spend')
            ->groupBy('country_name')
            ->orderByDesc('spend')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Spend (EUR)',
                    'data' => $spend->map(fn (object $row): float => round((float) $row->spend, 2))->all(),
                    'backgroundColor' => '#2a78d6',
                    'borderRadius' => 4,
                    'barThickness' => 14,
                ],
            ],
            'labels' => $spend->pluck('country_name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
