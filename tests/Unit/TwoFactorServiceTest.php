<?php

namespace Tests\Unit;

use App\Models\TwoFactorCode;
use App\Models\User;
use App\Services\SmsService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'phone' => '09171234321']);
        $this->mock(SmsService::class)
            ->shouldReceive('sendSms')
            ->andReturnUsing(function (string $number, string $message) {
                $this->sent[] = $message;
                return true;
            });
    }

    private function service(): TwoFactorService
    {
        return app(TwoFactorService::class);
    }

    private function lastCode(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sent), $m);

        return $m[1];
    }

    public function test_send_stores_hash_not_plain_code(): void
    {
        $this->assertTrue($this->service()->sendCode($this->admin));

        $code = $this->lastCode();
        $row = TwoFactorCode::where('user_id', $this->admin->id)->sole();
        $this->assertNotSame($code, $row->code);
        $this->assertSame(hash('sha256', $code), $row->code);
        $this->assertSame("Your STALLM verification code is {$code}. It expires in 10 minutes.", end($this->sent));
    }

    public function test_verify_accepts_correct_code_once(): void
    {
        $this->service()->sendCode($this->admin);
        $code = $this->lastCode();

        $this->assertTrue($this->service()->verify($this->admin, $code));
        $this->assertFalse($this->service()->verify($this->admin, $code));
    }

    public function test_expired_code_fails(): void
    {
        $this->service()->sendCode($this->admin);
        $code = $this->lastCode();

        $this->travel(11)->minutes();

        $this->assertFalse($this->service()->verify($this->admin, $code));
    }

    public function test_new_code_invalidates_old(): void
    {
        $this->service()->sendCode($this->admin);
        $old = $this->lastCode();

        $this->travel(61)->seconds();
        $this->service()->sendCode($this->admin);
        $new = $this->lastCode();

        if ($old === $new) {
            $this->markTestSkipped('Random codes collided; rerun.');
        }
        $this->assertFalse($this->service()->verify($this->admin, $old));
        $this->assertTrue($this->service()->verify($this->admin, $new));
    }

    public function test_resend_throttled_for_60_seconds(): void
    {
        $this->assertTrue($this->service()->sendCode($this->admin));
        $this->assertFalse($this->service()->sendCode($this->admin));

        $this->assertCount(1, $this->sent);
        $this->assertGreaterThan(0, $this->service()->secondsUntilResend($this->admin));
    }

    public function test_five_wrong_attempts_block_even_correct_code(): void
    {
        $this->service()->sendCode($this->admin);
        $code = $this->lastCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($this->service()->verify($this->admin, $wrong));
        }

        $this->assertSame(0, $this->service()->attemptsRemaining($this->admin));
        $this->assertFalse($this->service()->verify($this->admin, $code));
    }

    public function test_never_logs_the_code(): void
    {
        $spy = Log::spy();

        $this->service()->sendCode($this->admin);
        $code = $this->lastCode();
        $this->service()->verify($this->admin, '999999' === $code ? '888888' : '999999');
        $this->service()->verify($this->admin, $code);

        foreach (['info', 'debug', 'warning', 'error', 'notice'] as $level) {
            $spy->shouldNotHaveReceived($level, [Mockery::on(fn ($message) => is_string($message) && str_contains($message, $code)), Mockery::any()]);
            $spy->shouldNotHaveReceived($level, [Mockery::on(fn ($message) => is_string($message) && str_contains($message, $code))]);
        }
        $this->addToAssertionCount(1);
    }
}
