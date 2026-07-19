<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AuditLogs\Tables;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogsTable
{
    /**
     * @var array<string, string>
     */
    protected static array $events = [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $subjects = [
        'App\\Models\\User' => 'Utilisateur',
        'App\\Models\\TeamMember' => 'Membre d’équipe',
        'App\\Models\\Invitation' => 'Invitation',
        'App\\Models\\Donor' => 'Bailleur',
        'App\\Models\\Sector' => 'Secteur',
        'App\\Models\\Tag' => 'Étiquette',
        'App\\Models\\Locality' => 'Localité',
        'App\\Models\\Organization' => 'Organisation',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('causer')
                    ->label('Auteur')
                    ->getStateUsing(fn (ActivityLog $record): string => self::causerName($record))
                    ->placeholder('Système'),
                TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::$events[$state] ?? (string) $state),
                TextColumn::make('subject_type')
                    ->label('Objet')
                    ->formatStateUsing(fn (?string $state): string => self::$subjects[(string) $state] ?? class_basename((string) $state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([]);
    }

    protected static function causerName(ActivityLog $record): string
    {
        $causer = $record->causer;

        if ($causer !== null && method_exists($causer, 'getFilamentName')) {
            return (string) $causer->getFilamentName();
        }

        return 'Système';
    }
}
