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
        $this->assertTrue($form->has('work_group_id'));
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
        $this->assertArrayHasKey('work_group_id', $errors);
    }

    public function testUserFormValidationWithValidData(): void
    {
        $group = new DeveloperGroup();
        $group->setId(\Ramsey\Uuid\Uuid::uuid4());
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm();
        $form->setWorkGroups([$group]);
        $form->setData([
            'email' => 'test@test.com',
            'password' => 'secret123',
            'work_group_id' => $group->getId()->toString(),
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testUserFormValidationWithInvalidEmail(): void
    {
        $form = $this->createForm();
        $form->setData([
            'email' => 'invalid-email',
            'password' => 'secret123',
            'work_group_id' => 'test-uuid',
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
            'work_group_id' => 'test-uuid',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('password', $errors);
    }

    public function testUserFormElementCount(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $this->assertCount(6, $form->getElements());
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

        $workGroupId = $form->get('work_group_id');
        $this->assertSame('select', $workGroupId->getAttribute('type'));
        $this->assertTrue($workGroupId->getAttribute('required'));
    }

    public function testUserFormWorkGroupsOrderedByRank(): void
    {
        $tester = new TesterGroup();
        $tester->setId(\Ramsey\Uuid\Uuid::uuid4());
        $tester->setName('Testers');
        $tester->setCreatedAt(new \DateTimeImmutable());

        $developer = new DeveloperGroup();
        $developer->setId(\Ramsey\Uuid\Uuid::uuid4());
        $developer->setName('Developers');
        $developer->setCreatedAt(new \DateTimeImmutable());

        $designer = new DesignerGroup();
        $designer->setId(\Ramsey\Uuid\Uuid::uuid4());
        $designer->setName('Designers');
        $designer->setCreatedAt(new \DateTimeImmutable());

        $form = new UserForm(null, [], $this->laminasSm);
        $form->setWorkGroups([$tester, $developer, $designer]);

        $workGroupId = $form->get('work_group_id');
        $options = $workGroupId->getValueOptions();

        $this->assertArrayHasKey($developer->getId()->toString(), $options);
        $this->assertArrayHasKey($designer->getId()->toString(), $options);
        $this->assertArrayHasKey($tester->getId()->toString(), $options);
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
