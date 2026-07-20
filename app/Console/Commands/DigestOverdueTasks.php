<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tasks\SendOverdueTasksDigest;
use Illuminate\Console\Command;

/**
 * Récapitulatif hebdomadaire des tâches en retard (RGT-14). Planifiée le lundi matin.
 */
class DigestOverdueTasks extends Command
{
    protected $signature = 'tasks:overdue-digest';

    protected $description = 'Notifie les équipes projet des tâches en retard (récap hebdomadaire).';

    public function handle(): int
    {
        $count = (new SendOverdueTasksDigest)->handle();
        $this->info("{$count} notification(s) de retard envoyée(s).");

        return self::SUCCESS;
    }
}
