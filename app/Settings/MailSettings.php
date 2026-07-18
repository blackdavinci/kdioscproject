<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Paramètres d'envoi d'e-mail au niveau plateforme (super-admin), modèle centralisé :
 * un compte unique (ex. Brevo) envoie pour toutes les OSC, chaque OSC ne surchargeant
 * que l'expéditeur affiché et l'adresse de réponse (cf. Organization::notificationSettings()).
 *
 * Fondation posée dès le socle ; le moteur d'envoi effectif relève de la spec 08.
 * La clé API est chiffrée au repos et ne doit jamais apparaître dans les exports/audit.
 */
class MailSettings extends Settings
{
    public bool $enabled = false;

    /** Fournisseur transactionnel : 'brevo' (défaut), 'smtp'… */
    public string $provider = 'brevo';

    public string $fromAddress = 'plateforme@kdiosc.test';

    public string $fromName = 'KIDIANI OSC';

    public ?string $apiKey = null;

    public static function group(): string
    {
        return 'mail';
    }

    /**
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['apiKey'];
    }
}
