<?php

namespace App\Filament\Widgets;

use App\Enums\VonageProduct;
use App\Filament\Resources\VonageReportRecords\VonageReportRecordResource;
use App\Filament\Widgets\Concerns\QueriesFilteredReportRecords;
use App\Models\VonageReportRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentFailuresTable extends TableWidget
{
    use QueriesFilteredReportRecords;

    protected static ?string $heading = 'Recent failures';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->filteredQuery()->failed()->latest('occurred_at'))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25])
            ->recordUrl(fn (VonageReportRecord $record): string => VonageReportRecordResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('occurred_at')->label('Date')->dateTime(),
                TextColumn::make('product')
                    ->formatStateUsing(fn (VonageProduct $state): string => $state->label())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('to'),
                TextColumn::make('country_name')->label('Country'),
                TextColumn::make('status')->badge()->color('danger'),
                TextColumn::make('error_description')
                    ->label('Error')
                    ->formatStateUsing(fn (VonageReportRecord $record): string => trim("{$record->error_code} {$record->error_description}"))
                    ->placeholder('—'),
            ]);
    }
}
