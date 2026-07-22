<?php

use App\Services\RegistrationVerification;
use PHPUnit\Framework\TestCase;

class RegistrationVerificationTest extends TestCase
{
    public function testNormalizePhoneSupportsLocalAndInternationalFormats(): void
    {
        $this->assertSame('+639171234567', RegistrationVerification::normalizePhone('0917 123 4567'));
        $this->assertSame('+639171234567', RegistrationVerification::normalizePhone('+63 917-123-4567'));
        $this->assertSame('+14155551212', RegistrationVerification::normalizePhone('+1 (415) 555-1212'));
    }

    public function testNormalizePhoneRejectsInvalidValues(): void
    {
        $this->assertNull(RegistrationVerification::normalizePhone('12345'));
        $this->assertNull(RegistrationVerification::normalizePhone('phone-number'));
    }

    public function testGeneratedCodesCanBeValidated(): void
    {
        $code = RegistrationVerification::generateCode();
        $hash = RegistrationVerification::hashCode($code);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue(RegistrationVerification::codesMatch($code, $hash));
        $this->assertFalse(RegistrationVerification::codesMatch('000000', $hash));
    }
}
