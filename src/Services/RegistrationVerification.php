<?php

namespace App\Services;

final class RegistrationVerification
{
    public const CODE_LENGTH = 6;

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', trim($code));
    }

    public static function codesMatch(string $submittedCode, string $expectedHash): bool
    {
        $submittedCode = trim($submittedCode);
        $expectedHash = trim($expectedHash);

        if ($submittedCode === '' || $expectedHash === '') {
            return false;
        }

        return hash_equals($expectedHash, self::hashCode($submittedCode));
    }

    public static function normalizePhone(string $input): ?string
    {
        $value = trim($input);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[\s\-\(\)]+/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        if (substr_count($value, '+') > 1 || (strpos($value, '+') !== false && strpos($value, '+') !== 0)) {
            return null;
        }

        if (strpos($value, '00') === 0) {
            $value = '+' . substr($value, 2);
        }

        if (strpos($value, '+') === 0) {
            $digits = substr($value, 1);
            if ($digits === '' || !ctype_digit($digits)) {
                return null;
            }

            return self::normalizeDigitString($digits);
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11 && strpos($digits, '0') === 0) {
            return '+63' . substr($digits, 1);
        }

        if (strlen($digits) === 12 && strpos($digits, '63') === 0) {
            return '+' . $digits;
        }

        return self::normalizeDigitString($digits);
    }

    public static function formatPhoneForDisplay(string $phone): string
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === null) {
            return trim($phone);
        }

        $digits = substr($normalized, 1);
        if (strlen($digits) === 12 && strpos($digits, '63') === 0) {
            return '+63 ' . substr($digits, 2, 3) . ' ' . substr($digits, 5, 3) . ' ' . substr($digits, 8, 4);
        }

        return $normalized;
    }

    public static function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || strpos($email, '@') === false) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visibleLocal = substr($local, 0, min(2, max(1, strlen($local))));
        $maskedLocal = $visibleLocal . str_repeat('*', max(1, strlen($local) - strlen($visibleLocal)));

        $domainParts = explode('.', $domain, 2);
        $domainName = $domainParts[0] ?? '';
        $suffix = isset($domainParts[1]) ? '.' . $domainParts[1] : '';

        if ($domainName === '') {
            return $maskedLocal . '@' . $domain;
        }

        $maskedDomain = substr($domainName, 0, 1) . str_repeat('*', max(1, strlen($domainName) - 1));

        return $maskedLocal . '@' . $maskedDomain . $suffix;
    }

    public static function maskPhone(string $phone): string
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === null) {
            return trim($phone);
        }

        $digits = substr($normalized, 1);
        if (strlen($digits) <= 4) {
            return '+' . $digits;
        }

        $prefixLength = min(3, max(1, strlen($digits) - 4));
        $visiblePrefix = substr($digits, 0, $prefixLength);
        $visibleSuffix = substr($digits, -4);
        $maskedMiddle = str_repeat('*', max(1, strlen($digits) - $prefixLength - 4));

        return '+' . $visiblePrefix . $maskedMiddle . $visibleSuffix;
    }

    public static function isExpired(array $pendingRegistration): bool
    {
        $expiresAt = (int) ($pendingRegistration['expires_at'] ?? 0);
        return $expiresAt > 0 && $expiresAt < time();
    }

    private static function normalizeDigitString(string $digits): ?string
    {
        if ($digits === '' || !ctype_digit($digits)) {
            return null;
        }

        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            return null;
        }

        return '+' . $digits;
    }
}
