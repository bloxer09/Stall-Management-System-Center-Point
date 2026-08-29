<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Core Admin Account
        User::firstOrCreate(
            ['email' => 'admin@stallm.com'],
            [
                'name' => 'Center Point Admin',
                'phone' => '09171112233',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'email_verified_at' => now(),
            ]
        );

        // 2. Default System Settings
        Setting::updateOrCreate(['setting_key' => 'platform_name'], ['setting_value' => 'Center Point Commercial Complex']);
        Setting::updateOrCreate(['setting_key' => 'platform_address'], ['setting_value' => 'Pagadian City, Zamboanga del Sur']);
        Setting::updateOrCreate(['setting_key' => 'support_email'], ['setting_value' => 'support@stallm.com']);
        Setting::updateOrCreate(['setting_key' => 'support_phone'], ['setting_value' => '09171112233']);
        Setting::updateOrCreate(['setting_key' => 'privacy_policy'], ['setting_value' => "Center Point Commercial Stall Management Privacy Policy\n\n1. Data Collection\nWe collect tenant names, contact numbers, email addresses, and stall lease information required for property administration.\n\n2. Data Protection\nTenant payment transactions and personal credentials are encrypted using industry-standard protocols.\n\n3. Contact Information\nFor privacy inquiries, contact admin@stallm.com."]);
        Setting::updateOrCreate(['setting_key' => 'terms_conditions'], ['setting_value' => "Center Point Commercial Stall Rental Terms and Conditions\n\n1. Lease Agreement\nTenants agree to comply with monthly rental payment schedules and building floor plan regulations.\n\n2. Utility Metering\nSub-metered electricity and water consumption are billed monthly based on official meter readings.\n\n3. Termination\nLease termination or renewal requests must be submitted at least 30 days prior to contract expiration."]);

        $this->command->info('Database successfully initialized with Admin account and System Settings!');
    }
}
