<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('hourly:maintain {task}', function (string $task) {
    $maintenance = app(\App\Services\HourlyMaintenance::class);
    if (!in_array($task, ['expiry', 'slots'], true)) {
        $this->error('Pilih expiry atau slots.');
        return 1;
    }
    if (!$maintenance->ready()) {
        $this->info('Schema booking per jam belum siap.');
        return 0;
    }
    if (!$maintenance->run($task)) {
        $this->error('Sebagian venue gagal diproses. Lihat pemantauan dan log aplikasi.');
        return 1;
    }
    $this->info('Proses booking per jam selesai.');
    return 0;
})->describe('Kedaluwarsakan booking atau lengkapi slot per jam');
