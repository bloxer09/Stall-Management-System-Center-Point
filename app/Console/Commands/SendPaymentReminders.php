<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Payment;
use App\Services\MailjetMailer;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'stallm:send-reminders';
    protected $description = 'Send multi-stage payment reminders (7 days, 3 days, today) via email and SMS';

    public function handle(MailjetMailer $mailer, SmsService $smsService): int
    {
        $this->info('Processing payment reminders...');

        $today = Carbon::today();
        $days7 = $today->copy()->addDays(7)->toDateString();
        $days3 = $today->copy()->addDays(3)->toDateString();
        $todayStr = $today->toDateString();

        $pendingPayments = Payment::with(['user', 'stall'])
            ->where('status', 'pending')
            ->whereIn('due_date', [$days7, $days3, $todayStr])
            ->get();

        $sentCount = 0;

        foreach ($pendingPayments as $payment) {
            $user = $payment->user;
            $stall = $payment->stall;
            if (!$user) continue;

            $daysLeft = Carbon::parse($payment->due_date)->diffInDays($today);
            $dueText = $daysLeft == 0 ? "is due TODAY" : "is due in {$daysLeft} days ({$payment->due_date})";

            $title = "Payment Reminder: Stall {$stall->stall_number}";
            $msg = "Hello {$user->name}, your invoice of ₱" . number_format($payment->amount, 2) . " for Stall {$stall->stall_number} {$dueText}.";

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
