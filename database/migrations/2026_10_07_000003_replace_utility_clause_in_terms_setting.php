<?php

use App\Livewire\Admin\SystemSettings;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Utility billing was removed; swap the old default Terms for the new one. Terms an admin has
 * edited are left exactly as they are.
 */
return new class extends Migration
{
    private const OLD_DEFAULT = "Center Point Commercial Stall Rental Terms and Conditions\n\n1. Lease Agreement\nTenants agree to comply with monthly rental payment schedules and building floor plan regulations.\n\n2. Utility Metering\nSub-metered electricity and water consumption are billed monthly based on official meter readings.\n\n3. Termination\nLease termination or renewal requests must be submitted at least 30 days prior to contract expiration.";

    public function up(): void
    {
        if (Setting::get('terms_conditions') === self::OLD_DEFAULT) {
            Setting::set('terms_conditions', SystemSettings::DEFAULT_TERMS);
        }
    }

    public function down(): void
    {
        // Not restored: the old text describes a feature that no longer exists.
    }
};
