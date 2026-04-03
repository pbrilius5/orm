<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\WizardRole;
use Laminas\Form\Element;
use Laminas\InputFilter\Input;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

class UserForm extends BaseForm
{
    protected function initForm(): void
    {
        $this->setName('user');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'email',
            'type' => Element\Email::class,
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
            'type' => Element\Password::class,
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
            'type' => Element\MultiCheckbox::class,
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
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Save',
                'class' => 'btn btn-primary',
            ],
        ]);

        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $emailInput = new Input('email');
        $emailInput->setRequired(true);
        $emailInput->getFilterChain()->attach(new \Laminas\Filter\StringTrim());
        $emailInput->getValidatorChain()
            ->attach(new NotEmpty(), true)
            ->attach(new EmailAddress());
        $inputFilter->add($emailInput);

        $passwordInput = new Input('password');
        $passwordInput->setRequired(true);
        $passwordInput->getValidatorChain()
            ->attach(new NotEmpty())
            ->attach(new StringLength(['min' => 6]));
        $inputFilter->add($passwordInput);

        $rolesInput = new Input('gamification_roles');
        $rolesInput->setRequired(false);
        $inputFilter->add($rolesInput);

        $this->setInputFilter($inputFilter);
    }

    public function setGamificationRoles(array $roleNames): void
    {
        $rolesDisplay = $this->get('gamification_roles');
        $rolesDisplay->setValue($roleNames);
    }
}
