<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Paramètres commerciaux et d'encaissement (RGF-12). Config Djomy (clés chiffrées au
 * repos) + politique d'abonnement (délai de grâce, planning des relances, réactivation
 * automatique). Le prix/périodicité/essai vivent dans les plans (billing_plans).
 */
class BillingSettings extends Settings
{
    // --- Djomy ---
    public bool $djomy_enabled = false;

    /** sandbox | production */
    public string $djomy_environment = 'sandbox';

    public ?string $djomy_client_id = null;

    public ?string $djomy_client_secret = null;

    public string $djomy_api_url = '';

    public ?string $djomy_webhook_secret = null;

    public ?string $djomy_partner_domain = null;

    // --- Politique d'abonnement ---
    public int $grace_days = 15;

    /** @var array<int, int> Jours avant échéance déclenchant une relance. */
    public array $reminder_days_before = [30, 7, 0];

    public bool $auto_reactivate_on_payment = true;

    public static function group(): string
    {
        return 'billing';
    }

    /**
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['djomy_client_id', 'djomy_client_secret', 'djomy_webhook_secret', 'djomy_partner_domain'];
    }

    public function isSandbox(): bool
    {
        return $this->djomy_environment === 'sandbox';
    }

    public function getApiUrl(): string
    {
        if ($this->isSandbox()) {
            return 'https://sandbox-api.djomy.africa/v1/';
        }

        return $this->djomy_api_url !== '' ? $this->djomy_api_url : 'https://api.djomy.africa/v1/';
    }
}
