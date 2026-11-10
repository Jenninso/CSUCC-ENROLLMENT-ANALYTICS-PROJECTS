<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public static function checks(string $password): array
    {
        return [
            'length' => mb_strlen($password) >= 8,
            'uppercase' => (bool) preg_match('/[A-Z]/u', $password),
            'lowercase' => (bool) preg_match('/[a-z]/u', $password),
            'number' => (bool) preg_match('/\d/u', $password),
            'symbol' => (bool) preg_match('/[^A-Za-z0-9]/u', $password),
            'long' => mb_strlen($password) >= 12,
        ];
    }

    public static function score(string $password): int
    {
        return count(array_filter(self::checks($password)));
    }

    public static function level(string $password): string
    {
        if ($password === '') {
            return 'empty';
        }

        $score = self::score($password);

        if ($score <= 2) {
            return 'weak';
        }

        if ($score <= 4) {
            return 'medium';
        }

        return 'strong';
    }

    public static function isAcceptable(string $password): bool
    {
        $checks = self::checks($password);

        return $checks['length']
            && $checks['uppercase']
            && $checks['lowercase']
            && $checks['number']
            && self::level($password) !== 'weak';
    }

    public static function rule(): Password
    {
        return Password::min(8)->mixedCase()->numbers();
    }

    public static function message(): string
    {
        return 'Use at least 8 characters with uppercase, lowercase, and a number. Weak passwords are not allowed.';
    }
}
