<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Support;

use App\Enums\AgeBracket;
use App\Enums\DisaggregationDimension;
use App\Enums\DisaggregationPhase;
use App\Enums\Sex;
use App\Models\Activity;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

/**
 * Construction, extraction et persistance des désagrégations de participants
 * (RGA-05) dans un formulaire Filament. Les champs vivent sous la clé `disagg`
 * et ne sont pas hydratés vers les colonnes du modèle.
 */
class ActivityDisaggregation
{
    /**
     * Fieldset de saisie pour une phase (prévu/réel).
     */
    public static function fieldset(DisaggregationPhase $phase, string $label): Fieldset
    {
        $p = $phase->value;

        $ageFields = [];
        foreach (AgeBracket::cases() as $bracket) {
            $ageFields[] = TextInput::make("disagg.{$p}.age.{$bracket->value}")
                ->label($bracket->label())
                ->numeric()
                ->minValue(0)
                ->default(0);
        }

        return Fieldset::make($label)
            ->columns(3)
            ->schema([
                TextInput::make("disagg.{$p}.total")
                    ->label('Total participants')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make("disagg.{$p}.sex.".Sex::Femme->value)
                    ->label('Femmes')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make("disagg.{$p}.sex.".Sex::Homme->value)
                    ->label('Hommes')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                ...$ageFields,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{total: int, sex: array<string, int>, age: array<string, int>}
     */
    public static function extract(array $data, DisaggregationPhase $phase): array
    {
        $node = $data['disagg'][$phase->value] ?? [];

        return [
            'total' => (int) ($node['total'] ?? 0),
            'sex' => array_map('intval', $node['sex'] ?? []),
            'age' => array_map('intval', $node['age'] ?? []),
        ];
    }

    /**
     * Réécrit les lignes de désagrégation d'une phase pour une activité.
     *
     * @param  array<string, int>  $counts
     */
    public static function sync(Activity $activity, DisaggregationPhase $phase, string $dimension, array $counts): void
    {
        $dim = DisaggregationDimension::from($dimension);

        $activity->disaggregations()
            ->where('phase', $phase->value)
            ->where('dimension', $dim->value)
            ->delete();

        foreach ($counts as $key => $count) {
            if ((int) $count === 0) {
                continue;
            }

            $activity->disaggregations()->create([
                'phase' => $phase,
                'dimension' => $dim,
                'key' => $key,
                'count' => (int) $count,
            ]);
        }
    }

    /**
     * Charge les compteurs d'une phase pour pré-remplir le formulaire.
     *
     * @return array<string, mixed>
     */
    public static function load(Activity $activity, DisaggregationPhase $phase): array
    {
        $rows = $activity->disaggregations()->where('phase', $phase->value)->get();

        $sex = [];
        $age = [];

        foreach ($rows as $row) {
            if ($row->dimension === DisaggregationDimension::Sex) {
                $sex[$row->key] = $row->count;
            } else {
                $age[$row->key] = $row->count;
            }
        }

        return [
            'total' => array_sum($sex),
            'sex' => $sex,
            'age' => $age,
        ];
    }
}
