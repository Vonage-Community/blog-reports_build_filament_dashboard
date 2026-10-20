<?php

namespace App\Filament\Pages;

use App\Enums\VonageProduct;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Vonage Reports';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('period')
                        ->options([
                            7 => 'Last 7 days',
                            14 => 'Last 14 days',
                            30 => 'Last 30 days',
                            90 => 'Last 90 days',
                        ])
                        ->default(30)
                        ->selectablePlaceholder(false),
                    Select::make('product')
                        ->options(VonageProduct::options())
                        ->placeholder('All products'),
                ]),
        ]);
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
