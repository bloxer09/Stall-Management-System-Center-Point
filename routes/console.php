<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\GenerateMonthlyInvoices;
use App\Console\Commands\SendPaymentReminders;
use App\Console\Commands\NotifyExpiringLeases;

Schedule::command('billing:generate-invoices')->monthlyOn(1, '00:00');
Schedule::command('stallm:send-reminders')->dailyAt('08:00');
Schedule::command('stallm:notify-expiring-leases')->dailyAt('07:00');
Schedule::command('stallm:flag-overdue')->dailyAt('08:30');
Schedule::command('stallm:backup')->dailyAt('02:00');
