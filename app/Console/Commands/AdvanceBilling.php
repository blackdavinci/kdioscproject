<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\AdvanceSubscriptionLifecycle;
use App\Actions\Billing\SendRenewalReminders;
use Illuminate\Console\Command;

/**
 * Fait avancer le cycle de vie des abonnements (RGF-08) : facturation des échéances et
 * suspension des impayés. Planifiée quotidiennement (voir routes/console.php).
 */
class AdvanceBilling extends Command
{
    protected $signature = 'billing:advance';

    protected $description = 'Facture les échéances d’abonnement et suspend les impayés (cycle de vie).';

    public function handle(): int
    {
        $result = (new AdvanceSubscriptionLifecycle)->handle();
        $reminders = (new SendRenewalReminders)->handle();

        $this->info("Cycle de vie : {$result['invoiced']} facture(s) émise(s), {$result['suspended']} suspension(s), {$reminders} relance(s).");

        return self::SUCCESS;
    }
}
