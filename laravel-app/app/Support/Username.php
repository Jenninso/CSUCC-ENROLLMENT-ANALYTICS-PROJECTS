<?php

namespace App\Support;

class Username
{
    public const PATTERN = '/^[a-z0-9](?:[a-z0-9._-]{1,48}[a-z0-9])?$/';

    public static function normalize(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    public static function fromEmail(string $email): string
    {
        $local = strstr(strtolower(trim($email)), '@', true);
        $local = preg_replace('/[^a-z0-9._-]/', '', (string) $local) ?: 'user';

        return substr($local, 0, 50);
    }

    public static function isValid(string $username): bool
    {
        $username = self::normalize($username);

        return $username !== ''
            && ! str_contains($username, '@')
            && (bool) preg_match(self::PATTERN, $username);
    }

    /** @return list<string> */
    public static function rules(string $uniqueExceptId = ''): array
    {
        $unique = 'unique:users,username';
        if ($uniqueExceptId !== '') {
            $unique .= ','.$uniqueExceptId;
        }

        return [
            'required',
            'string',
            'min:3',
            'max:50',
            'regex:'.self::PATTERN,
            $unique,
        ];
    }

    public static function message(): string
    {
        return 'Use 3–50 characters: lowercase letters, numbers, dots, underscores, or hyphens (e.g. jennel.paracuelles).';
    }
}
