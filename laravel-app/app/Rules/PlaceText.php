<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PlaceText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('This field is required.');

            return;
        }

        if (preg_match('/\s{2,}/u', $value)) {
            $fail('Double spacing is not allowed.');

            return;
        }

        if ($value !== trim($value)) {
            $fail('Leading or trailing spaces are not allowed.');

            return;
        }

        if (preg_match("/[^A-Za-zÑñ0-9 #.,\\/'\\-]/u", $value)) {
            $fail('Use letters, numbers, and basic punctuation only.');
        }
    }
}
