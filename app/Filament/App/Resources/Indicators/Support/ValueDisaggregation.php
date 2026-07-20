<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Indicators\Support;

use App\Enums\AgeBracket;
use App\Enums\DisaggregationDimension;
use App\Enums\Sex;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\Locality;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

/**
 * Désagrégation détaillée d'une valeur d'indicateur (RGSE-04) : champs de
 * ventilation par axe activé (sexe / âge / localité), contrôle de cohérence
 * (somme d'un axe = valeur totale) et persistance normalisée.
 */
class ValueDisaggregation
{
    /**
     * Fieldsets de saisie selon les axes activés de l'indicateur.
     *
     * @return list<Fieldset>
     */
    public static function fields(Indicator $indicator): array
    {
        $fieldsets = [];

        if ($indicator->hasAxis('sex')) {
            $fieldsets[] = Fieldset::make('Ventilation par sexe')
                ->columns(2)
                ->schema([
                    TextInput::make('disagg.sex.'.Sex::Femme->value)->label('Femmes')->numeric()->minValue(0)->default(0),
                    TextInput::make('disagg.sex.'.Sex::Homme->value)->label('Hommes')->numeric()->minValue(0)->default(0),
                ]);
        }

        if ($indicator->hasAxis('age')) {
            $ageFields = [];
            foreach (AgeBracket::cases() as $bracket) {
                $ageFields[] = TextInput::make('disagg.age.'.$bracket->value)->label($bracket->label())->numeric()->minValue(0)->default(0);
            }
            $fieldsets[] = Fieldset::make('Ventilation par tranche d’âge')->columns(3)->schema($ageFields);
        }

        if ($indicator->hasAxis('locality')) {
            $fieldsets[] = Fieldset::make('Ventilation par localité')
                ->schema([
                    Repeater::make('disagg.locality')
                        ->label('Localités')
                        ->columnSpanFull()
                        ->columns(2)
                        ->schema([
                            Select::make('locality_id')
                                ->label('Localité')
                                ->options(fn (): array => Locality::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                            TextInput::make('count')->label('Nombre')->numeric()->minValue(0)->default(0)->required(),
                        ])
                        ->addActionLabel('Ajouter une localité')
                        ->defaultItems(0),
                ]);
        }

        return $fieldsets;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{sex: array<string, int>, age: array<string, int>, locality: array<int, array{locality_id: string, count: int}>}
     */
    public static function extract(array $data): array
    {
        $node = $data['disagg'] ?? [];

        $locality = [];
        foreach ($node['locality'] ?? [] as $row) {
            if (! empty($row['locality_id'])) {
                $locality[] = ['locality_id' => (string) $row['locality_id'], 'count' => (int) ($row['count'] ?? 0)];
            }
        }

        return [
            'sex' => array_map('intval', $node['sex'] ?? []),
            'age' => array_map('intval', $node['age'] ?? []),
            'locality' => $locality,
        ];
    }

    /**
     * Écarts de cohérence par axe activé (somme = valeur totale).
     *
     * @param  array{sex: array<string, int>, age: array<string, int>, locality: array<int, array{locality_id: string, count: int}>}  $extracted
     * @return list<string>
     */
    public static function issues(Indicator $indicator, float $total, array $extracted): array
    {
        $issues = [];
        $totalInt = (int) round($total);

        if ($indicator->hasAxis('sex')) {
            $sum = array_sum($extracted['sex']);
            if ($sum !== 0 && $sum !== $totalInt) {
                $issues[] = "La somme par sexe ({$sum}) ne correspond pas à la valeur ({$totalInt}).";
            }
        }
        if ($indicator->hasAxis('age')) {
            $sum = array_sum($extracted['age']);
            if ($sum !== 0 && $sum !== $totalInt) {
                $issues[] = "La somme par tranche d'âge ({$sum}) ne correspond pas à la valeur ({$totalInt}).";
            }
        }
        if ($indicator->hasAxis('locality')) {
            $sum = array_sum(array_column($extracted['locality'], 'count'));
            if ($sum !== 0 && $sum !== $totalInt) {
                $issues[] = "La somme par localité ({$sum}) ne correspond pas à la valeur ({$totalInt}).";
            }
        }

        return $issues;
    }

    /**
     * @param  array{sex: array<string, int>, age: array<string, int>, locality: array<int, array{locality_id: string, count: int}>}  $extracted
     */
    public static function sync(IndicatorValue $value, array $extracted): void
    {
        $value->disaggregations()->delete();

        foreach ($extracted['sex'] as $key => $count) {
            self::store($value, DisaggregationDimension::Sex, $key, $count);
        }
        foreach ($extracted['age'] as $key => $count) {
            self::store($value, DisaggregationDimension::Age, $key, $count);
        }
        foreach ($extracted['locality'] as $row) {
            self::store($value, DisaggregationDimension::Locality, $row['locality_id'], $row['count']);
        }
    }

    /**
     * Recharge les compteurs pour pré-remplir le formulaire d'édition.
     *
     * @return array<string, mixed>
     */
    public static function load(IndicatorValue $value): array
    {
        $sex = [];
        $age = [];
        $locality = [];

        foreach ($value->disaggregations()->get() as $row) {
            match ($row->dimension) {
                DisaggregationDimension::Sex => $sex[$row->key] = (int) $row->count,
                DisaggregationDimension::Age => $age[$row->key] = (int) $row->count,
                DisaggregationDimension::Locality => $locality[] = ['locality_id' => $row->key, 'count' => (int) $row->count],
            };
        }

        return ['sex' => $sex, 'age' => $age, 'locality' => $locality];
    }

    private static function store(IndicatorValue $value, DisaggregationDimension $dimension, string $key, int $count): void
    {
        if ($count === 0) {
            return;
        }

        $value->disaggregations()->create([
            'dimension' => $dimension,
            'key' => $key,
            'count' => $count,
        ]);
    }
}
