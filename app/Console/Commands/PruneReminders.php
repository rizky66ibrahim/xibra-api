<?php

namespace App\Console\Commands;

use App\Services\ReminderSync;
use Illuminate\Console\Command;

class PruneReminders extends Command
{
    protected $signature = 'reminders:prune {--days=30 : Delete sent reminders older than this many days}';

    protected $description = 'Delete sent reminders older than N days so the table does not grow forever.';

    public function handle(ReminderSync $sync): int
    {
        $deleted = $sync->prune((int) $this->option('days'));

        $this->info("Deleted {$deleted} old reminder(s).");

        return self::SUCCESS;
    }
}
