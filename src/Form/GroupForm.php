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

        $nameSpec = [
            'name' => 'name',
            'required' => true,
            'validators' => [
                ['name' => 'NotEmpty'],
                ['name' => 'StringLength', 'options' => ['min' => 2, 'max' => 255]],
            ],
        ];

        $inputFilter = $inputFilterFactory->createInputFilter([
            $nameSpec,
        ]);

        $this->setInputFilter($inputFilter);
    }
}
