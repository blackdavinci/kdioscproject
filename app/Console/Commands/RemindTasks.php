<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tasks\RemindDueTasks;
use Illuminate\Console\Command;

/**
 * Rappels d'échéance des tâches (RGT-13). Planifiée quotidiennement.
 */
class RemindTasks extends Command
{
    protected $signature = 'tasks:remind';

    protected $description = 'Notifie les assignés dont une tâche approche de son échéance (rappel J-X).';

    public function handle(): int
    {
        $count = (new RemindDueTasks)->handle();
        $this->info("{$count} rappel(s) d’échéance envoyé(s).");

        return self::SUCCESS;
    }
}
