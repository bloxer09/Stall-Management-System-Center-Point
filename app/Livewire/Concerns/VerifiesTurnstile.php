<?php

namespace App\Livewire\Concerns;

use App\Services\TurnstileService;

/**
 * Turnstile check for public forms. The widget (<x-turnstile />) writes its token into
 * $captchaToken; tokens are single-use, so the widget is reset after every check.
 */
trait VerifiesTurnstile
{
    public string $captchaToken = '';

    /**
     * True when Turnstile is off or the token is genuine; otherwise adds the error under $errorKey.
     */
    protected function passesTurnstile(string $errorKey): bool
    {
        $turnstile = app(TurnstileService::class);
        if (!$turnstile->enabled()) {
            return true;
        }

        $passed = $turnstile->verify($this->captchaToken, (string) request()->ip());
        $this->captchaToken = '';
        $this->dispatch('turnstile-reset');

        if (!$passed) {
            $this->addError($errorKey, 'Please complete the security check.');
        }

        return $passed;
    }
}
