<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('clinic:inquiries {--limit=20 : Number of recent inquiries}', function () {
    $limit = max(1, min(100, (int) $this->option('limit')));
    $inquiries = DB::table('inquiries')->latest()->limit($limit)->get();
    if ($inquiries->isEmpty()) {
        $this->info('No inquiries yet.');

        return;
    }
    $this->table(['Reference', 'Name', 'Contact', 'Topic', 'Message', 'Received'], $inquiries->map(fn ($item) => [
        $item->reference, $item->name, $item->contact_number, $item->topic, $item->message, $item->created_at,
    ])->all());
})->purpose('View recent inquiries saved by the clinic website');
