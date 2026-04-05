<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Container\LaminasServiceManagerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use App\Form\GroupForm;

class GroupFormTest extends TestCase
{
    private ServiceManager $laminasSm;

    protected function setUp(): void
    {
        $this->laminasSm = LaminasServiceManagerFactory::create();
    }

    private function createForm(): GroupForm
    {
        return new GroupForm(null, ['skip_csrf' => true], $this->laminasSm);
    }

    public function testGroupFormHasCorrectName(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $this->assertSame('group', $form->getName());
    }

    public function testGroupFormHasCorrectMethod(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $this->assertSame('post', $form->getAttribute('method'));
    }

    public function testGroupFormHasExpectedElements(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('submit'));
        $this->assertTrue($form->has('csrf'));
    }

    public function testGroupFormHasCsrfElement(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $this->assertTrue($form->has('csrf'));
        $csrf = $form->get('csrf');
        $this->assertInstanceOf(\Laminas\Form\Element\Csrf::class, $csrf);
    }

    public function testGroupFormElementCount(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $this->assertCount(4, $form->getElements());
    }

    public function testGroupNameElementAttributes(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $name = $form->get('name');
        $this->assertSame('text', $name->getAttribute('type'));
        $this->assertTrue($name->getAttribute('required'));
        $this->assertSame('Enter group name', $name->getAttribute('placeholder'));
    }

    public function testGroupDescriptionElementAttributes(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $description = $form->get('description');
        $this->assertSame('textarea', $description->getAttribute('type'));
        $this->assertFalse($description->getAttribute('required'));
        $this->assertSame('Optional description', $description->getAttribute('placeholder'));
    }

    public function testGroupSubmitElementAttributes(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $submit = $form->get('submit');
        $this->assertSame('Save Group', $submit->getValue());
        $this->assertSame('btn btn-primary', $submit->getAttribute('class'));
    }

    public function testGroupFormValidationWithValidData(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => 'Developers',
            'description' => 'Development team',
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGroupFormValidationWithValidDataNoDescription(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => 'Testers',
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGroupFormValidationWithEmptyName(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => '',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('name', $errors);
    }

    public function testGroupFormValidationWithMissingName(): void
    {
        $form = $this->createForm();
        $form->setData([]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('name', $errors);
    }

    public function testGroupFormValidationWithShortName(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => 'A',
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('name', $errors);
    }

    public function testGroupFormValidationWithSingleCharName(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => 'X',
        ]);
        $this->assertFalse($form->isValid());
    }

    public function testGroupFormValidationWithTwoCharName(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => 'AB',
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGroupFormValidationWithLongName(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => str_repeat('A', 255),
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGroupFormValidationWithTooLongName(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => str_repeat('A', 256),
        ]);
        $this->assertFalse($form->isValid());
        $errors = $form->getValidationErrors();
        $this->assertArrayHasKey('name', $errors);
    }

    public function testGroupFormValidationWithDescription(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => 'Developers',
            'description' => 'A team of developers working on the project',
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGroupFormValidationWithEmptyDescription(): void
    {
        $form = $this->createForm();
        $form->setData([
            'name' => 'Developers',
            'description' => '',
        ]);
        $this->assertTrue($form->isValid());
    }

    public function testGetValidationErrorsReturnsArray(): void
    {
        $form = $this->createForm();
        $form->setData([]);
        $form->isValid();
        $errors = $form->getValidationErrors();
        $this->assertIsArray($errors);
    }

    public function testGroupFormInputFilterIsSet(): void
    {
        $form = $this->createForm();
        $this->assertNotNull($form->getInputFilter());
    }
}
