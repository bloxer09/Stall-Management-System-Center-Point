<?php

namespace App\Services;

use App\Config\App;

class PhoneVerificationSender
{
    public function sendRegistrationCode(string $phone, string $code): array
    {
        $smsService = new SmsService();
        $message = sprintf('Your STALLM verification code is: %s', $code);
        $result = $smsService->send($phone, $message);

        if ($result['sent']) {
            return [
                'sent' => true,
                'notice' => $result['notice'] ?? 'Verification SMS sent successfully.',
            ];
        }

        return [
            'sent' => false,
            'notice' => $result['error'] ?? 'SMS delivery failed.',
        ];
    }
}
