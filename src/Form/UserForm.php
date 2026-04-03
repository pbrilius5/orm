<?php

declare(strict_types=1);

namespace App\Form;

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
            'name' => 'roles',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' => 'ROLE_USER',
            ],
        ]);

        $this->add([
            'name' => 'roles_display',
            'type' => Element\MultiCheckbox::class,
            'options' => [
                'label' => 'Roles',
                'value_options' => [
                    'ROLE_USER' => 'User',
                    'ROLE_WIZARD' => 'Wizard',
                    'ROLE_ARCHITECT' => 'Architect',
                    'ROLE_GAME_MASTER' => 'Game Master',
                ],
            ],
            'attributes' => [
                'value' => ['ROLE_USER'],
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

        $rolesInput = new Input('roles');
        $rolesInput->setRequired(false);
        $inputFilter->add($rolesInput);

        $rolesDisplayInput = new Input('roles_display');
        $rolesDisplayInput->setRequired(false);
        $inputFilter->add($rolesDisplayInput);

        $this->setInputFilter($inputFilter);
    }

    public function setRoles(array $roleNames): void
    {
        $rolesDisplay = $this->get('roles_display');
        $rolesDisplay->setValue($roleNames);

        $roles = $this->get('roles');
        if (!in_array('ROLE_USER', $roleNames, true)) {
            $roleNames[] = 'ROLE_USER';
        }
        $roles->setValue(implode(',', $roleNames));
    }
}
