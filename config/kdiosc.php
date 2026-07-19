<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | 2FA obligatoire des administrateurs (RG-09)
    |--------------------------------------------------------------------------
    |
    | Impose la double authentification au super-admin (panel `admin`) et aux
    | administrateurs d'organisation (panel `app`) : tant qu'elle n'est pas
    | confirmée, l'accès est restreint à « Mon profil ». Laisser à true en
    | production ; peut être désactivé en local pour le confort de développement
    | via KDIOSC_ENFORCE_ADMIN_2FA=false.
    |
    */

    'enforce_admin_two_factor' => (bool) env('KDIOSC_ENFORCE_ADMIN_2FA', true),

];
