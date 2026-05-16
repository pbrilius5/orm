<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Action\Group\CreateAction as GroupCreateAction;
use App\Action\Group\DeleteAction as GroupDeleteAction;
use App\Action\Group\ListAction as GroupListAction;
use App\Action\Group\PatchAction as GroupPatchAction;
use App\Action\Group\ShowAction as GroupShowAction;
use App\Action\Group\UpdateAction as GroupUpdateAction;
use App\Action\User\CreateAction as UserCreateAction;
use App\Action\User\DeleteAction as UserDeleteAction;
use App\Action\User\ListAction as UserListAction;
use App\Action\User\PatchAction as UserPatchAction;
use App\Action\User\ShowAction as UserShowAction;
use App\Action\User\UpdateAction as UserUpdateAction;
use Oryx\Adr\Action\ActionInterface;
use PHPUnit\Framework\TestCase;

class AdrActionInterfaceTest extends TestCase
{
    public function testActionsImplementOryxAdrActionInterface(): void
    {
        $actions = [
            UserListAction::class,
            UserCreateAction::class,
            UserShowAction::class,
            UserUpdateAction::class,
            UserPatchAction::class,
            UserDeleteAction::class,
            GroupListAction::class,
            GroupCreateAction::class,
            GroupShowAction::class,
            GroupUpdateAction::class,
            GroupPatchAction::class,
            GroupDeleteAction::class,
        ];

        foreach ($actions as $actionClass) {
            $this->assertTrue(
                is_subclass_of($actionClass, ActionInterface::class),
                sprintf('%s must implement %s', $actionClass, ActionInterface::class)
            );
        }
    }
}
