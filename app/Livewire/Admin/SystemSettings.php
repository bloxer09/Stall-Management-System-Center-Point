<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SystemSettings extends Component
{
    public string $platform_name = '';
    public string $support_email = '';
    public string $support_phone = '';
    public string $privacy_policy = '';
    public string $terms_conditions = '';

    public function mount(): void
    {
        $this->platform_name = Setting::get('platform_name', 'STALLM Commercial Platform');
        $this->support_email = Setting::get('support_email', 'support@stallm.com');
        $this->support_phone = Setting::get('support_phone', '09171112233');
        $this->privacy_policy = Setting::get('privacy_policy', "Center Point Commercial Stall Management Privacy Policy\n\n1. Data Collection\nWe collect tenant names, contact numbers, email addresses, and stall lease information required for property administration.\n\n2. Data Protection\nTenant payment transactions and personal credentials are encrypted using industry-standard protocols.\n\n3. Contact Information\nFor privacy inquiries, contact admin@stallm.com.");
        $this->terms_conditions = Setting::get('terms_conditions', "Center Point Commercial Stall Rental Terms and Conditions\n\n1. Lease Agreement\nTenants agree to comply with monthly rental payment schedules and building floor plan regulations.\n\n2. Utility Metering\nSub-metered electricity and water consumption are billed monthly based on official meter readings.\n\n3. Termination\nLease termination or renewal requests must be submitted at least 30 days prior to contract expiration.");
    }

    public function saveSettings(): void
    {
        $this->validate([
            'platform_name' => 'required|string|max:255',
            'support_email' => 'required|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'privacy_policy' => 'required|string|min:20',
            'terms_conditions' => 'required|string|min:20',
        ]);

        Setting::set('platform_name', $this->platform_name);
        Setting::set('support_email', $this->support_email);
        Setting::set('support_phone', $this->support_phone);
        Setting::set('privacy_policy', $this->privacy_policy);
        Setting::set('terms_conditions', $this->terms_conditions);

        AuditLog::log(
            Auth::id(),
            "Updated System Settings",
            'Setting',
            1
        );

        session()->flash('message', 'Settings saved successfully.');
        $this->redirect(route('admin.settings'), navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.system-settings');
    }
}
