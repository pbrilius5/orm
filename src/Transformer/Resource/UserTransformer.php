<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\DTO\UserApiDTO;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['group', 'userRoles'];

    public function transform(UserApiDTO $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'roles' => $user->roles,
            'created_at' => $user->createdAt->format('c'),
            'updated_at' => $user->updatedAt?->format('c'),
        ];
    }

    public function includeGroup(UserApiDTO $user)
    {
        if ($user->groupId === null) {
            return null;
        }

        return $this->item([
            'id' => $user->groupId,
            'name' => $user->groupName,
        ], new GroupTransformer());
    }

    public function includeUserRoles(UserApiDTO $user)
    {
        return $this->collection($user->userRoles, new UserRoleTransformer());
    }
}
