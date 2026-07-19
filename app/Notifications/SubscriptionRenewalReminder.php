<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Billing\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Relance de renouvellement d'abonnement (RGF-10) : in-app + e-mail à l'admin de l'OSC,
 * avec le montant, l'échéance et le rappel de régler pour éviter la suspension.
 */
class SubscriptionRenewalReminder extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice, public int $daysBefore) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->invoice->amount_gnf, 0, ',', ' ');
        $due = $this->invoice->due_date->format('d/m/Y');

        return (new MailMessage)
            ->subject('Renouvellement de votre abonnement KDI OSC')
            ->line("Votre abonnement doit être réglé avant le {$due}.")
            ->line("Facture {$this->invoice->number} : {$amount} GNF.")
            ->line('Sans règlement à l’échéance, l’accès de votre organisation sera suspendu.')
            ->action('Régler mon abonnement', url('/app'));
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Renouvellement d’abonnement',
            'body' => "Facture {$this->invoice->number} à régler avant le {$this->invoice->due_date->format('d/m/Y')}.",
        ];
    }
}
