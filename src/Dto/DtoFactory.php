<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Group;
use App\Entity\User;

class DtoFactory
{
    public function toUserDto(User $user, array $userRoles = [], ?Group $group = null): UserApiDTO
    {
        return UserApiDTO::fromEntity($user, $userRoles, $group);
    }

    public function toGroupDto(Group $group, array $users = []): GroupApiDTO
    {
        return GroupApiDTO::fromEntity($group, $users);
    }

    public function create(string $dtoClass, object $entity, array $context = []): object
    {
        return match ($dtoClass) {
            UserApiDTO::class => $this->toUserDto(
                $entity,
                $context['userRoles'] ?? [],
                $context['group'] ?? null
            ),
            GroupApiDTO::class => $this->toGroupDto(
                $entity,
                $context['users'] ?? []
            ),
            default => throw new \RuntimeException("Unknown DTO: $dtoClass"),
        };
    }
}
