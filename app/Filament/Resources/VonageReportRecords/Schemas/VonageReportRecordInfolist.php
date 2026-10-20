<?php

namespace App\Filament\Resources\VonageReportRecords\Schemas;

use App\Enums\VonageProduct;
use App\Models\VonageReportRecord;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VonageReportRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Record')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('product')
                            ->formatStateUsing(fn (VonageProduct $state): string => $state->label())
                            ->badge(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => VonageReportRecord::statusColor($state)),
                        TextEntry::make('direction'),
                        TextEntry::make('channel')->placeholder('—'),
                        TextEntry::make('from'),
                        TextEntry::make('to'),
                        TextEntry::make('external_id')->label('ID')->copyable()->columnSpanFull(),
                        TextEntry::make('account_id')->label('Account'),
                    ]),
                Section::make('Delivery')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('occurred_at')->label('Received / started')->dateTime(),
                        TextEntry::make('finalized_at')->label('Finalized / ended')->dateTime()->placeholder('—'),
                        TextEntry::make('country_name')->label('Country'),
                        TextEntry::make('network_name')->label('Network')->placeholder('—'),
                        TextEntry::make('total_price')
                            ->label('Price')
                            ->money(fn (VonageReportRecord $record): string => $record->currency ?? 'EUR', decimalPlaces: 4),
                        TextEntry::make('latency_ms')->label('Latency')->suffix(' ms')->placeholder('—'),
                        TextEntry::make('duration_seconds')->label('Duration')->suffix(' s')->placeholder('—'),
                        TextEntry::make('error_description')
                            ->label('Error')
                            ->formatStateUsing(fn (VonageReportRecord $record): string => trim("{$record->error_code} {$record->error_description}"))
                            ->placeholder('—'),
                    ]),
                Section::make('Raw API payload')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        KeyValueEntry::make('payload')
                            ->hiddenLabel()
                            ->state(fn (VonageReportRecord $record): array => collect($record->payload)
                                ->map(fn (mixed $value): string => is_scalar($value) ? (string) $value : json_encode($value))
                                ->all()),
                    ]),
            ]);
    }
}
