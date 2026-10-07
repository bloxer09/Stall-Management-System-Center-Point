<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemSettings;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SmsSwitchTest extends TestCase
{
    use RefreshDatabase;

    /** SmsService with the real sendSms() but the delivery step stubbed. */
    private function smsSpy(): SmsService
    {
        $sms = Mockery::mock(SmsService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->instance(SmsService::class, $sms);

        return $sms;
    }

    public function test_non_essential_sms_skipped_when_disabled(): void
    {
        Setting::set('sms_enabled', '0');
        $sms = $this->smsSpy();
        $sms->shouldNotReceive('captureLocalSms');

        $this->assertFalse($sms->sendSms('09171234567', 'Your invoice is due.'));
    }

    public function test_non_essential_sms_sent_when_enabled(): void
    {
        $sms = $this->smsSpy();
        $sms->shouldReceive('captureLocalSms')->once()->andReturn(true);

        $this->assertTrue($sms->sendSms('09171234567', 'Your invoice is due.'));
    }

    public function test_two_factor_sms_sent_when_sms_disabled(): void
    {
        Setting::set('sms_enabled', '0');
        $sms = $this->smsSpy();
        $sms->shouldReceive('captureLocalSms')->once()->andReturn(true);
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09171234321']);

        $this->assertTrue(app(TwoFactorService::class)->sendCode($admin));
    }

    public function test_toggle_saved_and_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(SystemSettings::class)
            ->assertSet('sms_enabled', true)
            ->assertSee('Admin two-factor codes are always sent.')
            ->set('sms_enabled', false)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertSame('0', Setting::get('sms_enabled'));
        $this->assertNotNull(AuditLog::where('action', 'Turned SMS notifications off')->first());

        Livewire::actingAs($admin)->test(SystemSettings::class)->call('saveSettings');
        $this->assertSame(1, AuditLog::where('action', 'like', 'Turned SMS notifications%')->count());
    }
}
