<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AuditLogs;

use App\Filament\App\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\App\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Journal d'audit de l'organisation (RG-26, §5-6). Lecture seule, réservé à l'admin,
 * scopé aux activités de l'organisation courante.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'entrée d’audit';

    protected static ?string $pluralModelLabel = 'journal d’audit';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('admin');
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('organization_id', $tenant instanceof Organization ? $tenant->getKey() : null);
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
