<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Support;

use App\Enums\ActivityStatus;
use App\Enums\DisaggregationPhase;
use App\Models\Activity;
use App\Models\Organization;
use App\Support\DisaggregationCheck;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Extraction, contrôle de cohérence (RGA-05 : alerte par défaut, blocage si
 * l'organisation l'impose) et persistance des désagrégations d'une activité.
 */
trait HandlesActivityForm
{
    /** @var array<string, mixed> */
    protected array $stashedDisagg = [];

    /**
     * Retire les compteurs de désagrégation des données du modèle et les met de côté.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function stripDisaggregation(array $data): array
    {
        $this->stashedDisagg = $data['disagg'] ?? [];
        unset($data['disagg']);

        // Statut dérivé : une date de réalisation renseignée marque l'activité réalisée.
        if (! empty($data['realized_at']) && ($data['status'] ?? ActivityStatus::Planifiee->value) === ActivityStatus::Planifiee->value) {
            $data['status'] = ActivityStatus::Realisee->value;
        }

        return $data;
    }

    /**
     * Bloque la sauvegarde si l'organisation impose la cohérence et qu'un écart existe.
     */
    protected function assertCoherenceIfEnforced(): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization || ! $tenant->enforcesDisaggregation()) {
            return;
        }

        if ($this->coherenceIssues() !== []) {
            throw ValidationException::withMessages([
                'data.disagg' => 'Désagrégations incohérentes : '.implode(' ', $this->coherenceIssues()),
            ]);
        }
    }

    protected function persistDisaggregation(Activity $activity): void
    {
        foreach ([DisaggregationPhase::Planned, DisaggregationPhase::Real] as $phase) {
            $d = ActivityDisaggregation::extract(['disagg' => $this->stashedDisagg], $phase);
            ActivityDisaggregation::sync($activity, $phase, 'sex', $d['sex']);
            ActivityDisaggregation::sync($activity, $phase, 'age', $d['age']);
        }

        $issues = $this->coherenceIssues();
        if ($issues !== []) {
            Notification::make()
                ->warning()
                ->title('Désagrégations à vérifier')
                ->body(implode(' ', $issues))
                ->send();
        }
    }

    /**
     * @return list<string>
     */
    protected function coherenceIssues(): array
    {
        $issues = [];

        foreach ([DisaggregationPhase::Planned, DisaggregationPhase::Real] as $phase) {
            $d = ActivityDisaggregation::extract(['disagg' => $this->stashedDisagg], $phase);

            if ($d['total'] === 0 && array_sum($d['sex']) === 0 && array_sum($d['age']) === 0) {
                continue; // phase non renseignée
            }

            foreach (DisaggregationCheck::issues($d['total'], $d['sex'], $d['age']) as $issue) {
                $issues[] = ($phase === DisaggregationPhase::Planned ? 'Prévu — ' : 'Réel — ').$issue;
            }
        }

        return $issues;
    }
}
