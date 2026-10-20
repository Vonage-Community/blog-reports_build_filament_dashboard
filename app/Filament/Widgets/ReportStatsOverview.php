<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\QueriesFilteredReportRecords;
use App\Models\VonageReportRecord;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ReportStatsOverview extends StatsOverviewWidget
{
    use QueriesFilteredReportRecords;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totals = $this->filteredQuery()
            ->toBase()
            ->selectRaw('count(*) as records')
            ->selectRaw('coalesce(sum(total_price), 0) as spend')
            ->selectRaw('sum(case when status in ('.$this->placeholders(VonageReportRecord::SUCCESSFUL_STATUSES).') then 1 else 0 end) as successful', VonageReportRecord::SUCCESSFUL_STATUSES)
            ->selectRaw('sum(case when status in ('.$this->placeholders(VonageReportRecord::FAILED_STATUSES).') then 1 else 0 end) as failed', VonageReportRecord::FAILED_STATUSES)
            ->selectRaw('avg(latency_ms) as latency')
            ->selectRaw('coalesce(sum(duration_seconds), 0) as duration')
            ->first();

        $daily = $this->filteredQuery()
            ->toBase()
            ->selectRaw('date(occurred_at) as day, count(*) as records, coalesce(sum(total_price), 0) as spend')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = fn (string $metric): array => collect($this->periodDates())
            ->map(fn (string $date): float => (float) ($daily[$date]->{$metric} ?? 0))
            ->all();

        $records = (int) $totals->records;
        $successRate = $records > 0 ? $totals->successful / $records * 100 : 0;
        $failureRate = $records > 0 ? $totals->failed / $records * 100 : 0;

        return [
            Stat::make('Records', Number::format($records))
                ->description("Last {$this->periodDays()} days")
                ->chart($series('records'))
                ->color('primary'),
            Stat::make('Spend', Number::currency((float) $totals->spend, 'EUR'))
                ->description('Total billed across products')
                ->chart($series('spend'))
                ->color('primary'),
            Stat::make('Success rate', Number::percentage($successRate, 1))
                ->description(Number::percentage($failureRate, 1).' failed')
                ->descriptionIcon($failureRate > 10 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($failureRate > 10 ? 'danger' : 'success'),
            Stat::make('Avg latency', $totals->latency !== null ? Number::format($totals->latency / 1000, 2).' s' : '—')
                ->description(Number::format($totals->duration / 60, 0).' voice minutes'),
        ];
    }

    /**
     * @param  list<string>  $values
     */
    private function placeholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }
}
