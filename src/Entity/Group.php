<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\UuidInterface;

/**
 * Group
 * @ORM\Entity
 * @ORM\Table(name="groups")
 */
class Group
{
    /**
     * @var UuidInterface
     * @ORM\Id
     * @ORM\Column(type="uuid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     */
    private ?UuidInterface $id = null;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
     * @ORM\OneToMany(targetEntity="User", mappedBy="group")
     */
    private $users;

    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getUsers(): \Doctrine\Common\Collections\Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            if ($user->getGroup() === $this) {
                $user->setGroup(null);
            }
        }

        return $this;
    }
}
