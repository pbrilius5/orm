<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\Group;
use App\Entity\WizardRole;
use App\Service\WorkGroupMapInterface;

class UserForm extends BaseForm
{
    private array $workGroups = [];
    private WorkGroupMapInterface $workGroupMap;

    public function __construct(?string $name = null, array $options = [], $serviceManager = null, ?WorkGroupMapInterface $workGroupMap = null)
    {
        // If not provided (tests or legacy callers), create a default WorkGroupMap.
        $this->workGroupMap = $workGroupMap ?? new \App\Service\WorkGroupMap();
        parent::__construct($name, $options, $serviceManager);
    }

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
            'name' => 'work_group',
            'type' => 'select',
            'options' => [
                'label' => 'Work Group',
                'empty_option' => 'Select your work group',
                'value_options' => [
                    'tester' => [
                        'value' => 'tester',
                        'label' => 'Tester',
                        'attributes' => ['data-fqcn' => \App\Entity\TesterGroup::class],
                    ],
                    'designer' => [
                        'value' => 'designer',
                        'label' => 'Designer',
                        'attributes' => ['data-fqcn' => \App\Entity\DesignerGroup::class],
                    ],
                    'developer' => [
                        'value' => 'developer',
                        'label' => 'Developer',
                        'attributes' => ['data-fqcn' => \App\Entity\DeveloperGroup::class],
                    ],
                ],
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

        $workGroupSpec = [
            'name' => 'work_group',
            'required' => true,
            'validators' => [
                ['name' => 'NotEmpty'],
                ['name' => 'InArray', 'options' => ['haystack' => $this->workGroupMap->getAllDiscriminators()]],
            ],
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
        if ($this->has('work_group')) {
            $element = $this->get('work_group');
            if (method_exists($element, 'setValueOptions')) {
                $element->setValueOptions($this->getWorkGroupValueOptions());
            }

            $inputFilter = $this->getInputFilter();
            $workGroupInput = $inputFilter->get('work_group');
            if ($workGroupInput !== null) {
                $validators = $workGroupInput->getValidatorChain()->getValidators();
                $haystack = array_keys($this->getWorkGroupValueOptions());
                foreach ($validators as $validator) {
                    $v = $validator['instance'];
                    if ($v instanceof \Laminas\Validator\InArray) {
                        $v->setHaystack($haystack);
                    }
                }
            }
        }
    }

    private function getWorkGroupValueOptions(): array
    {
        $options = [];
        $sortedGroups = $this->workGroups;
        usort($sortedGroups, fn($a, $b) => $a->getRank() <=> $b->getRank());
        foreach ($sortedGroups as $group) {
            // value is discriminator (mapped via Doctrine discriminator map on Group entity)

            // derive discriminator from Group instance using central map service
            $discriminator = $this->workGroupMap->getDiscriminatorForGroup($group);
            $fqcn = $group::class;

            // value_options array with label and attributes so Laminas will render data-fqcn
            $options[$discriminator] = [
                'value' => $discriminator,
                'label' => $group->getName(),
                'attributes' => ['data-fqcn' => $fqcn],
            ];
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
