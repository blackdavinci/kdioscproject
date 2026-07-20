<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail envoyé à une personne mentionnée dans un commentaire (RGT-09/10).
 */
class CommentMentionMail extends Notification
{
    public function __construct(
        private readonly Comment $comment,
        private readonly string $subjectLabel,
        private readonly string $url,
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
        return (new MailMessage)
            ->subject('Vous avez été mentionné·e')
            ->greeting('Bonjour,')
            ->line($this->comment->author?->getFilamentName().' vous a mentionné·e sur : '.$this->subjectLabel)
            ->line('« '.str($this->comment->body)->limit(160).' »')
            ->action('Ouvrir', $this->url);
    }
}
