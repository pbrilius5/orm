<?php

declare(strict_types=1);

use App\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testGetErrors(): void
    {
        $errors = [
            ['field' => 'email', 'message' => 'Invalid email'],
            ['field' => 'password', 'message' => 'Too short'],
        ];

        $ex = new ValidationException($errors);

        $this->assertSame($errors, $ex->getErrors());
        $this->assertStringContainsString('Validation', $ex->getMessage());
    }
}
