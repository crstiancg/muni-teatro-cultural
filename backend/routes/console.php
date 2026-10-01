<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// backup diario de la base y los archivos subidos (ver App\Console\Commands\BackupDiario)
Schedule::command('backup:diario')->dailyAt('03:00')->withoutOverlapping();
