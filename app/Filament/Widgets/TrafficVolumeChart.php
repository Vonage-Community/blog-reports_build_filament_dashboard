<?php

namespace App\Filament\Widgets;

use App\Enums\VonageProduct;
use App\Filament\Widgets\Concerns\QueriesFilteredReportRecords;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TrafficVolumeChart extends ChartWidget
{
    use QueriesFilteredReportRecords;

    protected ?string $heading = 'Traffic volume';

    protected ?string $description = 'Records per day, by product';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $counts = $this->filteredQuery()
            ->toBase()
            ->selectRaw('date(occurred_at) as day, product, count(*) as total')
            ->groupBy('day', 'product')
            ->get()
            ->groupBy('product');

        $dates = $this->periodDates();
        $products = $this->selectedProduct() ? [$this->selectedProduct()] : VonageProduct::cases();

        return [
            'datasets' => collect($products)->map(function (VonageProduct $product) use ($counts, $dates): array {
                $byDay = ($counts[$product->value] ?? collect())->pluck('total', 'day');

                return [
                    'label' => $product->label(),
                    'data' => array_map(fn (string $date): int => (int) ($byDay[$date] ?? 0), $dates),
                    'borderColor' => self::PRODUCT_COLORS[$product->value],
                    'backgroundColor' => self::PRODUCT_COLORS[$product->value],
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                    'tension' => 0.3,
                ];
            })->all(),
            'labels' => array_map(fn (string $date): string => Carbon::parse($date)->format('M j'), $dates),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
