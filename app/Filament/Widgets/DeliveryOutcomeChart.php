<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\QueriesFilteredReportRecords;
use Filament\Widgets\ChartWidget;

class DeliveryOutcomeChart extends ChartWidget
{
    use QueriesFilteredReportRecords;

    protected ?string $heading = 'Delivery outcome';

    protected ?string $description = 'Share of records by final status';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $successful = $this->filteredQuery()->successful()->count();
        $failed = $this->filteredQuery()->failed()->count();
        $pending = $this->filteredQuery()->count() - $successful - $failed;

        return [
            'datasets' => [
                [
                    'data' => [$successful, $pending, $failed],
                    'backgroundColor' => ['#008300', '#eda100', '#e34948'],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Delivered / completed', 'Pending / other', 'Failed'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '65%',
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
