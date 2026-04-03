<?php

declare(strict_types=1);

namespace App\Form;

use Laminas\Form\Form;

class BaseForm extends Form
{
    public function __construct(?string $name = null, array $options = [])
    {
        parent::__construct($name, $options);
        $this->initForm();
    }

    protected function initForm(): void
    {
    }

    public function getValidationErrors(): array
    {
        $errors = [];
        foreach ($this->getMessages() as $field => $fieldErrors) {
            $errors[$field] = $fieldErrors;
        }
        return $errors;
    }
}
