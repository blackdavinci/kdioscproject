<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Settings\MailSettings;
use App\Settings\SmsSettings;
use Illuminate\Support\Facades\DB;

it('expose les paramètres plateforme d’envoi avec des défauts centralisés désactivés', function (): void {
    $mail = app(MailSettings::class);
    $sms = app(SmsSettings::class);

    expect($mail->enabled)->toBeFalse()
        ->and($mail->provider)->toBe('brevo')
        ->and($mail->fromName)->toBe('KIDIANI OSC')
        ->and($sms->enabled)->toBeFalse()
        ->and($sms->provider)->toBe('nimba');
});

it('chiffre la clé API au repos (jamais en clair en base)', function (): void {
    $mail = app(MailSettings::class);
    $mail->apiKey = 'cle-brevo-tres-secrete';
    $mail->save();

    $stored = DB::table('settings')->where('group', 'mail')->where('name', 'apiKey')->value('payload');

    expect($stored)->not->toContain('cle-brevo-tres-secrete');
    expect(app(MailSettings::class)->apiKey)->toBe('cle-brevo-tres-secrete');
});

it('déduit l’expéditeur affiché de l’OSC et laisse surcharger reply-to / SMS (modèle centralisé)', function (): void {
    $org = Organization::factory()->create([
        'name' => 'ONG Espoir',
        'contacts' => ['email' => 'contact@espoir.gn'],
    ]);

    $defaults = $org->notificationSettings();

    expect($defaults->fromName)->toBe('ONG Espoir')       // défaut = nom de l'OSC
        ->and($defaults->replyTo)->toBe('contact@espoir.gn') // défaut = contact de l'OSC
        ->and($defaults->smsEnabled)->toBeFalse()
        ->and($defaults->byoMailProvider)->toBeNull();       // pas de BYO par défaut

    $org->forceFill(['settings' => ['notifications' => [
        'from_name' => 'Espoir Guinée',
        'sms_enabled' => true,
        'sms_monthly_quota' => 200,
    ]]])->save();

    $overridden = $org->fresh()->notificationSettings();

    expect($overridden->fromName)->toBe('Espoir Guinée')
        ->and($overridden->smsEnabled)->toBeTrue()
        ->and($overridden->smsMonthlyQuota)->toBe(200);
});
