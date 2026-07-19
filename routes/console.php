<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cycle de vie des abonnements : facturation des échéances + suspension des impayés (RGF-08).
Schedule::command('billing:advance')->dailyAt('02:00');
