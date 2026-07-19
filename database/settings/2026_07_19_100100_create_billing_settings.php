<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valeurs par défaut de la configuration commerciale (Djomy désactivé tant que non
 * renseigné ; politique d'abonnement par défaut : grâce 15 j, relances J-30/J-7/J-0,
 * réactivation auto). Clés Djomy chiffrées.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('billing.djomy_enabled', false);
        $this->migrator->add('billing.djomy_environment', 'sandbox');
        $this->migrator->addEncrypted('billing.djomy_client_id', null);
        $this->migrator->addEncrypted('billing.djomy_client_secret', null);
        $this->migrator->add('billing.djomy_api_url', '');
        $this->migrator->addEncrypted('billing.djomy_webhook_secret', null);
        $this->migrator->addEncrypted('billing.djomy_partner_domain', null);

        $this->migrator->add('billing.grace_days', 15);
        $this->migrator->add('billing.reminder_days_before', [30, 7, 0]);
        $this->migrator->add('billing.auto_reactivate_on_payment', true);
    }
};
