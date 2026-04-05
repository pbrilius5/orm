<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Container\LaminasServiceManagerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use App\Form\BaseForm;
use App\Form\UserForm;
use App\Form\GroupForm;

class BaseFormTest extends TestCase
{
    private ServiceManager $laminasSm;

    protected function setUp(): void
    {
        $this->laminasSm = LaminasServiceManagerFactory::create();
    }

    public function testBaseFormCanBeExtended(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertInstanceOf(BaseForm::class, $form);
    }

    public function testUserFormHasCorrectName(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertSame('user', $form->getName());
    }

    public function testUserFormHasCorrectMethod(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertSame('post', $form->getAttribute('method'));
    }

    public function testUserFormHasExpectedElements(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('password'));
        $this->assertTrue($form->has('group_id'));
        $this->assertTrue($form->has('gamification_roles'));
        $this->assertTrue($form->has('submit'));
    }

    public function testUserFormElementCount(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertCount(6, $form->getElements());
    }

    public function testUserFormEmailElementAttributes(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $email = $form->get('email');
        $this->assertSame('email', $email->getAttribute('type'));
        $this->assertTrue($email->getAttribute('required'));
        $this->assertSame('user@example.com', $email->getAttribute('placeholder'));
    }

    public function testUserFormPasswordElementAttributes(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $password = $form->get('password');
        $this->assertSame('password', $password->getAttribute('type'));
        $this->assertTrue($password->getAttribute('required'));
    }

    public function testUserFormGamificationRolesElementIsMultiCheckbox(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $roles = $form->get('gamification_roles');
        $this->assertSame('multi_checkbox', $roles->getAttribute('type'));
    }

    public function testUserFormGamificationRolesValueOptions(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $roles = $form->get('gamification_roles');
        $valueOptions = $roles->getOption('value_options');
        $this->assertArrayHasKey('ROLE_WIZARD', $valueOptions);
        $this->assertArrayHasKey('ROLE_ARCHITECT', $valueOptions);
        $this->assertArrayHasKey('ROLE_GAME_MASTER', $valueOptions);
    }

    public function testUserFormSubmitElementAttributes(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $submit = $form->get('submit');
        $this->assertSame('Save', $submit->getValue());
        $this->assertSame('btn btn-primary', $submit->getAttribute('class'));
    }

    public function testUserFormValidationWithValidData(): void
    {
        $group = new \App\Entity\DeveloperGroup();
        $group->setId(\Ramsey\Uuid\Uuid::uuid4());
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $form = new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
        $form->setGroups([$group]);
        $form->setData([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'group_id' => $group->getId()->toString(),
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testUserFormValidationWithInvalidEmail(): void
    {
        $form = new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
        $form->setData([
            'email' => 'not-an-email',
            'password' => 'secret123',
            'group_id' => 'some-group-id',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('email', $errors);
    }

    public function testUserFormValidationWithEmptyEmail(): void
    {
        $form = new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
        $form->setData([
            'email' => ' ',
            'password' => 'secret123',
            'group_id' => 'some-group-id',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('email', $errors);
    }

    public function testUserFormValidationWithShortPassword(): void
    {
        $form = new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
        $form->setData([
            'email' => 'test@example.com',
            'password' => 'abc',
            'group_id' => 'some-group-id',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormValidationWithEmptyPassword(): void
    {
        $form = new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
        $form->setData([
            'email' => 'test@example.com',
            'password' => '',
            'group_id' => 'some-group-id',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormValidationWithMissingFields(): void
    {
        $form = new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
        $form->setData([]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormValidationWithGamificationRoles(): void
    {
        $group = new \App\Entity\DeveloperGroup();
        $group->setId(\Ramsey\Uuid\Uuid::uuid4());
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $form = new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
        $form->setGroups([$group]);
        $form->setData([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'group_id' => $group->getId()->toString(),
            'gamification_roles' => ['ROLE_WIZARD', 'ROLE_ARCHITECT'],
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGetValidationErrorsReturnsArray(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $form->setData([]);
        $form->isValid();
        $errors = $form->getValidationErrors();
        $this->assertIsArray($errors);
    }
}
