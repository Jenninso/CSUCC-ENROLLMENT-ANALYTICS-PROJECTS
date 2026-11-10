<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PersonName implements ValidationRule
{
    public function __construct(private bool $required = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            if ($this->required) {
                $fail('Enter a valid name.');
            }

            return;
        }

        $error = self::errorFor($value);
        if ($error !== null) {
            $fail($error);
        }
    }

    public static function errorFor(string $value): ?string
    {
        if (preg_match('/\d/u', $value)) {
            return 'Numbers are not allowed in the name.';
        }

        if (preg_match('/\s{2,}/u', $value)) {
            return 'Double spacing is not allowed in the name.';
        }

        if ($value !== trim($value)) {
            return 'Leading or trailing spaces are not allowed in the name.';
        }

        if (preg_match("/[^A-Za-zÑñ '\\-]/u", $value)) {
            return 'Use letters only. Hyphens and apostrophes are allowed.';
        }

        if (preg_match('/([A-Za-zÑñ])\1\1/u', mb_strtolower($value))) {
            return 'Three consecutive letters of the same kind are not allowed.';
        }

        foreach (preg_split('/\s+/u', $value) as $word) {
            if ($word === '') {
                continue;
            }

            $parts = preg_split("/['\\-]/u", $word) ?: [];
            foreach ($parts as $part) {
                if ($part === '') {
                    return 'Use a letter after a hyphen or apostrophe.';
                }

                $first = mb_substr($part, 0, 1);
                if (! preg_match('/\p{Lu}/u', $first)) {
                    return 'The first letter of the name must be a capital letter.';
                }

                $rest = mb_substr($part, 1);
                if ($rest !== '' && preg_match('/\p{Lu}/u', $rest)) {
                    return 'After the first capital letter, use small letters only. Names like JoHn are not allowed.';
                }
            }
        }

        return null;
    }
}
