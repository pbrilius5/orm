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
        $emailInput->getValidatorChain()
            ->attach(new NotEmpty())
            ->attach(new EmailAddress());
        $inputFilter->add($emailInput);

        $passwordInput = new Input('password');
        $passwordInput->setRequired(true);
        $passwordInput->getValidatorChain()
            ->attach(new NotEmpty())
            ->attach(new StringLength(['min' => 6]));
        $inputFilter->add($passwordInput);

        $this->setInputFilter($inputFilter);
    }
}
