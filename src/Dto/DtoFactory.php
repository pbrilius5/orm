<?php

declare(strict_types=1);

namespace App\Dto;

use App\DTO\ArchitectRoleApiDTO;
use App\DTO\DesignerGroupApiDTO;
use App\DTO\DeveloperGroupApiDTO;
use App\DTO\GameMasterRoleApiDTO;
use App\DTO\GroupApiDTO;
use App\DTO\RoleApiDTO;
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
                $context['users'] ?? []
            ),
            $entity instanceof DesignerGroup => $this->toDesignerGroupDto(
                $entity,
                $context['users'] ?? []
            ),
            $entity instanceof TesterGroup => $this->toTesterGroupDto(
                $entity,
                $context['users'] ?? []
            ),
            $entity instanceof Group => $this->toGroupDto(
                $entity,
                $context['users'] ?? []
            ),
            $entity instanceof WizardRole => $this->toWizardRoleDto($entity),
            $entity instanceof ArchitectRole => $this->toArchitectRoleDto($entity),
            $entity instanceof GameMasterRole => $this->toGameMasterRoleDto($entity),
            $entity instanceof Role => $this->toRoleDto($entity),
            default => throw new \RuntimeException('Unknown entity: ' . get_class($entity)),
        };
    }

    public function toUserDto(User $user, array $userRoles = [], ?Group $workGroup = null, array $gamificationRoles = []): UserApiDTO
    {
        return UserApiDTO::fromEntity($user, $userRoles, $workGroup, $gamificationRoles);
    }

    public function toGroupDto(Group $group, array $users = []): GroupApiDTO
    {
        return GroupApiDTO::fromEntity($group, $users);
    }

    public function toDeveloperGroupDto(DeveloperGroup $group, array $users = []): DeveloperGroupApiDTO
    {
        return DeveloperGroupApiDTO::fromEntity($group, $users);
    }

    public function toDesignerGroupDto(DesignerGroup $group, array $users = []): DesignerGroupApiDTO
    {
        return DesignerGroupApiDTO::fromEntity($group, $users);
    }

    public function toTesterGroupDto(TesterGroup $group, array $users = []): TesterGroupApiDTO
    {
        return TesterGroupApiDTO::fromEntity($group, $users);
    }

    public function toRoleDto(Role $role): RoleApiDTO
    {
        return RoleApiDTO::fromEntity($role);
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
