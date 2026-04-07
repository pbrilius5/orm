<?php

declare(strict_types=1);

namespace App\Csrf;

class CsrfSession
{
    private const SESSION_KEY = '_csrf_tokens';
    private const MAX_TOKENS = 50;
    private const TOKEN_LENGTH = 16;

    private static ?\Closure $sessionGetter = null;
    private static ?\Closure $sessionSetter = null;

    public static function setSessionAccessors(?\Closure $getter, ?\Closure $setter): void
    {
        self::$sessionGetter = $getter;
        self::$sessionSetter = $setter;
    }

    public static function clearSessionAccessors(): void
    {
        self::$sessionGetter = null;
        self::$sessionSetter = null;
    }

    public static function generateToken(): string
    {
        self::ensureSessionStarted();

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        $session = self::getSession();
        $session[$token] = time();

        if (count($session) > self::MAX_TOKENS) {
            $session = array_slice($session, -self::MAX_TOKENS, self::MAX_TOKENS, true);
        }

        self::setSession($session);

        return $token;
    }

    public static function validateToken(string $token, ?int $maxAge = null): bool
    {
        self::ensureSessionStarted();

        $session = self::getSession();

        if (!isset($session[$token])) {
            return false;
        }

        $created = $session[$token];
        $maxAge ??= 3600;

        if ((time() - $created) > $maxAge) {
            unset($session[$token]);
            self::setSession($session);
            return false;
        }

        unset($session[$token]);
        self::setSession($session);
        return true;
    }

    private static function getSession(): array
    {
        if (self::$sessionGetter !== null) {
            return (self::$sessionGetter)();
        }
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    private static function setSession(array $session): void
    {
        if (self::$sessionSetter !== null) {
            (self::$sessionSetter)($session);
            return;
        }
        $_SESSION[self::SESSION_KEY] = $session;
    }

    private static function ensureSessionStarted(): void
    {
        if (self::$sessionGetter !== null) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }
}
