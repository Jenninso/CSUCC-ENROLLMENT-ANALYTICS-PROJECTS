<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContactNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Contact number is required.');

            return;
        }

        if (preg_match('/[A-Za-z]/', $value)) {
            $fail('Letters are not allowed in the contact number.');

            return;
        }

        if (! preg_match('/^09\d{9}$/', $value)) {
            $fail('Enter an 11-digit mobile number starting with 09.');
        }
    }
}
