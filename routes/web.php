<?php

use App\Http\Controllers\AcceptInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/app'));

// Acceptation d'invitation (RG-07). Le lien GET est signé (72 h) ; la soumission
// revérifie le jeton et l'état de l'invitation.
Route::get('/invitation/{invitation}/{token}', [AcceptInvitationController::class, 'show'])
    ->name('invitation.accept')
    ->middleware('signed');

Route::post('/invitation/{invitation}/{token}', [AcceptInvitationController::class, 'store'])
    ->name('invitation.accept.store');
