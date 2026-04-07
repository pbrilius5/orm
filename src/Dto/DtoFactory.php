<?php

declare(strict_types=1);

namespace App\Dto;

use App\DTO\ArchitectRoleApiDTO;
use App\DTO\DesignerGroupApiDTO;
use App\DTO\DeveloperGroupApiDTO;
use App\DTO\GameMasterRoleApiDTO;
use App\DTO\TesterGroupApiDTO;
use App\DTO\UserApiDTO;
use App\DTO\WizardRoleApiDTO;
use App\Entity\ArchitectRole;
use App\Entity\DesignerGroup;
use App\Entity\DeveloperGroup;
use App\Entity\GameMasterRole;
use App\Entity\Group;
use App\Entity\Role;
use App\Entity\TesterGroup;
use App\Entity\User;
use App\Entity\WizardRole;

class DtoFactory
{
    public function create(object $entity, array $context = []): object
    {
        return match (true) {
            $entity instanceof User => $this->toUserDto(
                $entity,
                $context['userRoles'] ?? [],
                $context['workGroup'] ?? null,
                $context['gamificationRoles'] ?? []
            ),
            $entity instanceof DeveloperGroup => $this->toDeveloperGroupDto(
                $entity,
                $context['users'] ?? [],
                $context['gamificationRoles'] ?? []
            ),
            $entity instanceof DesignerGroup => $this->toDesignerGroupDto(
                $entity,
                $context['users'] ?? [],
                $context['gamificationRoles'] ?? []
            ),
            $entity instanceof TesterGroup => $this->toTesterGroupDto(
                $entity,
                $context['users'] ?? [],
                $context['gamificationRoles'] ?? []
            ),
            $entity instanceof Group => throw new \RuntimeException('Base Group entity is hidden from API'),
            $entity instanceof Role => throw new \RuntimeException('Base Role entity is hidden from API'),
            default => throw new \RuntimeException('Unknown entity: ' . get_class($entity)),
        };
    }

    public function toUserDto(User $user, array $userRoles = [], ?Group $workGroup = null, array $gamificationRoles = []): UserApiDTO
    {
        return UserApiDTO::fromEntity($user, $userRoles, $workGroup, $gamificationRoles);
    }

    public function toDeveloperGroupDto(DeveloperGroup $group, array $users = [], array $gamificationRoles = []): DeveloperGroupApiDTO
    {
        return DeveloperGroupApiDTO::fromEntity($group, $users, $gamificationRoles);
    }

    public function toDesignerGroupDto(DesignerGroup $group, array $users = [], array $gamificationRoles = []): DesignerGroupApiDTO
    {
        return DesignerGroupApiDTO::fromEntity($group, $users, $gamificationRoles);
    }

    public function toTesterGroupDto(TesterGroup $group, array $users = [], array $gamificationRoles = []): TesterGroupApiDTO
    {
        return TesterGroupApiDTO::fromEntity($group, $users, $gamificationRoles);
    }

    public function toWizardRoleDto(WizardRole $role): WizardRoleApiDTO
    {
        return WizardRoleApiDTO::fromEntity($role);
    }

    public function toArchitectRoleDto(ArchitectRole $role): ArchitectRoleApiDTO
    {
        return ArchitectRoleApiDTO::fromEntity($role);
    }

    public function toGameMasterRoleDto(GameMasterRole $role): GameMasterRoleApiDTO
    {
        return GameMasterRoleApiDTO::fromEntity($role);
    }
}
