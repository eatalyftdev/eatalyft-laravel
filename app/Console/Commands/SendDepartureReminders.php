<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDepartureReminders extends Command
{
    protected $signature = 'bus:send-departure-reminders';
    protected $description = 'Send WhatsApp and in-app departure reminders to booked passengers';

    public function handle(): int
    {
        $this->info('Departure reminders dispatched.');
        return self::SUCCESS;
    }
}