<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Notifications\SubscriptionRenewalReminder;
use App\Settings\BillingSettings;
use App\Support\OrganizationAdmins;
use Illuminate\Support\Facades\Notification;

/**
 * Envoie les relances de renouvellement (RGF-10) : pour chaque facture en attente dont
 * l'échéance tombe dans exactement l'un des jours configurés (ex. J-30 / J-7 / J-0),
 * notifie les administrateurs de l'organisation (in-app + e-mail). Exécuté quotidiennement.
 *
 * @return int Nombre de relances envoyées.
 */
class SendRenewalReminders
{
    public function handle(): int
    {
        $offsets = app(BillingSettings::class)->reminder_days_before;
        $admins = app(OrganizationAdmins::class);
        $today = now()->startOfDay();
        $sent = 0;

        $pending = Invoice::query()->where('status', InvoiceStatus::Pending->value)->get();

        foreach ($pending as $invoice) {
            $daysUntilDue = (int) round($today->diffInDays($invoice->due_date->copy()->startOfDay(), false));

            if (! in_array($daysUntilDue, $offsets, true)) {
                continue;
            }

            $recipients = $admins->activeAdmins($invoice->organization_id);

            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new SubscriptionRenewalReminder($invoice, $daysUntilDue));
            $sent += $recipients->count();
        }

        return $sent;
    }
}
