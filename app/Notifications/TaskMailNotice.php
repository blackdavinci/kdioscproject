<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail générique lié aux tâches (rappel d'échéance RGT-13, récap des retards RGT-14).
 */
class TaskMailNotice extends Notification
{
    public function __construct(
        private readonly string $subjectLine,
        private readonly string $line,
        private readonly ?string $url = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting('Bonjour,')
            ->line($this->line);

        if ($this->url !== null) {
            $mail->action('Ouvrir', $this->url);
        }

        return $mail;
    }
}
