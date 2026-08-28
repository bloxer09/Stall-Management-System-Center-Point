<?php

namespace App\Services;

use App\Mail\GenericNotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send a transactional HTML email with optional plain text alternative.
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            Mail::to($toEmail, $toName)->send(new GenericNotificationMail($subject, $htmlBody, $textBody));
            return true;
        } catch (\Throwable $e) {
            Log::error('MailService dispatch error to ' . $toEmail . ': ' . $e->getMessage(), [
                'exception' => $e,
                'subject' => $subject,
            ]);
            return false;
        }
    }

    /**
     * Send an OTP verification code via email.
     */
    public function sendOtp(string $toEmail, string $toName, string $otpCode): bool
    {
        $subject = "Your STALLM Verification Code";
        $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background-color: #ffffff;'>
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h2 style='color: #1f2937; margin: 0; font-size: 22px; font-weight: 700;'>Security Verification</h2>
                    <p style='color: #6b7280; font-size: 14px; margin-top: 6px;'>Commercial Stall Management System</p>
                </div>
                <div style='padding: 20px; background-color: #f9fafb; border-radius: 8px; text-align: center; margin-bottom: 24px;'>
                    <p style='color: #4b5563; font-size: 14px; margin: 0 0 12px 0;'>Hello <strong>{$toName}</strong>,</p>
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
