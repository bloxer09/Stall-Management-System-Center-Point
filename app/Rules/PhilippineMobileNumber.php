<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhilippineMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/\A(?:0|\+?63)?9[0-9]{9}\z/', trim($value))) {
            $fail('Enter a valid Philippine mobile number: 10 digits starting with 9 after +63.');
        }
    }
}
