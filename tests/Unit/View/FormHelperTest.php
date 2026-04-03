<?php

declare(strict_types=1);

namespace App\Tests\Unit\View;

use App\Container\LaminasServiceManagerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use App\Form\UserForm;
use App\Form\GroupForm;
use App\View\Helper\FormHelper;

class FormHelperTest extends TestCase
{
    private ServiceManager $laminasSm;

    protected function setUp(): void
    {
        $this->laminasSm = LaminasServiceManagerFactory::create();
    }

    public function testRenderFormReturnsHtmlString(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertIsString($html);
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('</form>', $html);
    }

    public function testRenderFormHasCorrectMethod(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testRenderFormHasCorrectAction(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setAttribute('action', '/groups/create');
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('action="/groups/create"', $html);
    }

    public function testRenderFormContainsNameField(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('type="text"', $html);
    }

    public function testRenderFormContainsDescriptionField(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('<textarea', $html);
    }

    public function testRenderFormContainsSubmitButton(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('Save Group', $html);
    }

    public function testRenderFormContainsLabels(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('Group Name', $html);
        $this->assertStringContainsString('Description', $html);
    }

    public function testRenderUserFormContainsEmailField(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('type="email"', $html);
    }

    public function testRenderUserFormContainsPasswordField(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('type="password"', $html);
    }

    public function testRenderUserFormContainsGamificationRoles(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('name="gamification_roles[]"', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
    }

    public function testRenderUserFormContainsRoleOptions(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('ROLE_WIZARD', $html);
        $this->assertStringContainsString('ROLE_ARCHITECT', $html);
        $this->assertStringContainsString('ROLE_GAME_MASTER', $html);
    }

    public function testRenderFormDisplaysValidationErrors(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setData([]);
        $form->isValid();
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('invalid-feedback', $html);
    }

    public function testRenderFormDisplaysIsInvalidClassOnError(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setData([]);
        $form->isValid();
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('is-invalid', $html);
    }

    public function testRenderFormNoValidationErrorsOnValidData(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setData(['name' => 'Developers']);
        $form->isValid();
        $html = FormHelper::renderForm($form);
        $this->assertStringNotContainsString('invalid-feedback', $html);
        $this->assertStringNotContainsString('is-invalid', $html);
    }

    public function testRenderFormEscapesHtmlInValues(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setData(['name' => '<script>alert("xss")</script>']);
        $form->isValid();
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testRenderFormEscapesHtmlInLabels(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('Group Name', $html);
    }

    public function testRenderFormWithPrepopulatedData(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setData(['name' => 'Test Group']);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('value="Test Group"', $html);
    }

    public function testRenderFormWithTextareaPrepopulatedData(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setData(['name' => 'Test', 'description' => 'Test description']);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('Test description', $html);
    }

    public function testRenderFormWithCheckboxSelectedValues(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $form->setData(['email' => 'test@example.com', 'password' => 'secret', 'gamification_roles' => ['ROLE_WIZARD']]);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('value="ROLE_WIZARD"', $html);
    }

    public function testRenderFormSubmitButtonHasCorrectClass(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('btn btn-primary', $html);
    }

    public function testRenderFormHasBootstrapClasses(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('form-control', $html);
        $this->assertStringContainsString('form-label', $html);
        $this->assertStringContainsString('mb-3', $html);
    }

    public function testRenderFormMultipleValidationErrors(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setData(['name' => '']);
        $form->isValid();
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('invalid-feedback', $html);
    }

    public function testRenderFormWithEmptyAction(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('action=""', $html);
    }

    public function testRenderFormWithCustomAction(): void
    {
        $form = new GroupForm(null, [], $this->laminasSm);
        $form->setAttribute('action', '/custom/path');
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('action="/custom/path"', $html);
    }

    public function testRenderUserFormSubmitText(): void
    {
        $form = new UserForm(null, [], $this->laminasSm);
        $html = FormHelper::renderForm($form);
        $this->assertStringContainsString('Save', $html);
    }
}
