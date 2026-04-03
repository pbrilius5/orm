<?php

declare(strict_types=1);

namespace App\Form;

use Laminas\Form\Element;
use Laminas\InputFilter\Input;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

class GroupForm extends BaseForm
{
    protected function initForm(): void
    {
        $this->setName('group');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'name',
            'type' => Element\Text::class,
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
            'type' => Element\Textarea::class,
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
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Save Group',
                'class' => 'btn btn-primary',
            ],
        ]);

        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $nameInput = new Input('name');
        $nameInput->setRequired(true);
        $nameInput->getValidatorChain()
            ->attach(new NotEmpty())
            ->attach(new StringLength(['min' => 2, 'max' => 255]));
        $inputFilter->add($nameInput);

        $this->setInputFilter($inputFilter);
    }
}
