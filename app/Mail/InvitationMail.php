<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail d'invitation à créer un compte (RG-07). Contient le lien signé d'activation.
 */
class InvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $acceptUrl,
    ) {}

    protected function organizationName(): string
    {
        return (string) $this->invitation->organization?->name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation à rejoindre '.$this->organizationName().' sur KIDIANI OSC',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'organizationName' => $this->organizationName(),
                'roleLabel' => $this->invitation->role->label(),
                'acceptUrl' => $this->acceptUrl,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
