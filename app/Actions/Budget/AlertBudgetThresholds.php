<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\BudgetLine;
use App\Models\User;
use App\Notifications\TaskMailNotice;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Alerte budgétaire proactive (RGD-06, complète RGB-07) : notifie le responsable
 * financier et l'admin de chaque organisation lorsqu'une ligne franchit son seuil.
 * Anti-doublon via `alert_notified_at` (réinitialisé quand la ligne repasse sous
 * le seuil). S'exécute hors contexte tenant.
 */
class AlertBudgetThresholds
{
    public function handle(): int
    {
        $notified = 0;

        BudgetLine::query()->with('project')->chunkById(200, function (Collection $lines) use (&$notified): void {
            foreach ($lines as $line) {
                /** @var BudgetLine $line */
                if (! $line->isOverThreshold()) {
                    // Repassée sous le seuil : on réarme l'alerte.
                    if ($line->alert_notified_at !== null) {
                        $line->forceFill(['alert_notified_at' => null])->save();
                    }

                    continue;
                }

                if ($line->alert_notified_at !== null) {
                    continue; // déjà notifiée pour ce franchissement
                }

                $notified += $this->notifyLine($line);
                $line->forceFill(['alert_notified_at' => now()])->save();
            }
        });

        return $notified;
    }

    private function notifyLine(BudgetLine $line): int
    {
        $rate = $line->consumptionRate();
        $percent = $rate !== null ? round($rate * 100) : 0;
        $projectTitle = (string) data_get($line, 'project.title', '—');
        $message = $line->isOverspent()
            ? "Dépassement de budget sur « {$line->label} » ({$projectTitle}) : {$percent} %."
            : "Seuil d'alerte atteint sur « {$line->label} » ({$projectTitle}) : {$percent} %.";

        app(PermissionRegistrar::class)->setPermissionsTeamId($line->organization_id);

        $recipients = User::query()
            ->where('organization_id', $line->organization_id)
            ->get()
            ->filter(fn (User $u): bool => $u->hasAnyRole(['admin', 'responsable_financier']));

        $count = 0;
        foreach ($recipients as $user) {
            FilamentNotification::make()
                ->warning()
                ->title('Alerte budgétaire')
                ->body($message)
                ->sendToDatabase($user);

            if (filled($user->email)) {
                $user->notify(new TaskMailNotice('Alerte budgétaire', $message));
            }

            $count++;
        }

        return $count;
    }
}
