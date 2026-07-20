<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\Schemas;

use App\Enums\IndicatorDirection;
use App\Enums\PeriodType;
use App\Models\LogframeNode;
use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class IndicatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
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
                                ->label('Niveau (nœud du cadre logique)')
                                ->options(fn (Get $get): array => self::nodeOptions($get('project_id')))
                                ->searchable()
                                ->required(),
                        ]),

                    Fieldset::make('Définition')
                        ->columns(2)
                        ->schema([
                            TextInput::make('code')
                                ->label('Code')
                                ->maxLength(50),
                            TextInput::make('label')
                                ->label('Libellé')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('unit')
                                ->label('Unité')
                                ->placeholder('personnes, %, ratio…')
                                ->maxLength(50),
                            Select::make('direction')
                                ->label('Sens')
                                ->options(IndicatorDirection::class)
                                ->default(IndicatorDirection::Croissant)
                                ->required(),
                            TextInput::make('baseline_value')
                                ->label('Valeur de référence (baseline)')
                                ->numeric(),
                            DatePicker::make('baseline_date')
                                ->label('Date de référence')
                                ->native(false),
                        ]),

                    Fieldset::make('Périodicité et désagrégation')
                        ->columns(2)
                        ->schema([
                            Select::make('period_type')
                                ->label('Type de période')
                                ->options(PeriodType::class)
                                ->default(PeriodType::Trimestriel)
                                ->required(),
                            Toggle::make('disaggregations.sex')
                                ->label('Désagréger par sexe'),
                            Toggle::make('disaggregations.age')
                                ->label('Désagréger par tranche d’âge'),
                            Toggle::make('disaggregations.locality')
                                ->label('Désagréger par localité'),
                        ]),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function nodeOptions(mixed $projectId): array
    {
        if (blank($projectId)) {
            return [];
        }

        return LogframeNode::query()
            ->where('project_id', $projectId)
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn (LogframeNode $n): array => [$n->id => trim(($n->code ? $n->code.' — ' : '').$n->title)])
            ->all();
    }
}
