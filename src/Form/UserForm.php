<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\Group;
use App\Entity\WizardRole;

class UserForm extends BaseForm
{
    private array $groups = [];

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
            'name' => 'group_id',
            'type' => 'select',
            'options' => [
                'label' => 'Group',
                'empty_option' => 'Select a group',
                'value_options' => $this->getGroupValueOptions(),
            ],
            'attributes' => [
                'required' => true,
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

        $groupSpec = [
            'name' => 'group_id',
            'required' => true,
        ];

        $rolesSpec = [
            'name' => 'gamification_roles',
            'required' => false,
        ];

        $inputFilter = $inputFilterFactory->createInputFilter([
            $emailSpec,
            $passwordSpec,
            $groupSpec,
            $rolesSpec,
        ]);

        $this->setInputFilter($inputFilter);
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
        if ($this->has('group_id')) {
            $this->get('group_id')->setValueOptions($this->getGroupValueOptions());
        }
    }

    private function getGroupValueOptions(): array
    {
        $options = [];
        foreach ($this->groups as $group) {
            $options[$group->getId()->toString()] = $group->getName();
        }
        return $options;
    }

    public function setGamificationRoles(array $roleNames): void
    {
        $rolesDisplay = $this->get('gamification_roles');
        $rolesDisplay->setValue($roleNames);
    }
}
