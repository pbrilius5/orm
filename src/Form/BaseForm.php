<?php

declare(strict_types=1);

namespace App\Form;

use Laminas\Form\Form;
use Laminas\Form\Element\Csrf;
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

        if (!($options['skip_csrf'] ?? false)) {
            $this->addCsrfElement();
        }
    }

    protected function initForm(): void {}

    protected function addCsrfElement(): void
    {
        $csrf = new Csrf('csrf', [
            'csrf_options' => [
                'timeout' => 600,
            ],
        ]);
        $this->add($csrf);
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
