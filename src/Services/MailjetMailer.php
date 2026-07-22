<?php

namespace App\Services;

use App\Config\App;
use Mailjet\Resources;

class MailjetMailer {
    private string $apiKey;
    private string $apiSecret;
    private string $fromEmail;
    private string $fromName;

    public function __construct() {
        $this->apiKey = (string)($_ENV['MAILJET_API_KEY'] ?? '');
        $this->apiSecret = (string)($_ENV['MAILJET_API_SECRET'] ?? '');
        $this->fromEmail = (string)($_ENV['MAILJET_FROM_EMAIL'] ?? 'no-reply@yourdomain.com');
        $this->fromName = (string)($_ENV['MAILJET_FROM_NAME'] ?? App::name());
    }

    private function isConfigured(): bool {
        return $this->apiKey !== '' && $this->apiSecret !== '' && $this->apiKey !== 'YOUR_MAILJET_API_KEY';
    }

    private function client(): ?\Mailjet\Client {
        if (!$this->isConfigured()) {
            return null;
        }

        return new \Mailjet\Client($this->apiKey, $this->apiSecret, true, ['version' => 'v3.1']);
    }

    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool {
        $client = $this->client();
        if (!$client) {
            error_log('Mailjet not configured for password reset email.');
            return false;
        }
        $appName = App::name();

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => 'Reset Your Password - ' . $appName,
                'TextPart' => "Hello {$toName},\n\nYou requested a password reset. Use this link (valid for 1 hour):\n{$resetLink}\n\nIf this was not you, please ignore this message.",
                'HTMLPart' => $this->buildPasswordResetHtml($toName, $resetLink),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response->success();
    }

    public function sendRegistrationVerificationEmail(string $toEmail, string $toName, string $code): array {
        $client = $this->client();
        $appName = App::name();

        if (!$client) {
            error_log(sprintf('Registration email verification code for %s: %s', $toEmail, $code));

            return [
                'sent' => true,
                'notice' => 'Email verification is using the PHP error log because Mailjet is not configured.',
            ];
        }

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => 'Your Verification Code - ' . $appName,
                'TextPart' => "Hello {$toName},\n\nUse this verification code to continue creating your {$appName} account:\n{$code}\n\nThis code expires in 10 minutes.",
                'HTMLPart' => $this->buildRegistrationVerificationHtml($toName, $code),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        if (!$response->success()) {
            return [
                'sent' => false,
                'notice' => 'Unable to send the email verification code right now.',
            ];
        }

        return [
            'sent' => true,
            'notice' => null,
        ];
    }

    public function sendPaymentReminderEmail(string $toEmail, string $toName, string $stallNumber, float $amount, ?string $dueDate = null): bool {
        $client = $this->client();
        if (!$client) {
            error_log('Mailjet not configured for payment reminder email.');
            return false;
        }

        $dueLine = $dueDate ? "Due date: {$dueDate}" : 'Due soon';

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => 'Payment Reminder - Stall ' . $stallNumber,
                'TextPart' => "Hello {$toName},\n\nThis is a reminder that your stall rent payment is due.\nStall: {$stallNumber}\nAmount: PHP " . number_format($amount, 2) . "\n{$dueLine}\n\nPlease log in to complete your payment.",
                'HTMLPart' => $this->buildPaymentReminderHtml($toName, $stallNumber, $amount, $dueDate),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response->success();
    }

