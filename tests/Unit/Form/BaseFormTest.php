<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use PHPUnit\Framework\TestCase;
use App\Form\BaseForm;
use App\Form\UserForm;
use App\Form\GroupForm;

class BaseFormTest extends TestCase
{
    public function testBaseFormCanBeExtended(): void
    {
        $form = new UserForm();
        $this->assertInstanceOf(BaseForm::class, $form);
    }

    public function testUserFormHasCorrectName(): void
    {
        $form = new UserForm();
        $this->assertSame('user', $form->getName());
    }

    public function testUserFormHasCorrectMethod(): void
    {
        $form = new UserForm();
        $this->assertSame('post', $form->getAttribute('method'));
    }

    public function testUserFormHasExpectedElements(): void
    {
        $form = new UserForm();
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('password'));
        $this->assertTrue($form->has('roles'));
        $this->assertTrue($form->has('submit'));
    }

    public function testUserFormElementCount(): void
    {
        $form = new UserForm();
        $this->assertCount(4, $form->getElements());
    }

    public function testUserFormEmailElementAttributes(): void
    {
        $form = new UserForm();
        $email = $form->get('email');
        $this->assertSame('email', $email->getAttribute('type'));
        $this->assertTrue($email->getAttribute('required'));
        $this->assertSame('user@example.com', $email->getAttribute('placeholder'));
    }

    public function testUserFormPasswordElementAttributes(): void
    {
        $form = new UserForm();
        $password = $form->get('password');
        $this->assertSame('password', $password->getAttribute('type'));
        $this->assertTrue($password->getAttribute('required'));
    }

    public function testUserFormRolesElementIsMultiCheckbox(): void
    {
        $form = new UserForm();
        $roles = $form->get('roles');
        $this->assertSame('multi_checkbox', $roles->getAttribute('type'));
    }

    public function testUserFormRolesValueOptions(): void
    {
        $form = new UserForm();
        $roles = $form->get('roles');
        $valueOptions = $roles->getOption('value_options');
        $this->assertArrayHasKey('ROLE_USER', $valueOptions);
        $this->assertArrayHasKey('ROLE_WIZARD', $valueOptions);
        $this->assertArrayHasKey('ROLE_ARCHITECT', $valueOptions);
        $this->assertArrayHasKey('ROLE_GAME_MASTER', $valueOptions);
    }

    public function testUserFormSubmitElementAttributes(): void
    {
        $form = new UserForm();
        $submit = $form->get('submit');
        $this->assertSame('Save', $submit->getValue());
        $this->assertSame('btn btn-primary', $submit->getAttribute('class'));
    }

    public function testUserFormValidationWithValidData(): void
    {
        $form = new UserForm();
        $form->setData([
            'email' => 'test@example.com',
            'password' => 'secret123',
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testUserFormValidationWithInvalidEmail(): void
    {
        $form = new UserForm();
        $form->setData([
            'email' => 'not-an-email',
            'password' => 'secret123',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('email', $errors);
    }

    public function testUserFormValidationWithEmptyEmail(): void
    {
        $form = new UserForm();
        $form->setData([
            'email' => ' ',
            'password' => 'secret123',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('email', $errors);
    }

    public function testUserFormValidationWithShortPassword(): void
    {
        $form = new UserForm();
        $form->setData([
            'email' => 'test@example.com',
            'password' => 'abc',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormValidationWithEmptyPassword(): void
    {
        $form = new UserForm();
        $form->setData([
            'email' => 'test@example.com',
            'password' => '',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormValidationWithMissingFields(): void
    {
        $form = new UserForm();
        $form->setData([]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormValidationWithRoles(): void
    {
        $form = new UserForm();
        $form->setData([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'roles' => ['ROLE_USER', 'ROLE_WIZARD'],
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGetValidationErrorsReturnsArray(): void
    {
        $form = new UserForm();
        $form->setData([]);
        $form->isValid();
        $errors = $form->getValidationErrors();
        $this->assertIsArray($errors);
    }
}
