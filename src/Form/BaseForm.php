<?php

declare(strict_types=1);

namespace App\Form;

use Laminas\Form\Form;
use Laminas\ServiceManager\ServiceManager;

class BaseForm extends Form
{
    public function __construct(?string $name = null, array $options = [], ?ServiceManager $laminasSm = null)
    {
        parent::__construct($name, $options);

        if ($laminasSm !== null) {
            if ($laminasSm->has('FormElementManager')) {
                $formElementManager = $laminasSm->get('FormElementManager');
                $this->getFormFactory()->setFormElementManager($formElementManager);
            }

            if ($laminasSm->has('InputFilterManager')) {
                $inputFilterManager = $laminasSm->get('InputFilterManager');
                $this->getFormFactory()->getInputFilterFactory()->setInputFilterManager($inputFilterManager);
            }
        }

        $this->initForm();
    }

    protected function initForm(): void {}

    public function getValidationErrors(): array
    {
        $errors = [];
        foreach ($this->getMessages() as $field => $fieldErrors) {
            $errors[$field] = $fieldErrors;
        }
        return $errors;
    }
}
