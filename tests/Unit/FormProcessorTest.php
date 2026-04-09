<?php

declare(strict_types=1);

use App\Service\FormProcessor;
use App\Form\UserForm;
use PHPUnit\Framework\TestCase;

final class FormProcessorTest extends TestCase
{
    public function testValidateOrThrowReturnsDataWhenValid(): void
    {
        $processor = new FormProcessor();
        $workGroupMap = new \App\Service\WorkGroupMap();
        $form = new UserForm(null, ['skip_csrf' => true], null, $workGroupMap);

        $data = [
            'email' => 'user@example.com',
            'password' => 'hunter12',
            'work_group' => $workGroupMap->getAllDiscriminators()[0] ?? 'tester',
            'gamification_roles' => '',
        ];

        $result = $processor->validateOrThrow($form, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        $this->assertSame('user@example.com', $result['email']);
    }

    public function testValidateOrThrowThrowsOnInvalid(): void
    {
        $this->expectException(\App\Exception\ValidationException::class);

        $processor = new FormProcessor();
        $workGroupMap = new \App\Service\WorkGroupMap();
        $form = new UserForm(null, ['skip_csrf' => true], null, $workGroupMap);

        $data = [
            'email' => 'not-an-email',
            'password' => 'x',
            'work_group' => '',
            'gamification_roles' => '',
        ];

        $processor->validateOrThrow($form, $data);
    }
}