    private function buildPasswordResetHtml(string $toName, string $resetLink): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $escapedAppName = htmlspecialchars(App::name(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#2563eb;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">Password Reset</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>You requested a password reset for your {$escapedAppName} account. This link expires in 1 hour.</p>
      <p><a href="{$escapedLink}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;">Reset Password</a></p>
      <p>If the button does not work, copy and paste this URL:</p>
      <p><a href="{$escapedLink}">{$escapedLink}</a></p>
      <p>If you did not request this, you can ignore this email.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function buildPaymentReminderHtml(string $toName, string $stallNumber, float $amount, ?string $dueDate): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedStall = htmlspecialchars($stallNumber, ENT_QUOTES, 'UTF-8');
        $escapedDueDate = $dueDate ? htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') : 'Soon';
        $amountText = number_format($amount, 2);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#f59e0b;padding:20px 24px;color:#1f2937;font-size:20px;font-weight:700;">Payment Reminder</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>This is a reminder for your upcoming stall payment.</p>
      <ul>
        <li><strong>Stall:</strong> {$escapedStall}</li>
        <li><strong>Amount:</strong> PHP {$amountText}</li>
        <li><strong>Due Date:</strong> {$escapedDueDate}</li>
      </ul>
      <p>Please log in to your account to complete payment before the due date.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function buildRegistrationVerificationHtml(string $toName, string $code): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $escapedAppName = htmlspecialchars(App::name(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#16a34a;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">Verify Your Email</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>Use the code below to continue creating your {$escapedAppName} account. This code expires in 10 minutes.</p>
      <p style="display:inline-block;background:#ecfdf5;border:1px solid #86efac;border-radius:8px;padding:12px 16px;font-size:28px;font-weight:700;letter-spacing:6px;color:#166534;">{$escapedCode}</p>
      <p>If you did not start this registration, you can ignore this email.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    public function sendNewTicketNotificationToStaff(string $toEmail, string $toName, string $ticketId, string $tenantName, string $stallNumber, string $issue): bool {
        $client = $this->client();
        if (!$client) {
            error_log("Mailjet not configured. Staff new ticket email alert for #{$ticketId}: Tenant {$tenantName}, Stall {$stallNumber}");
            return false;
        }

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => "New Support Ticket #{$ticketId} - Stall {$stallNumber}",
                'TextPart' => "Hello {$toName},\n\nA new support ticket has been created.\n\nTicket ID: #{$ticketId}\nTenant: {$tenantName}\nStall: {$stallNumber}\nIssue: {$issue}\n\nPlease log in to view and reply to this ticket.",
                'HTMLPart' => $this->buildNewTicketStaffHtml($toName, $ticketId, $tenantName, $stallNumber, $issue),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response->success();
    }

    public function sendTicketReceiptToTenant(string $toEmail, string $toName, string $ticketId, string $stallNumber, string $issue): bool {
        $client = $this->client();
        if (!$client) {
            error_log("Mailjet not configured. Tenant ticket confirmation email for #{$ticketId}: Stall {$stallNumber}");
            return false;
        }

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => "Support Ticket Submitted #{$ticketId}",
                'TextPart' => "Hello {$toName},\n\nYour support ticket has been successfully submitted.\n\nTicket ID: #{$ticketId}\nStall: {$stallNumber}\nIssue: {$issue}\n\nOur team will review your request and reply shortly.",
                'HTMLPart' => $this->buildTicketReceiptTenantHtml($toName, $ticketId, $stallNumber, $issue),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response->success();
    }

    public function sendTicketReplyNotification(string $toEmail, string $toName, string $ticketId, string $senderName, string $messageSnippet): bool {
        $client = $this->client();
        if (!$client) {
            error_log("Mailjet not configured. Ticket #{$ticketId} reply email notification from {$senderName} to {$toName}");
            return false;
        }

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => "New Reply on Ticket #{$ticketId}",
                'TextPart' => "Hello {$toName},\n\n{$senderName} has posted a new reply on Ticket #{$ticketId}:\n\n\"{$messageSnippet}\"\n\nPlease log in to view the full conversation.",
                'HTMLPart' => $this->buildTicketReplyHtml($toName, $ticketId, $senderName, $messageSnippet),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response->success();
    }

    public function sendTicketStatusUpdateNotification(string $toEmail, string $toName, string $ticketId, string $newStatus): bool {
        $client = $this->client();
        if (!$client) {
            error_log("Mailjet not configured. Ticket #{$ticketId} status update email to {$toName} (Status: {$newStatus})");
            return false;
        }

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => "Ticket #{$ticketId} Status Updated",
                'TextPart' => "Hello {$toName},\n\nYour support ticket #{$ticketId} status has been updated to: " . strtoupper($newStatus) . ".\n\nPlease log in to view details.",
                'HTMLPart' => $this->buildTicketStatusUpdateHtml($toName, $ticketId, $newStatus),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response->success();
    }

    private function buildNewTicketStaffHtml(string $toName, string $ticketId, string $tenantName, string $stallNumber, string $issue): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedTenant = htmlspecialchars($tenantName, ENT_QUOTES, 'UTF-8');
        $escapedStall = htmlspecialchars($stallNumber, ENT_QUOTES, 'UTF-8');
        $escapedIssue = nl2br(htmlspecialchars($issue, ENT_QUOTES, 'UTF-8'));
        $escapedTicketId = htmlspecialchars($ticketId, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#2563eb;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">New Support Ticket</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>A new support ticket has been submitted by a tenant.</p>
      <div style="background:#f3f4f6;padding:16px;border-radius:8px;margin:16px 0;">
        <p style="margin:0 0 8px 0;"><strong>Ticket ID:</strong> #{$escapedTicketId}</p>
        <p style="margin:0 0 8px 0;"><strong>Tenant:</strong> {$escapedTenant}</p>
        <p style="margin:0 0 8px 0;"><strong>Stall:</strong> Stall {$escapedStall}</p>
        <p style="margin:0;"><strong>Issue details:</strong><br>{$escapedIssue}</p>
      </div>
      <p>Please log in to your staff portal to address this issue.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function buildTicketReceiptTenantHtml(string $toName, string $ticketId, string $stallNumber, string $issue): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedStall = htmlspecialchars($stallNumber, ENT_QUOTES, 'UTF-8');
        $escapedIssue = nl2br(htmlspecialchars($issue, ENT_QUOTES, 'UTF-8'));
        $escapedTicketId = htmlspecialchars($ticketId, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#10b981;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">Ticket Submitted</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>Your support ticket has been received by our office. Below are the ticket details:</p>
      <div style="background:#f3f4f6;padding:16px;border-radius:8px;margin:16px 0;">
        <p style="margin:0 0 8px 0;"><strong>Ticket ID:</strong> #{$escapedTicketId}</p>
        <p style="margin:0 0 8px 0;"><strong>Stall:</strong> Stall {$escapedStall}</p>
        <p style="margin:0;"><strong>Issue details:</strong><br>{$escapedIssue}</p>
      </div>
      <p>A staff member will review your request and get back to you soon.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function buildTicketReplyHtml(string $toName, string $ticketId, string $senderName, string $messageSnippet): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedSender = htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8');
        $escapedMsg = nl2br(htmlspecialchars($messageSnippet, ENT_QUOTES, 'UTF-8'));
        $escapedTicketId = htmlspecialchars($ticketId, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#2563eb;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">New Ticket Reply</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>There is a new reply on Ticket <strong>#{$escapedTicketId}</strong> from <strong>{$escapedSender}</strong>:</p>
      <div style="background:#f3f4f6;padding:16px;border-radius:8px;margin:16px 0;font-style:italic;color:#374151;">
        "{$escapedMsg}"
      </div>
      <p>Please log in to your account dashboard to view and reply to the thread.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function buildTicketStatusUpdateHtml(string $toName, string $ticketId, string $newStatus): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedTicketId = htmlspecialchars($ticketId, ENT_QUOTES, 'UTF-8');
        $escapedStatus = strtoupper(str_replace('_', ' ', $newStatus));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#4b5563;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">Ticket Status Updated</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>The status of your support ticket <strong>#{$escapedTicketId}</strong> has been updated to:</p>
      <div style="background:#f3f4f6;padding:12px 16px;border-radius:8px;margin:16px 0;display:inline-block;font-weight:700;color:#111827;font-size:16px;">
        {$escapedStatus}
      </div>
      <p>Please log in to your dashboard to review any comments or track resolution progress.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    public function sendLoginOtpEmail(string $toEmail, string $toName, string $code): bool {
        $client = $this->client();
        $appName = App::name();

        if (!$client) {
            error_log(sprintf('Login OTP verification code for %s: %s', $toEmail, $code));
            return true;
        }

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => 'Your Login Verification Code - ' . $appName,
                'TextPart' => "Hello {$toName},\n\nUse this verification code to complete your login to {$appName}:\n{$code}\n\nThis code expires in 10 minutes.",
                'HTMLPart' => $this->buildLoginOtpHtml($toName, $code),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response->success();
    }

    private function buildLoginOtpHtml(string $toName, string $code): string {
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $escapedAppName = htmlspecialchars(App::name(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#2563eb;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">Login Verification</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>Use the 6-digit verification code below to complete your login to your {$escapedAppName} account. This code is valid for 10 minutes.</p>
      <p style="display:inline-block;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:28px;font-weight:700;letter-spacing:6px;color:#1d4ed8;">{$escapedCode}</p>
      <p>If you did not attempt to sign in to your account, please ignore this message or contact support.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    public function sendPasswordChangeVerificationEmail(string $toEmail, string $toName, string $code): array {
        $client = $this->client();
        $appName = App::name();

        if (!$client) {
            error_log(sprintf('Password change verification code for %s: %s', $toEmail, $code));
            return [
                'sent' => true,
                'notice' => 'Email verification is using the PHP error log because Mailjet is not configured.',
            ];
        }

        $body = [
            'Messages' => [[
                'From' => [
                    'Email' => $this->fromEmail,
                    'Name' => $this->fromName,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName,
                ]],
                'Subject' => 'Password Change Verification Code - ' . $appName,
                'TextPart' => "Hello {$toName},\n\nUse this verification code to verify your request to change your password:\n{$code}\n\nThis code expires in 10 minutes.",
                'HTMLPart' => $this->buildPasswordChangeVerificationHtml($toName, $code),
            ]],
        ];

        $response = $client->post(Resources::$Email, ['body' => $body]);
        if (!$response->success()) {
            return [
                'sent' => false,
                'notice' => 'Unable to send the email verification code right now.',
            ];
        }

        return [
            'sent' => true,
            'notice' => null,
        ];
    }

    private function buildPasswordChangeVerificationHtml(string $toName, string $code): string {
        $appName = App::name();
        $escapedName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $escapedAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#dc2626;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">Password Change Request</div>
    <div style="padding:24px;color:#1f2937;line-height:1.6;">
      <p>Hello <strong>{$escapedName}</strong>,</p>
      <p>We received a request to change your password on {$escapedAppName}. Use the verification code below to authorize this change. This code is valid for 10 minutes.</p>
      <p style="display:inline-block;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;font-size:28px;font-weight:700;letter-spacing:6px;color:#dc2626;">{$escapedCode}</p>
      <p>If you did not make this request, please change your password immediately or contact support.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }
}
