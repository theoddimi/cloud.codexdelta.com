<?php

namespace Codexdelta\App\Security;

class CsrfToken
{

    public const CSRF_TOKEN_KEY = 'csrf_token';

    public static function isTokenValid(string $submittedToken = '')
    {
        if ('' === $submittedToken) {
            return false;
        }

        $storedToken = (string) session()->get(self::CSRF_TOKEN_KEY);

        return hash_equals($storedToken, $submittedToken);
    }
}