<?php

declare(strict_types=1);

namespace App\Csrf;

interface CsrfGuardInterface
{
    public function generateToken(string $keyName = '__csrf'): string;
    public function validateToken(string $token, string $csrfKey = '__csrf'): bool;
}
