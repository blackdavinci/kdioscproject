<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\RelationManagers;

use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Équipe';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Compte utilisateur')
                ->options(fn (): array => User::query()->orderBy('email')->get()->mapWithKeys(fn (User $u): array => [$u->id => $u->getFilamentName()])->all())
                ->searchable()
                ->helperText('Ou choisissez un membre sans compte ci-dessous.'),
            Select::make('team_member_id')
                ->label('Membre sans compte')
                ->options(fn (): array => TeamMember::query()->whereNull('user_id')->orderBy('full_name')->pluck('full_name', 'id')->all())
                ->searchable(),
            Select::make('project_role_id')
                ->label('Rôle dans le projet')
                ->options(fn (): array => ProjectRole::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->createOptionForm([
                    TextInput::make('name')->label('Nom du rôle')->required()->maxLength(255),
                ])
                ->createOptionUsing(fn (array $data): string => ProjectRole::create(['name' => $data['name']])->getKey()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('member')
                    ->label('Membre')
                    ->state(fn (ProjectMember $record): string => $record->displayName()),
                TextColumn::make('projectRole.name')
                    ->label('Rôle projet')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('user_id')
                    ->label('Type')
                    ->formatStateUsing(fn ($state): string => $state ? 'Compte' : 'Sans compte')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un membre'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
