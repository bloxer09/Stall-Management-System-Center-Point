<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\MailjetMailer;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class NotifyExpiringLeases extends Command
{
    protected $signature = 'stallm:notify-expiring-leases';
    protected $description = 'Dispatch notifications via email, SMS, and in-app bell for leases expiring in 60 and 30 days.';

    public function handle(MailjetMailer $mailjet, SmsService $smsService): int
    {
        $today = Carbon::today();
        $target60 = $today->copy()->addDays(60)->toDateString();
        $target30 = $today->copy()->addDays(30)->toDateString();

        $expiringMappings = StallMapping::with(['user', 'stall'])
            ->where('is_active', true)
            ->where(function ($q) use ($target60, $target30) {
                $q->whereDate('end_date', $target60)
                  ->orWhereDate('end_date', $target30);
            })
            ->get();

        $count = 0;
        $admins = User::where('role', 'admin')->get();

        foreach ($expiringMappings as $mapping) {
            $tenant = $mapping->user;
            $stall = $mapping->stall;
            if (!$tenant || !$stall) {
                continue;
            }

            $daysLeft = Carbon::parse($mapping->end_date)->diffInDays($today);
            $subject = "Lease Expiry Warning: Stall {$stall->stall_number} expires in {$daysLeft} days";
            $messageText = "Hello {$tenant->name}, your lease for Stall {$stall->stall_number} is scheduled to expire on {$mapping->end_date->format('Y-m-d')} ({$daysLeft} days remaining). Please log in to your portal to submit a renewal request.";

            // 1. In-App Notification to Tenant
            Notification::create([
                'user_id' => $tenant->id,
                'title' => "Lease Expiring Soon ({$daysLeft} Days)",
                'message' => "Your lease for Stall {$stall->stall_number} expires on {$mapping->end_date->format('Y-m-d')}.",
                'type' => 'warning',
                'is_read' => false,
            ]);

            // 2. In-App Notification to Admins
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => "Tenant Lease Expiring ({$daysLeft} Days)",
                    'message' => "Tenant {$tenant->name} (Stall {$stall->stall_number}) lease expires on {$mapping->end_date->format('Y-m-d')}.",
                    'type' => 'warning',
                    'is_read' => false,
                ]);
            }

            // 3. Email via Mailjet
            try {
                $mailjet->sendEmail($tenant->email, $tenant->name, $subject, "<p>{$messageText}</p>");
            } catch (\Exception $e) {
                Log::warning("Failed to send lease expiry email to {$tenant->email}: " . $e->getMessage());
            }

            // 4. SMS via Semaphore
            if ($tenant->phone) {
                try {
                    $smsService->sendSms($tenant->phone, $messageText);
                } catch (\Exception $e) {
                    Log::warning("Failed to send lease expiry SMS to {$tenant->phone}: " . $e->getMessage());
                }
            }

            $count++;
        }

        $this->info("Dispatched expiry notifications for {$count} lease(s).");
        return Command::SUCCESS;
    }
}
