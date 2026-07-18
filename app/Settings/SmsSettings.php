<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Paramètres SMS au niveau plateforme (super-admin). Un compte unique (ex. Nimba SMS)
 * envoie pour toutes les OSC ; chaque OSC gère seulement l'activation et son quota
 * mensuel (cf. Organization::notificationSettings(), CDC 9.4).
 *
 * Fondation posée dès le socle ; l'intégration Nimba effective relève de la spec 08.
 * La clé API est chiffrée au repos.
 */
class SmsSettings extends Settings
{
    /** Interrupteur global : tant que faux, aucun SMS n'est envoyé quelle que soit l'OSC. */
    public bool $enabled = false;

    public string $provider = 'nimba';

    public ?string $senderId = null;

    public ?string $apiKey = null;

    public static function group(): string
    {
        return 'sms';
    }

    /**
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['apiKey'];
    }
}
