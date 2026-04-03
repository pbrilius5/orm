<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\WizardRole;

class UserForm extends BaseForm
{
    protected function initForm(): void
    {
        $this->setName('user');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'email',
            'type' => 'email',
            'options' => [
                'label' => 'Email',
            ],
            'attributes' => [
                'required' => true,
                'placeholder' => 'user@example.com',
            ],
        ]);

        $this->add([
            'name' => 'password',
            'type' => 'password',
            'options' => [
                'label' => 'Password',
            ],
            'attributes' => [
                'required' => true,
                'placeholder' => 'Password',
            ],
        ]);

        $this->add([
            'name' => 'gamification_roles',
            'type' => 'multicheckbox',
            'options' => [
                'label' => 'Roles',
                'value_options' => [
                    WizardRole::NAME => 'Wizard',
                    ArchitectRole::NAME => 'Architect',
                    GameMasterRole::NAME => 'Game Master',
                ],
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Save',
                'class' => 'btn btn-primary',
            ],
        ]);

        $inputFilterFactory = $this->getFormFactory()->getInputFilterFactory();

        $emailSpec = [
            'name' => 'email',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                ['name' => 'NotEmpty', 'break_chain_on_failure' => true],
                ['name' => 'EmailAddress'],
            ],
        ];

        $passwordSpec = [
            'name' => 'password',
            'required' => true,
            'validators' => [
                ['name' => 'NotEmpty'],
                ['name' => 'StringLength', 'options' => ['min' => 6]],
            ],
        ];

        $rolesSpec = [
            'name' => 'gamification_roles',
            'required' => false,
        ];

        $inputFilter = $inputFilterFactory->createInputFilter([
            $emailSpec,
            $passwordSpec,
            $rolesSpec,
        ]);

        $this->setInputFilter($inputFilter);
    }

    public function setGamificationRoles(array $roleNames): void
    {
        $rolesDisplay = $this->get('gamification_roles');
        $rolesDisplay->setValue($roleNames);
    }
}
