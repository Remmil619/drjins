<?php

use App\Services\BookingSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:sync', function () {
    if (app(BookingSync::class)->sync()) {
        $this->info('Booking portal synchronized.');

        return 0;
    }
    $this->error('Portal unavailable or invalid response; pending updates retained.');

    return 1;
});
Schedule::command('bookings:sync')->everyMinute()->withoutOverlapping();

Artisan::command('clinic:backup', function () {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        $this->error('Use your database server backup tools for non-SQLite databases.');

        return 1;
    }
    $database = config('database.connections.sqlite.database');
    if (! $database || $database === ':memory:' || ! is_file($database)) {
        $this->error('A file-based SQLite database is required.');

        return 1;
    }
    $directory = storage_path('app/private/backups');
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    $file = $directory.'/clinic-'.now()->format('Ymd-His').'-'.Str::random(6).'.sqlite';
    $source = new SQLite3($database, SQLITE3_OPEN_READONLY);
    $destination = new SQLite3($file.'.partial');
    $ok = $source->backup($destination);
    $destination->close();
    $source->close();
    if (! $ok) {
        $this->error('Backup failed; the partial file must not be used for restoration.');

        return 1;
    }
    rename($file.'.partial', $file);
    $this->info('Database backup saved in storage/app/private/backups.');

    return 0;
})->purpose('Create a consistent local SQLite backup without exposing patient records publicly');
