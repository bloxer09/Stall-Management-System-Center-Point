<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Notification;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\MailService;
use App\Services\SmsService;
use Illuminate\Console\Command;

class FlagOverdueInvoices extends Command
{
    protected $signature = 'stallm:flag-overdue';

    protected $description = 'Mark leases with overdue invoices as past due and notify tenants and admins once per invoice';

    public function handle(MailService $mailer, SmsService $sms): int
    {
        // overdue_notified_at makes this safe to run any number of times a day.
        $invoices = Invoice::overdue()
            ->whereNull('overdue_notified_at')
            ->with(['user', 'stall'])
            ->get();

        foreach ($invoices as $invoice) {
            StallMapping::where('user_id', $invoice->user_id)
                ->where('stall_id', $invoice->stall_id)
                ->where('is_active', true)
                ->update(['payment_status' => 'past_due']);

            $tenant = $invoice->user;
            if ($tenant) {
                $message = "Your invoice {$invoice->invoice_number} for Stall {$invoice->stall?->stall_number} was due on "
                    . $invoice->due_date->format('M d, Y') . ' and is now overdue. Please pay as soon as possible.';

                Notification::send($tenant->id, 'warning', 'Invoice overdue', $message, route('user.payments'));
                $mailer->sendEmail($tenant->email, $tenant->name, 'Your invoice is overdue', '<p>Hello ' . e($tenant->name) . ',</p><p>' . e($message) . '</p>', $message);
                if ($tenant->phone) {
                    $sms->sendSms($tenant->phone, "STALLM: {$message}");
                }
            }

            $invoice->forceFill(['overdue_notified_at' => now()])->save();
        }

        $count = $invoices->count();
        if ($count > 0) {
            foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
                Notification::send($adminId, 'warning', 'Overdue invoices', "{$count} invoice(s) became overdue today", url('/admin/payments?status=overdue'));
            }
        }

        $this->info("Flagged {$count} overdue invoice(s).");

        return self::SUCCESS;
    }
}
