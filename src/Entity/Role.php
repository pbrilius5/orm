<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oryx\ORM\SodiumUuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'roles')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'role' => Role::class,
    'wizard' => WizardRole::class,
    'architect' => ArchitectRole::class,
    'game_master' => GameMasterRole::class,
])]
class Role
{
    public const USER = 'ROLE_USER';
    public const WIZARD = 'ROLE_WIZARD';
    public const ARCHITECT = 'ROLE_ARCHITECT';
    public const GAME_MASTER = 'ROLE_GAME_MASTER';
    public const MUGGLE = 'ROLE_MUGGLE';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: SodiumUuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: UserRole::class, mappedBy: 'role', cascade: ['persist'], orphanRemoval: true)]
    private Collection $userRoles;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isGamificationRole(): bool
    {
        return false;
    }

    public function getRank(): int
    {
        return 0;
    }

    public function addUserRole(UserRole $userRole): self
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles->add($userRole);
        }
        return $this;
    }

    public function removeUserRole(UserRole $userRole): self
    {
        $this->userRoles->removeElement($userRole);
        return $this;
    }

    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }
}
