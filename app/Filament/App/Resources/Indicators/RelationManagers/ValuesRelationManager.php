<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\RelationManagers;

use App\Enums\IndicatorValueSource;
use App\Filament\App\Resources\Indicators\Support\ValueDisaggregation;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'Valeurs réalisées';

    public function form(Schema $schema): Schema
    {
        $owner = $this->getOwnerRecord();
        $disaggFields = $owner instanceof Indicator ? ValueDisaggregation::fields($owner) : [];

        return $schema->components([
            TextInput::make('period_label')
                ->label('Période')
                ->placeholder('2026-T1')
                ->required()
                ->maxLength(50),
            TextInput::make('value')
                ->label('Valeur réalisée')
                ->numeric()
                ->required(),
            DatePicker::make('period_start')
                ->label('Début de période')
                ->native(false)
                ->required(),
            DatePicker::make('period_end')
                ->label('Fin de période')
                ->native(false)
                ->required()
                ->afterOrEqual('period_start'),
            Select::make('source')
                ->label('Source')
                ->options(IndicatorValueSource::class)
                ->default(IndicatorValueSource::Manuelle)
                ->required()
                ->live(),
            TextInput::make('kobo_reference')
                ->label('Référence Kobo')
                ->visible(fn (Get $get): bool => $get('source') === IndicatorValueSource::Kobo->value),
            SpatieMediaLibraryFileUpload::make('verification')
                ->label('Moyen de vérification')
                ->collection('verification')
                ->multiple()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                ->maxSize(10240)
                ->columnSpanFull(),
            ...$disaggFields,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period_label')
            ->defaultSort('period_start')
            ->columns([
                TextColumn::make('period_label')->label('Période'),
                TextColumn::make('value')->label('Réalisé')->numeric(),
                TextColumn::make('source')->label('Source')->badge(),
                TextColumn::make('disaggregations_count')->label('Ventilé')->counts('disaggregations')->badge()->color('gray'),
                TextColumn::make('recorded_at')->label('Saisie le')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Saisir une valeur')
                    ->using(fn (array $data): IndicatorValue => $this->persist($data)),
            ])
            ->recordActions([
                Action::make('ventilation')
                    ->label('Ventilation')
                    ->icon('heroicon-o-chart-pie')
                    ->color('gray')
                    ->visible(fn (IndicatorValue $record): bool => $record->disaggregations()->exists())
                    ->modalHeading('Détail de la ventilation')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn (IndicatorValue $record) => view('filament.app.components.value-disaggregation', ['record' => $record])),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, IndicatorValue $record): array {
                        $data['disagg'] = ValueDisaggregation::load($record);

                        return $data;
                    })
                    ->using(fn (IndicatorValue $record, array $data): IndicatorValue => $this->persist($data, $record)),
                DeleteAction::make(),
            ]);
    }

    /**
     * Persiste une valeur avec sa désagrégation (RGSE-04) : contrôle de cohérence
     * (alerte par défaut, blocage si l'organisation l'impose).
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data, ?IndicatorValue $record = null): IndicatorValue
    {
        $owner = $this->getOwnerRecord();
        $extracted = ValueDisaggregation::extract($data);
        unset($data['disagg']);

        $issues = $owner instanceof Indicator
            ? ValueDisaggregation::issues($owner, (float) ($data['value'] ?? 0), $extracted)
            : [];

        $tenant = Filament::getTenant();
        if ($issues !== [] && $tenant instanceof Organization && $tenant->enforcesDisaggregation()) {
            throw ValidationException::withMessages(['data.value' => 'Désagrégations incohérentes : '.implode(' ', $issues)]);
        }

        if ($record instanceof IndicatorValue) {
            $record->update($data);
        } else {
            $data['recorded_by'] = Filament::auth()->id();
            $data['recorded_at'] = now();
            /** @var IndicatorValue $record */
            $record = $this->getRelationship()->create($data);
        }

        ValueDisaggregation::sync($record, $extracted);

        if ($issues !== []) {
            Notification::make()->warning()->title('Désagrégations à vérifier')->body(implode(' ', $issues))->send();
        }

        return $record;
    }
}
