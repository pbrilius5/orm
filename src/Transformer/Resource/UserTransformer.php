<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\Entity\User;
use League\Fractal\TransformerAbstract;

/**
 * Transforms a User entity for API output.
 */
class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['posts', 'group', 'userRoles', 'wands', 'patronuses'];

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

    /**
     * Include posts.
     *
     * @param User $user
     * @return \League\Fractal\Resource\Collection
     */
    public function includePosts(User $user)
    {
        return $this->collection($user->getPosts(), new \App\Transformer\Resource\PostTransformer());
    }

    /**
     * Include group.
     *
     * @param User $user
     * @return \League\Fractal\Resource\Item
     */
    public function includeGroup(User $user)
    {
        return $this->item($user->getGroup(), new \App\Transformer\Resource\GroupTransformer());
    }

    public function includeUserRoles(User $user)
    {
        return $this->collection($user->getUserRoles(), new UserRoleTransformer());
    }

    public function includeWands(User $user)
    {
        return $this->collection($user->getWands(), new WandTransformer());
    }

    public function includePatronuses(User $user)
    {
        return $this->collection($user->getPatronuses(), new PatronusTransformer());
    }
}
