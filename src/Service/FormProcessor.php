<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use Laminas\Form\FormInterface;

class FormProcessor
{
    public function __construct() {}

    /**
     * Validate a Laminas form and either return validated data or throw ValidationException.
     *
     * @param FormInterface $form
     * @param array<string, mixed> $data
     * @return array<string, mixed> validated data
     * @throws ValidationException when validation fails
     */
    public function validateOrThrow(FormInterface $form, array $data): array
    {
        $form->setData($data);
        if ($form->isValid()) {
            return $form->getData();
        }

        $messages = $form->getMessages();
        $errors = [];
        foreach ($messages as $field => $fieldMessages) {
            if (!is_array($fieldMessages)) {
                continue;
            }
            foreach ($fieldMessages as $message) {
                // Laminas messages can be nested arrays; flatten first-level strings
                if (is_string($message)) {
                    $errors[] = ['field' => $field, 'message' => $message];
                } elseif (is_array($message)) {
                    foreach ($message as $m) {
                        if (is_string($m)) {
                            $errors[] = ['field' => $field, 'message' => $m];
                        }
                    }
                }
            }
        }

        throw new ValidationException($errors);
    }
}
