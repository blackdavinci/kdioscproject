<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification interne à l'admin émetteur lorsqu'une invitation est refusée parce que
 * l'adresse est déjà titulaire d'un compte (RG-07). Ne fuit rien vers l'invité.
 */
class InvitationBlocked extends Notification
{
    use Queueable;

    public function __construct(public string $email) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Invitation non envoyée',
            'body' => "L'adresse {$this->email} est déjà associée à un compte sur la plateforme. Aucune invitation n'a été envoyée.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitation non envoyée')
            ->line("L'adresse {$this->email} est déjà associée à un compte sur la plateforme.")
            ->line("Aucune invitation n'a été envoyée (protection anti-énumération).");
    }
}
