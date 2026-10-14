<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('demo:prune {--hours=24}', function () {
    $count = User::pruneDemos((int) $this->option('hours'));

    $this->info("Removed {$count} demo accounts.");
})->purpose('Delete demo accounts older than the given number of hours');

Schedule::command('demo:prune')->hourly();
