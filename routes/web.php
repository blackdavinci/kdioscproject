<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\ActivityPdfController;
use App\Http\Controllers\DjomyWebhookController;
use App\Http\Controllers\PublicBillingController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\TenantSubdomainController;
use Illuminate\Support\Facades\Route;

// Porte d'entrée par sous-domaine dédié : {slug}.kidiani.com → espace de l'OSC.
// Les sous-domaines réservés (app, www, admin, api, mail) sont exclus pour ne
// jamais masquer l'hôte du panel. Requiert en prod un wildcard DNS *.kidiani.com
// et un certificat TLS wildcard (configuration serveur, hors code).
Route::domain('{osc}.'.config('app.tenant_domain'))->group(function (): void {
    Route::get('/', TenantSubdomainController::class)
        ->where('osc', '[a-z0-9][a-z0-9-]*')
        ->name('tenant.subdomain');
});

Route::get('/', fn () => redirect('/app'));

// Règlement d'abonnement hors session (RGF-15) — pour une organisation suspendue.
Route::get('/regler', [PublicBillingController::class, 'show'])->name('billing.settle');
Route::post('/regler', [PublicBillingController::class, 'pay'])->name('billing.settle.pay')->middleware('throttle:6,1');

// Reçu PDF d'un paiement d'abonnement (RGF-17) — autorisation vérifiée dans le contrôleur.
Route::get('/facturation/recu/{invoice}', [ReceiptController::class, 'download'])->name('billing.receipt');

// Formulaires papier d'une activité (RGA-09) — autorisation vérifiée dans le contrôleur.
Route::get('/activites/{activity}/fiche', [ActivityPdfController::class, 'sheet'])->name('activities.sheet');
Route::get('/activites/{activity}/presence', [ActivityPdfController::class, 'attendance'])->name('activities.attendance');

// Webhook Djomy (RGF-13) — signature HMAC vérifiée dans le contrôleur, hors CSRF.
Route::post('/webhooks/djomy', [DjomyWebhookController::class, 'handle'])->name('webhooks.djomy');

// Acceptation d'invitation (RG-07). Le lien GET est signé (72 h) ; la soumission
// revérifie le jeton et l'état de l'invitation.
Route::get('/invitation/{invitation}/{token}', [AcceptInvitationController::class, 'show'])
    ->name('invitation.accept')
    ->middleware('signed');

Route::post('/invitation/{invitation}/{token}', [AcceptInvitationController::class, 'store'])
    ->name('invitation.accept.store');
