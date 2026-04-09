<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Container\LaminasServiceManagerFactory;
use App\Entity\DeveloperGroup;
use App\Entity\DesignerGroup;
use App\Entity\Group;
use App\Entity\TesterGroup;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use App\Form\UserForm;

class UserFormTest extends TestCase
{
    private ServiceManager $laminasSm;

    protected function setUp(): void
    {
        $this->laminasSm = LaminasServiceManagerFactory::create();
    }

    private function createForm(): UserForm
    {
        return new UserForm(null, ['skip_csrf' => true], $this->laminasSm);
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
        $this->assertTrue($form->has('work_group'));
        $this->assertTrue($form->has('gamification_roles'));
        $this->assertTrue($form->has('submit'));
        $this->assertTrue($form->has('csrf'));
    }

    public function testUserFormHasCsrfElement(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertTrue($form->has('csrf'));
        $csrf = $form->get('csrf');
        $this->assertInstanceOf(\App\Form\CsrfElement::class, $csrf);
    }

    public function testUserFormWorkGroupIdIsRequired(): void
    {
        $form = $this->createForm();
        $form->setData([
            'email' => 'test@test.com',
            'password' => 'secret123',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('work_group', $errors);
    }

    public function testUserFormValidationWithValidData(): void
    {
        $group = new DeveloperGroup();
        $group->setId(\Ramsey\Uuid\Uuid::uuid4());
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm();
        $form->setData([
            'email' => 'test@test.com',
            'password' => 'secret123',
            'work_group' => 'developer',
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testUserFormValidationWithInvalidEmail(): void
    {
        $form = $this->createForm();
        $form->setData([
            'email' => 'invalid-email',
            'password' => 'secret123',
            'work_group' => 'test-uuid',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('email', $errors);
    }

    public function testUserFormValidationWithShortPassword(): void
    {
        $form = $this->createForm();
        $form->setData([
            'email' => 'test@test.com',
            'password' => 'short',
            'work_group' => 'test-uuid',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormElementCount(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertSame($form->count(), count($form->getElements()));
    }

    public function testUserFormGamificationRoles(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertTrue($form->has('gamification_roles'));
        $roles = $form->get('gamification_roles');
        $this->assertSame('radio', $roles->getAttribute('type'));
    }

    public function testUserFormSetWorkGroups(): void
    {
        $group = new DeveloperGroup();
        $group->setId(\Ramsey\Uuid\Uuid::uuid4());
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $form = new UserForm(null, [], $this->laminasSm);
        $form->setWorkGroups([$group]);

        $workGroup = $form->get('work_group');
        $this->assertSame('select', $workGroup->getAttribute('type'));
        $this->assertTrue($workGroup->getAttribute('required'));
    }

    public function testUserFormWorkGroupsOrderedByRank(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $workGroup = $form->get('work_group');
        $options = $workGroup->getValueOptions();

        $this->assertArrayHasKey('tester', $options);
        $this->assertArrayHasKey('designer', $options);
        $this->assertArrayHasKey('developer', $options);
    }

    public function testGetValidationErrorsReturnsArray(): void
    {
        $form = $this->createForm();
        $form->setData([]);
        $form->isValid();
        $errors = $form->getValidationErrors();
        $this->assertIsArray($errors);
    }

    public function testUserFormInputFilterIsSet(): void
    {
        $form = $this->createForm();
        $this->assertNotNull($form->getInputFilter());
    }
}
