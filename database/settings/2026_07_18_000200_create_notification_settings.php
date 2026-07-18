<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valeurs par défaut des paramètres d'envoi plateforme (mail + SMS). Modèle
 * centralisé : désactivés tant que le super-admin n'a pas renseigné les clés API.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.enabled', false);
        $this->migrator->add('mail.provider', 'brevo');
        $this->migrator->add('mail.fromAddress', 'plateforme@kdiosc.test');
        $this->migrator->add('mail.fromName', 'KIDIANI OSC');
        $this->migrator->addEncrypted('mail.apiKey', null);

        $this->migrator->add('sms.enabled', false);
        $this->migrator->add('sms.provider', 'nimba');
        $this->migrator->add('sms.senderId', null);
        $this->migrator->addEncrypted('sms.apiKey', null);
    }
};
