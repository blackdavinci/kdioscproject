<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cycle de vie des abonnements : facturation des échéances + suspension des impayés (RGF-08).
Schedule::command('billing:advance')->dailyAt('02:00');

// Rappels d'échéance des tâches (RGT-13) et récap hebdomadaire des retards (RGT-14).
Schedule::command('tasks:remind')->dailyAt('07:00');
Schedule::command('tasks:overdue-digest')->weeklyOn(1, '07:30');
