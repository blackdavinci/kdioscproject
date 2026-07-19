<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\DjomyWebhookController;
use App\Http\Controllers\PublicBillingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/app'));

// Règlement d'abonnement hors session (RGF-15) — pour une organisation suspendue.
Route::get('/regler', [PublicBillingController::class, 'show'])->name('billing.settle');
Route::post('/regler', [PublicBillingController::class, 'pay'])->name('billing.settle.pay')->middleware('throttle:6,1');

// Webhook Djomy (RGF-13) — signature HMAC vérifiée dans le contrôleur, hors CSRF.
Route::post('/webhooks/djomy', [DjomyWebhookController::class, 'handle'])->name('webhooks.djomy');

// Acceptation d'invitation (RG-07). Le lien GET est signé (72 h) ; la soumission
// revérifie le jeton et l'état de l'invitation.
Route::get('/invitation/{invitation}/{token}', [AcceptInvitationController::class, 'show'])
    ->name('invitation.accept')
    ->middleware('signed');

Route::post('/invitation/{invitation}/{token}', [AcceptInvitationController::class, 'store'])
    ->name('invitation.accept.store');
