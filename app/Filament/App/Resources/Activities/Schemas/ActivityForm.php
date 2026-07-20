<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Schemas;

use App\Enums\DisaggregationPhase;
use App\Enums\LogframeNodeType;
use App\Filament\App\Resources\Activities\Support\ActivityDisaggregation;
use App\Models\GeoUnit;
use App\Models\Locality;
use App\Models\LogframeNode;
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

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Planification')
                ->columnSpanFull()
                ->schema([
                    Fieldset::make('Rattachement au cadre logique')
                        ->columns(2)
                        ->schema([
                            Select::make('project_id')
                                ->label('Projet')
                                ->options(fn (): array => Project::query()->orderBy('title')->pluck('title', 'id')->all())
                                ->searchable()
                                ->required()
                                ->live(),
                            Select::make('logframe_node_id')
                                ->label('Activité du cadre logique')
                                ->options(fn (Get $get): array => self::activityNodes($get('project_id')))
                                ->searchable()
                                ->required(),
                            TextInput::make('title')
                                ->label('Intitulé de l’activité')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),

                    Fieldset::make('Dates')
                        ->columns(2)
                        ->schema([
                            DatePicker::make('planned_start')
                                ->label('Date prévue (début)')
                                ->native(false)
                                ->required(),
                            DatePicker::make('planned_end')
                                ->label('Date prévue (fin)')
                                ->native(false)
                                ->afterOrEqual('planned_start'),
                        ]),

                    Fieldset::make('Lieu')
                        ->columns(2)
                        ->schema([
                            Select::make('geo_unit_id')
                                ->label('Unité administrative')
                                ->getSearchResultsUsing(fn (string $search): array => GeoUnit::query()->where('active', true)->where('name', 'ilike', "%{$search}%")->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                                ->getOptionLabelUsing(fn ($value): ?string => GeoUnit::whereKey($value)->value('name'))
                                ->searchable(),
                            Select::make('locality_id')
                                ->label('Localité')
                                ->options(fn (): array => Locality::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable(),
                            TextInput::make('latitude')
                                ->label('Latitude')
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90),
                            TextInput::make('longitude')
                                ->label('Longitude')
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180),
                        ]),

                    Fieldset::make('Responsable et ressources')
                        ->columns(2)
                        ->schema([
                            Select::make('responsible_user_id')
                                ->label('Responsable (compte)')
                                ->options(fn (Get $get): array => self::teamUsers($get('project_id')))
                                ->searchable(),
                            Select::make('responsible_team_member_id')
                                ->label('Responsable (membre sans compte)')
                                ->options(fn (Get $get): array => self::teamMembers($get('project_id')))
                                ->searchable(),
                            Textarea::make('planned_resources')
                                ->label('Ressources prévues')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),

                    ActivityDisaggregation::fieldset(DisaggregationPhase::Planned, 'Participants prévus'),
                ]),

            Section::make('Réalisation')
                ->columnSpanFull()
                ->description('À renseigner après l’activité (saisie différée possible).')
                ->schema([
                    DatePicker::make('realized_at')
                        ->label('Date effective de réalisation')
                        ->native(false)
                        ->maxDate(now())
                        ->helperText('Date de terrain, distincte de la date de saisie.'),
                    Textarea::make('description')
                        ->label('Déroulé / description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('difficulties')
                        ->label('Difficultés rencontrées')
                        ->rows(2),
                    Textarea::make('corrective_measures')
                        ->label('Mesures correctives')
                        ->rows(2),

                    ActivityDisaggregation::fieldset(DisaggregationPhase::Real, 'Participants réels'),

                    SpatieMediaLibraryFileUpload::make('justificatifs')
                        ->label('Justificatifs (photos, listes de présence)')
                        ->collection('justificatifs')
                        ->multiple()
                        ->reorderable()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                        ->maxSize(10240)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function activityNodes(mixed $projectId): array
    {
        if (blank($projectId)) {
            return [];
        }

        return LogframeNode::query()
            ->where('project_id', $projectId)
            ->where('type', LogframeNodeType::Activite->value)
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn (LogframeNode $n): array => [$n->id => trim(($n->code ? $n->code.' — ' : '').$n->title)])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function teamUsers(mixed $projectId): array
    {
        $project = blank($projectId) ? null : Project::find($projectId);

        if (! $project instanceof Project) {
            return [];
        }

        return $project->members()
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->mapWithKeys(fn ($m): array => $m->user instanceof User ? [$m->user->id => $m->user->getFilamentName()] : [])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function teamMembers(mixed $projectId): array
    {
        $project = blank($projectId) ? null : Project::find($projectId);

        if (! $project instanceof Project) {
            return [];
        }

        return $project->members()
            ->whereNotNull('team_member_id')
            ->with('teamMember')
            ->get()
            ->mapWithKeys(fn ($m): array => $m->teamMember instanceof TeamMember ? [$m->teamMember->id => $m->teamMember->full_name] : [])
            ->all();
    }
}
