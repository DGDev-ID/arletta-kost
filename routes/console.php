<?php

use App\Console\Commands\AutoCheckoutExpiredBills;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto checkout bills yang sudah melewati due_date — dijalankan setiap hari tengah malam
Schedule::command(AutoCheckoutExpiredBills::class)->dailyAt('00:05');
