<?php

namespace App\Filament\Widgets;

use App\Enums\VonageProduct;
use App\Filament\Widgets\Concerns\QueriesFilteredReportRecords;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class MessagesChannelChart extends ChartWidget
{
    use QueriesFilteredReportRecords;

    protected ?string $heading = 'Messages API by channel';

    protected ?string $description = 'Outbound and inbound records';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $channels = $this->filteredQuery()
            ->toBase()
            ->where('product', VonageProduct::Messages->value)
            ->whereNotNull('channel')
            ->selectRaw('channel, count(*) as total')
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Records',
                    'data' => $channels->pluck('total')->map(fn (mixed $total): int => (int) $total)->all(),
                    'backgroundColor' => self::PRODUCT_COLORS[VonageProduct::Messages->value],
                    'borderRadius' => 4,
                    'barThickness' => 14,
                ],
            ],
            'labels' => $channels->map(fn (object $row): string => Str::headline($row->channel))->all(),
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
