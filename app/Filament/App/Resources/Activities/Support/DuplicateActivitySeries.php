<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Activities\Support;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Duplique une activité en série (RGA-12) : génère N occurrences supplémentaires
 * référençant le même nœud du cadre logique, aux dates décalées, reliées par
 * `recurrence_group_id`. Seule la planification est reprise (pas la réalisation).
 */
class DuplicateActivitySeries
{
    /**
     * @param  'weekly'|'biweekly'|'monthly'  $frequency
     * @return int nombre d'occurrences créées
     */
    public static function handle(Activity $activity, string $frequency, int $count): int
    {
        $count = max(1, min($count, 52));

        $groupId = $activity->recurrence_group_id ?? (string) Str::ulid();

        return DB::transaction(function () use ($activity, $frequency, $count, $groupId): int {
            if ($activity->recurrence_group_id === null) {
                $activity->update(['recurrence_group_id' => $groupId]);
            }

            $created = 0;

            for ($i = 1; $i <= $count; $i++) {
                $start = self::shift($activity->planned_start, $frequency, $i);
                $end = $activity->planned_end
                    ? self::shift($activity->planned_end, $frequency, $i)
                    : null;

                Activity::create([
                    'organization_id' => $activity->organization_id,
                    'project_id' => $activity->project_id,
                    'logframe_node_id' => $activity->logframe_node_id,
                    'title' => $activity->title,
                    'planned_start' => $start,
                    'planned_end' => $end,
                    'geo_unit_id' => $activity->geo_unit_id,
                    'locality_id' => $activity->locality_id,
                    'latitude' => $activity->latitude,
                    'longitude' => $activity->longitude,
                    'responsible_user_id' => $activity->responsible_user_id,
                    'responsible_team_member_id' => $activity->responsible_team_member_id,
                    'planned_resources' => $activity->planned_resources,
                    'status' => ActivityStatus::Planifiee,
                    'recurrence_group_id' => $groupId,
                    'created_by' => $activity->created_by,
                ]);

                $created++;
            }

            return $created;
        });
    }

    private static function shift(Carbon $date, string $frequency, int $i): Carbon
    {
        return match ($frequency) {
            'weekly' => $date->copy()->addWeeks($i),
            'biweekly' => $date->copy()->addWeeks($i * 2),
            default => $date->copy()->addMonthsNoOverflow($i),
        };
    }
}
