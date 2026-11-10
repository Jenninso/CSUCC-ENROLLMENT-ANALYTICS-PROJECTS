<?php

namespace App\Support;

class AccountCategory
{
    public const INSTITUTIONAL = 'institutional';

    public const PERSONAL = 'personal';

    public const LABELS = [
        self::INSTITUTIONAL => 'Institutional Account',
        self::PERSONAL => 'Personal Account',
    ];

    public static function fromEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));
        $at = strrpos($email, '@');
        if ($at === false) {
            return self::PERSONAL;
        }

        $domain = substr($email, $at + 1);
        if ($domain === '' || str_contains($domain, ' ')) {
            return self::PERSONAL;
        }

        foreach (config('accounts.institutional_email_domains', []) as $institutional) {
            $institutional = strtolower(trim((string) $institutional));
            if ($institutional === '') {
                continue;
            }
            if ($domain === $institutional || str_ends_with($domain, '.'.$institutional)) {
                return self::INSTITUTIONAL;
            }
        }

        return self::PERSONAL;
    }

    public static function label(?string $emailOrCategory): string
    {
        $value = strtolower(trim((string) $emailOrCategory));
        if ($value === self::INSTITUTIONAL || $value === self::PERSONAL) {
            return self::LABELS[$value];
        }

        return self::LABELS[self::fromEmail($emailOrCategory)] ?? self::LABELS[self::PERSONAL];
    }

    public static function isInstitutional(?string $email): bool
    {
        return self::fromEmail($email) === self::INSTITUTIONAL;
    }
}
