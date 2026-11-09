<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemSettings;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsWordingTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_DEFAULT = "Center Point Commercial Stall Rental Terms and Conditions\n\n1. Lease Agreement\nTenants agree to comply with monthly rental payment schedules and building floor plan regulations.\n\n2. Utility Metering\nSub-metered electricity and water consumption are billed monthly based on official meter readings.\n\n3. Termination\nLease termination or renewal requests must be submitted at least 30 days prior to contract expiration.";

    private function runMigration(): void
    {
        (include database_path('migrations/2026_10_07_000003_replace_utility_clause_in_terms_setting.php'))->up();
    }

    public function test_default_terms_have_no_utility_clause(): void
    {
        $this->assertStringContainsString("2. Payments\nRent is billed monthly. Invoices not paid by their due date are flagged as overdue.", SystemSettings::DEFAULT_TERMS);
        $this->assertStringNotContainsString('Utility', SystemSettings::DEFAULT_TERMS);
    }

    public function test_migration_replaces_untouched_default(): void
    {
        Setting::set('terms_conditions', self::OLD_DEFAULT);

        $this->runMigration();

        $this->assertSame(SystemSettings::DEFAULT_TERMS, Setting::get('terms_conditions'));
    }

    public function test_migration_keeps_edited_terms(): void
    {
        Setting::set('terms_conditions', 'Our own terms.');

        $this->runMigration();

        $this->assertSame('Our own terms.', Setting::get('terms_conditions'));
    }

    public function test_public_terms_page_falls_back_to_default(): void
    {
        $this->get('/terms-and-conditions')->assertOk()->assertSee('2. Payments')->assertDontSee('Utility');
    }
}
