<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tasks\Schemas;

use App\Enums\TaskPriority;
use App\Enums\TaskRecurrence;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Fieldset::make('Rattachement')
                        ->columns(2)
                        ->schema([
                            Select::make('project_id')
                                ->label('Projet')
                                ->options(fn (): array => Project::query()->orderBy('title')->pluck('title', 'id')->all())
                                ->searchable()
                                ->live()
                                ->helperText('Laisser vide pour une tâche interne (hors projet).'),
                            Select::make('activity_id')
                                ->label('Activité')
                                ->options(fn (Get $get): array => blank($get('project_id')) ? [] : Activity::query()->where('project_id', $get('project_id'))->orderBy('planned_start')->pluck('title', 'id')->all())
                                ->searchable(),
                        ]),

                    Fieldset::make('Détails')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titre')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Textarea::make('description')
                                ->label('Description')
                                ->rows(3)
                                ->columnSpanFull(),
                            Select::make('priority')
                                ->label('Priorité')
                                ->options(TaskPriority::class)
                                ->default(TaskPriority::Normale)
                                ->required(),
                            Select::make('status')
                                ->label('Statut')
                                ->options(TaskStatus::class)
                                ->default(TaskStatus::AFaire)
                                ->required(),
                        ]),

                    Fieldset::make('Assignation et échéance')
                        ->columns(2)
                        ->schema([
                            Select::make('assignee_user_id')
                                ->label('Assigné (compte)')
                                ->options(fn (): array => User::query()->get()->mapWithKeys(fn (User $u): array => [$u->id => $u->getFilamentName()])->all())
                                ->searchable(),
                            Select::make('assignee_team_member_id')
                                ->label('Assigné (membre sans compte)')
                                ->options(fn (): array => TeamMember::query()->whereNull('user_id')->orderBy('full_name')->pluck('full_name', 'id')->all())
                                ->searchable(),
                            DatePicker::make('due_date')
                                ->label('Échéance')
                                ->native(false),
                        ]),

                    Fieldset::make('Étiquettes')
                        ->schema([
                            Select::make('tags')
                                ->label('Étiquettes')
                                ->relationship('tags', 'name')
                                ->multiple()
                                ->preload()
                                ->columnSpanFull(),
                        ]),

                    Fieldset::make('Récurrence')
                        ->columns(2)
                        ->schema([
                            Select::make('recurrence')
                                ->label('Fréquence')
                                ->options(TaskRecurrence::class)
                                ->default(TaskRecurrence::Aucune)
                                ->live(),
                            TextInput::make('reminder_days_before')
                                ->label('Rappel (jours avant échéance)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(90)
                                ->visible(fn (Get $get): bool => $get('recurrence') !== null && $get('recurrence') !== TaskRecurrence::Aucune->value),
                        ]),

                    Fieldset::make('Pièces jointes')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('pieces_jointes')
                                ->label('Documents')
                                ->collection('pieces_jointes')
                                ->multiple()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                ->maxSize(10240)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
