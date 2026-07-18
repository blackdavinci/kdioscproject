<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Organization;

/**
 * Bloc de paramétrage « notifications » d'une organisation (stocké dans
 * organizations.settings['notifications']). Modèle centralisé : l'OSC ne détient
 * aucune clé API par défaut — elle ne surcharge que l'expéditeur affiché, l'adresse
 * de réponse et ses préférences SMS (activation + quota mensuel, CDC 9.4).
 *
 * Le champ `byoMailProvider` réserve l'option « Bring Your Own provider » (spec 08) :
 * une OSC pourra y déclarer son propre transport ; tant qu'il est nul, l'envoi passe
 * par le compte plateforme (MailSettings).
 */
class OrganizationNotificationSettings
{
    public function __construct(
        public readonly string $fromName,
        public readonly ?string $replyTo,
        public readonly bool $smsEnabled,
        public readonly int $smsMonthlyQuota,
        public readonly ?string $byoMailProvider = null,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        $fromName = data_get($organization->settings, 'notifications.from_name');
        $replyTo = data_get($organization->settings, 'notifications.reply_to');
        $byoProvider = data_get($organization->settings, 'notifications.byo_mail_provider');
        $defaultReplyTo = data_get($organization->contacts, 'email');

        return new self(
            fromName: is_string($fromName) && $fromName !== '' ? $fromName : $organization->name,
            replyTo: is_string($replyTo) ? $replyTo : (is_string($defaultReplyTo) ? $defaultReplyTo : null),
            smsEnabled: (bool) data_get($organization->settings, 'notifications.sms_enabled', false),
            smsMonthlyQuota: (int) data_get($organization->settings, 'notifications.sms_monthly_quota', 0),
            byoMailProvider: is_string($byoProvider) ? $byoProvider : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from_name' => $this->fromName,
            'reply_to' => $this->replyTo,
            'sms_enabled' => $this->smsEnabled,
            'sms_monthly_quota' => $this->smsMonthlyQuota,
            'byo_mail_provider' => $this->byoMailProvider,
        ];
    }
}
