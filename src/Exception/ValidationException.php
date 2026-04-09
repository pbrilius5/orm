<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class ValidationException extends Exception
{
    /** @var array<int, array{field: string, message: string}> */
    private array $errors;

    /**
     * @param array<int, array{field: string, message: string}> $errors
     */
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /** @return array<int, array{field: string, message: string}> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
