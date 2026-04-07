<?php

declare(strict_types=1);

namespace App\Csrf;

class SessionCsrfGuard implements CsrfGuardInterface
{
    public function generateToken(string $keyName = '__csrf'): string
    {
        return CsrfSession::generateToken();
    }

    public function validateToken(string $token, string $csrfKey = '__csrf'): bool
    {
        return CsrfSession::validateToken($token);
    }
}
