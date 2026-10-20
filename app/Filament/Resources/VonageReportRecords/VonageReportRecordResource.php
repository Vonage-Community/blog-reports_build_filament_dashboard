<?php

namespace App\Filament\Resources\VonageReportRecords;

use App\Filament\Resources\VonageReportRecords\Pages\ListVonageReportRecords;
use App\Filament\Resources\VonageReportRecords\Pages\ViewVonageReportRecord;
use App\Filament\Resources\VonageReportRecords\Schemas\VonageReportRecordInfolist;
use App\Filament\Resources\VonageReportRecords\Tables\VonageReportRecordsTable;
use App\Models\VonageReportRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VonageReportRecordResource extends Resource
{
    protected static ?string $model = VonageReportRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Report Records';

    protected static ?string $modelLabel = 'report record';

    protected static ?string $recordTitleAttribute = 'external_id';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return VonageReportRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VonageReportRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVonageReportRecords::route('/'),
            'view' => ViewVonageReportRecord::route('/{record}'),
        ];
    }
}
