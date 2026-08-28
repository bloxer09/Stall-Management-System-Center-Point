<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Notification;
use App\Services\MailService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'stallm:send-reminders';
    protected $description = 'Send multi-stage payment reminders (7 days, 3 days, today) via email and SMS';

    public function handle(MailService $mailer, SmsService $smsService): int
    {
        $this->info('Processing payment reminders...');

        $today = Carbon::today();
        $days7 = $today->copy()->addDays(7)->toDateString();
        $days3 = $today->copy()->addDays(3)->toDateString();
        $todayStr = $today->toDateString();

        $pendingInvoices = Invoice::with(['user', 'stall'])
            ->where('status', 'pending')
            ->where(function ($q) use ($days7, $days3, $todayStr) {
                $q->whereDate('due_date', $days7)
                  ->orWhereDate('due_date', $days3)
                  ->orWhereDate('due_date', $todayStr);
            })
            ->get();

        $sentCount = 0;

        foreach ($pendingInvoices as $invoice) {
            $user = $invoice->user;
            $stall = $invoice->stall;
            if (!$user) continue;

            $daysLeft = Carbon::parse($invoice->due_date)->diffInDays($today);
            $dueText = $daysLeft == 0 ? "is due TODAY" : "is due in {$daysLeft} days ({$invoice->due_date})";

            $title = "Payment Reminder: Stall {$stall->stall_number}";
            $msg = "Hello {$user->name}, your invoice of ₱" . number_format($invoice->total_amount, 2) . " for Stall {$stall->stall_number} {$dueText}.";

            // In-app notification
            Notification::send($user->id, 'payment_reminder', $title, $msg, route('user.payments'));

            // Email
            $mailer->sendEmail($user->email, $user->name, $title, "<p>{$msg}</p><p><a href='" . route('user.payments') . "'>Pay Now</a></p>");

            // SMS if phone exists
            if (!empty($user->phone)) {
                $smsService->sendSms($user->phone, "STALLM: {$msg} Log in to pay.");
            }

            $sentCount++;
        }

        $this->info("Dispatched {$sentCount} payment reminders.");
        return Command::SUCCESS;
    }
}
