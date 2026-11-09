<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemSettings;
use App\Livewire\Auth\AuthModals;
use App\Livewire\Portal\StallBrowser;
use App\Livewire\Profile\ProfileModal;
use App\Models\Setting;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use App\Rules\PhilippineMobileNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    public static function invalidNumbers(): array
    {
        return array_map(fn ($number) => [$number], [
            'phone-number', '0917!234567', '0917123456', '091712345678',
            '08171234567', '+12125551234', '+63639171234567', '0917 123 4567',
        ]);
    }

    #[DataProvider('invalidNumbers')]
    public function test_all_phone_forms_reject_invalid_numbers_without_writes(string $number): void
    {
        Mail::fake();
        $tenant = User::factory()->create(['role' => 'user', 'phone' => '09171234567']);
        Livewire::actingAs($tenant)->test(ProfileModal::class)->call('openModal')
            ->set('phone', $number)->call('updateProfile')->assertHasErrors('phone');
        $this->assertSame('09171234567', $tenant->fresh()->phone);

        $floor = StallLocation::create(['name' => 'Ground', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'P-1', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'available']);
        Livewire::test(StallBrowser::class)->call('selectStall', $stall->id)->call('openApply')
            ->set('form.applicant_phone', $number)->set('form.business_name', 'Shop')
            ->set('form.business_type', 'Retail')->call('submitApplication')
            ->assertHasErrors('form.applicant_phone');
        $this->assertDatabaseCount('stall_applications', 0);

        $admin = User::factory()->create(['role' => 'admin']);
        Setting::set('support_phone', '09171112233');
        Livewire::actingAs($admin)->test(SystemSettings::class)->set('support_phone', $number)
            ->call('saveSettings')->assertHasErrors('support_phone');
        $this->assertSame('09171112233', Setting::get('support_phone'));

        Auth::logout();
        Livewire::test(AuthModals::class)->set('regName', 'Applicant')->set('regEmail', 'new@example.test')
            ->set('regPhone', $number)->set('regPassword', 'StrongPass123!')
            ->set('regPasswordConfirmation', 'StrongPass123!')->set('terms', true)
            ->call('processRegister')->assertHasErrors('regPhone');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
        Mail::assertNothingSent();
    }

    public function test_rule_accepts_domestic_and_international_mobile_formats(): void
    {
        foreach (['9171234567', '09171234567', '639171234567', '+639171234567'] as $number) {
            $this->assertFalse(Validator::make(['phone' => $number], ['phone' => new PhilippineMobileNumber])->fails(), $number);
        }
    }

    public function test_optional_fields_can_be_cleared_but_two_factor_phone_is_required(): void
    {
        $tenant = User::factory()->create(['role' => 'user', 'phone' => '09171234567']);
        Livewire::actingAs($tenant)->test(ProfileModal::class)->set('phone', '')
            ->call('updateProfile')->assertHasNoErrors();
        $this->assertNull($tenant->fresh()->phone);
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09171234567', 'two_factor_enabled' => true]);
        session(['two_factor_verified' => true]);
        Livewire::actingAs($admin)->test(ProfileModal::class)->set('phone', '')
            ->call('updateProfile')->assertHasErrors(['phone' => 'required']);
        Livewire::test(SystemSettings::class)->set('support_phone', '')->call('saveSettings')->assertHasNoErrors();
    }
}
