<?php

namespace App\Filament\Resources\VonageReportRecords\Tables;

use App\Enums\VonageProduct;
use App\Models\VonageReportRecord;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VonageReportRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('product')
                    ->formatStateUsing(fn (VonageProduct $state): string => $state->label())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('channel')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('direction')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'inbound' ? 'info' : 'primary'),
                TextColumn::make('from')
                    ->searchable(),
                TextColumn::make('to')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => VonageReportRecord::statusColor($state)),
                TextColumn::make('country_name')
                    ->label('Country')
                    ->sortable(),
                TextColumn::make('network_name')
                    ->label('Network')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_price')
                    ->label('Price')
                    ->money(fn (VonageReportRecord $record): string => $record->currency ?? 'EUR', decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('latency_ms')
                    ->label('Latency')
                    ->suffix(' ms')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->suffix(' s')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('external_id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product')
                    ->options(VonageProduct::options()),
                SelectFilter::make('direction')
                    ->options(['outbound' => 'Outbound', 'inbound' => 'Inbound']),
                SelectFilter::make('status')
                    ->multiple()
                    ->options(fn (): array => VonageReportRecord::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->orderBy('status')
                        ->pluck('status', 'status')
                        ->all()),
                SelectFilter::make('country')
                    ->options(fn (): array => VonageReportRecord::query()
                        ->whereNotNull('country')
                        ->distinct()
                        ->orderBy('country_name')
                        ->pluck('country_name', 'country')
                        ->all()),
                Filter::make('occurred_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
