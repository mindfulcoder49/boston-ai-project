<?php

namespace App\Support;

class AuthRedirect
{
    public static function sanitize(?string $redirectTo): ?string
    {
        $redirectTo = trim((string) $redirectTo);

        if ($redirectTo === '' || !str_starts_with($redirectTo, '/')) {
            return null;
        }

        if (str_starts_with($redirectTo, '//')) {
            return null;
        }

        $parts = parse_url($redirectTo);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        return $redirectTo;
    }
}
