<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\Group;
use App\Entity\WizardRole;

class UserForm extends BaseForm
{
    private array $workGroups = [];

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
                'minlength' => 6,
            ],
        ]);

        $this->add([
            'name' => 'work_group_id',
            'type' => 'select',
            'options' => [
                'label' => 'Work Group',
                'empty_option' => 'Select your work group',
                'value_options' => $this->getWorkGroupValueOptions(),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'gamification_roles',
            'type' => 'radio',
            'options' => [
                'label' => 'Role (gamification)',
                'value_options' => [
                    WizardRole::NAME => 'Wizard (rank: 1)',
                    ArchitectRole::NAME => 'Architect (rank: 2)',
                    GameMasterRole::NAME => 'Game Master (rank: 3)',
                ],
            ],
            'attributes' => [
                'required' => true,
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

        $workGroupSpec = [
            'name' => 'work_group_id',
            'required' => true,
        ];

        $rolesSpec = [
            'name' => 'gamification_roles',
            'required' => false,
        ];

        $inputFilter = $inputFilterFactory->createInputFilter([
            $emailSpec,
            $passwordSpec,
            $workGroupSpec,
            $rolesSpec,
        ]);

        $this->setInputFilter($inputFilter);
    }

    public function setWorkGroups(array $workGroups): void
    {
        $this->workGroups = $workGroups;
        if ($this->has('work_group_id')) {
            $this->get('work_group_id')->setValueOptions($this->getWorkGroupValueOptions());
        }
    }

    private function getWorkGroupValueOptions(): array
    {
        $options = [];
        $sortedGroups = $this->workGroups;
        usort($sortedGroups, fn($a, $b) => $a->getRank() <=> $b->getRank());
        foreach ($sortedGroups as $group) {
            $options[$group->getId()->toString()] = $group->getName() . ' (rank: ' . $group->getRank() . ')';
        }
        return $options;
    }

    public function setGamificationRoles(array $roleNames): void
    {
        $rolesDisplay = $this->get('gamification_roles');
        $rolesDisplay->setValue($roleNames[0] ?? null);
    }

    public function setDefaultGamificationRole(string $roleName): void
    {
        $rolesDisplay = $this->get('gamification_roles');
        if (empty($rolesDisplay->getValue())) {
            $rolesDisplay->setValue($roleName);
        }
    }
}
