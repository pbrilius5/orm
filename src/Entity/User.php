<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oryx\ORM\SodiumUuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: SodiumUuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: UserRole::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    private Collection $userRoles;

    #[ORM\OneToMany(targetEntity: UserGroup::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    private Collection $userGroups;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    public function hasGroup(string $groupName): bool
    {
        foreach ($this->userGroups as $userGroup) {
            if ($userGroup->getGroup()->getName() === $groupName && $userGroup->isActive()) {
                return true;
            }
        }
        return false;
    }

    public function addGroup(Group $group): self
    {
        foreach ($this->userGroups as $existingUserGroup) {
            if ($existingUserGroup->getGroup() === $group) {
                return $this;
            }
        }

        $userGroup = new UserGroup();
        $userGroup->setUser($this);
        $userGroup->setGroup($group);
        $this->userGroups->add($userGroup);
        $group->addUserGroup($userGroup);

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        foreach ($this->userGroups as $userGroup) {
            if ($userGroup->getGroup() === $group) {
                $this->userGroups->removeElement($userGroup);
                $group->removeUserGroup($userGroup);
                break;
            }
        }
        return $this;
    }

    public function getGroups(): array
    {
        return array_values($this->userGroups
            ->filter(fn($ug) => $ug->isActive())
            ->map(fn($ug) => $ug->getGroup()->getName())
            ->toArray());
    }

    public function getAllGroups(): array
    {
        return array_values($this->userGroups
            ->filter(fn($ug) => $ug->isActive())
            ->map(fn($ug) => $ug->getGroup())
            ->toArray());
    }

    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->userRoles as $userRole) {
            if ($userRole->getRole()->getName() === $roleName && $userRole->isActive()) {
                return true;
            }
        }
        return false;
    }

    public function addRole(Role $role): self
    {
        foreach ($this->userRoles as $existingUserRole) {
            if ($existingUserRole->getRole() === $role) {
                return $this;
            }
        }

        $userRole = new UserRole();
        $userRole->setUser($this);
        $userRole->setRole($role);
        $this->userRoles->add($userRole);
        $role->addUserRole($userRole);

        return $this;
    }

    public function removeRole(Role $role): self
    {
        foreach ($this->userRoles as $userRole) {
            if ($userRole->getRole() === $role) {
                $this->userRoles->removeElement($userRole);
                $role->removeUserRole($userRole);
                break;
            }
        }
        return $this;
    }

    public function getRoles(): array
    {
        return $this->getRoleNames();
    }

    public function getRoleNames(): array
    {
        return array_values($this->userRoles
            ->filter(fn($ur) => $ur->isActive())
            ->map(fn($ur) => $ur->getRole()->getName())
            ->toArray());
    }

    public function getAllRoles(): array
    {
        return array_values($this->userRoles
            ->filter(fn($ur) => $ur->isActive())
            ->map(fn($ur) => $ur->getRole())
            ->toArray());
    }

    public function setRoles(array $roleNames): self
    {
        $this->userRoles->clear();

        foreach ($roleNames as $roleName) {
            $role = new Role();
            $role->setName($roleName);

            $userRole = new UserRole();
            $userRole->setUser($this);
            $userRole->setRole($role);
            $userRole->setGrantedAt(new \DateTimeImmutable());
            $this->userRoles->add($userRole);
        }

        return $this;
    }

    public function getGamificationRoles(): array
    {
        return array_values($this->userRoles
            ->filter(fn($ur) => $ur->isActive() && $ur->getRole()->isGamificationRole())
            ->map(fn($ur) => $ur->getRole()->getName())
            ->toArray());
    }

    public function hasGamificationRole(string $roleName): bool
    {
        return in_array($roleName, $this->getGamificationRoles(), true);
    }
}
