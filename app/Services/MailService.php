<?php

namespace App\Services;

use App\Mail\GenericNotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Determine if the current environment/request is local development.
     */
    public function isLocal(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        if (app()->environment('local', 'testing')) {
            return true;
        }

        $host = '';
        if (app()->runningInConsole()) {
            $appUrl = config('app.url', 'http://localhost');
            $host = parse_url($appUrl, PHP_URL_HOST) ?? '';
        } else {
            $host = request()->getHost() ?? '';
        }

        return in_array($host, ['localhost', '127.0.0.1', '::1'])
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');
    }

    /**
     * Get the active mailer based on environment.
     */
    public function getActiveMailer(): string
    {
        if ($this->isLocal()) {
            return 'mailpit';
        }

        // In Production: if Mailjet credentials exist, route via mailjet
        if (!empty(config('services.mailjet.api_key')) || !empty(config('mail.mailers.mailjet.username'))) {
            return 'mailjet';
        }

        return config('mail.default', 'smtp');
    }

    /**
     * Send a transactional HTML email with optional plain text alternative.
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $mailer = $this->getActiveMailer();

        try {
            Mail::mailer($mailer)->to($toEmail, $toName)->send(new GenericNotificationMail($subject, $htmlBody, $textBody));
            return true;
        } catch (\Throwable $e) {
            Log::error("MailService dispatch error [Mailer: {$mailer}] to {$toEmail}: " . $e->getMessage(), [
                'exception' => $e,
                'subject' => $subject,
                'mailer' => $mailer,
            ]);

            // In local development, if Mailpit/SMTP is down, fall back to log driver
            if ($this->isLocal()) {
                try {
                    Mail::mailer('log')->to($toEmail, $toName)->send(new GenericNotificationMail($subject, $htmlBody, $textBody));
                    Log::info("MailService local fallback: email successfully captured in laravel.log for {$toEmail}");
                    return true;
                } catch (\Throwable $logEx) {
                    Log::error("MailService log fallback failed: " . $logEx->getMessage());
                }
            } elseif ($mailer !== 'smtp') {
                // In production: if primary mailer fails, attempt fallback to default SMTP
                try {
                    Mail::mailer('smtp')->to($toEmail, $toName)->send(new GenericNotificationMail($subject, $htmlBody, $textBody));
                    return true;
                } catch (\Throwable $fallbackEx) {
                    Log::error("MailService fallback dispatch also failed to {$toEmail}: " . $fallbackEx->getMessage());
                }
            }

            return false;
        }
    }

    /**
     * Send an OTP verification code via email.
     */
    public function sendOtp(string $toEmail, string $toName, string $otpCode): bool
    {
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $subject = "Your STALLM Verification Code";
        $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background-color: #ffffff;'>
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h2 style='color: #1f2937; margin: 0; font-size: 22px; font-weight: 700;'>Security Verification</h2>
                    <p style='color: #6b7280; font-size: 14px; margin-top: 6px;'>Commercial Stall Management System</p>
                </div>
                <div style='padding: 20px; background-color: #f9fafb; border-radius: 8px; text-align: center; margin-bottom: 24px;'>
                    <p style='color: #4b5563; font-size: 14px; margin: 0 0 12px 0;'>Hello <strong>{$safeName}</strong>,</p>
                    <p style='color: #4b5563; font-size: 14px; margin: 0 0 16px 0;'>Use the one-time verification code below to verify your account:</p>
                    <div style='display: inline-block; padding: 12px 28px; background-color: #4f46e5; color: #ffffff; font-size: 32px; font-weight: 800; letter-spacing: 6px; border-radius: 8px;'>
                        {$otpCode}
                    </div>
                    <p style='color: #9ca3af; font-size: 12px; margin-top: 16px; margin-bottom: 0;'>This code expires in 10 minutes. For your security, do not share it with anyone.</p>
                </div>
                <p style='color: #9ca3af; font-size: 12px; text-align: center; margin: 0;'>If you did not request this code, please disregard this email.</p>
            </div>
        ";

        return $this->sendEmail($toEmail, $toName, $subject, $htmlBody);
    }
}
