<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Budget\AlertBudgetThresholds;
use Illuminate\Console\Command;

/**
 * Alertes budgétaires proactives (RGD-06 / RGB-07). Planifiée quotidiennement.
 */
class AlertBudget extends Command
{
    protected $signature = 'budget:alert';

    protected $description = 'Notifie les responsables financiers des lignes budgétaires au-dessus du seuil.';

    public function handle(): int
    {
        $count = (new AlertBudgetThresholds)->handle();
        $this->info("{$count} alerte(s) budgétaire(s) envoyée(s).");

        return self::SUCCESS;
    }
}
