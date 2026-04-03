<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\Entity\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['posts', 'group', 'userRoles'];

    public function transform(User $user): array
    {
        $roles = [];
        foreach ($user->getUserRoles() as $userRole) {
            if ($userRole->isActive()) {
                $roles[] = $userRole->getRole()->getName();
            }
        }

        return [
            'id' => $user->getId() ?? 0,
            'email' => $user->getEmail(),
            'roles' => $roles,
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt() ? $user->getUpdatedAt()->format('c') : null,
        ];
    }

    public function includePosts(User $user)
    {
        return $this->collection($user->getPosts(), new PostTransformer());
    }

    public function includeGroup(User $user)
    {
        return $this->item($user->getGroup(), new GroupTransformer());
    }

    public function includeUserRoles(User $user)
    {
        return $this->collection($user->getUserRoles(), new UserRoleTransformer());
    }
}
