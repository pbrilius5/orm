<?php

declare(strict_types=1);

namespace App\Form;

class GroupForm extends BaseForm
{
    protected function initForm(): void
    {
        $this->setName('group');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'type',
            'type' => 'select',
            'options' => [
                'label' => 'Work Group Type',
                'value_options' => [
                    'developer' => 'Developer (rank: 3)',
                    'designer' => 'Designer (rank: 2)',
                    'tester' => 'Tester (rank: 1)',
                ],
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Group Name',
            ],
            'attributes' => [
                'required' => true,
                'placeholder' => 'Enter group name',
            ],
        ]);

        $this->add([
            'name' => 'description',
            'type' => 'textarea',
            'options' => [
                'label' => 'Description',
            ],
            'attributes' => [
                'required' => false,
                'placeholder' => 'Optional description',
                'rows' => 3,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Save Group',
                'class' => 'btn btn-primary',
            ],
        ]);

        $inputFilterFactory = $this->getFormFactory()->getInputFilterFactory();

        $typeSpec = [
            'name' => 'type',
            'required' => true,
            'validators' => [
                ['name' => 'InArray', 'options' => ['haystack' => ['developer', 'designer', 'tester']]],
            ],
        ];

        $nameSpec = [
            'name' => 'name',
            'required' => true,
            'validators' => [
                ['name' => 'NotEmpty'],
                ['name' => 'StringLength', 'options' => ['min' => 2, 'max' => 255]],
            ],
        ];

        $descriptionSpec = [
            'name' => 'description',
            'required' => false,
        ];

        $inputFilter = $inputFilterFactory->createInputFilter([
            $typeSpec,
            $nameSpec,
            $descriptionSpec,
        ]);

        $this->setInputFilter($inputFilter);
    }
}
