<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;

class GroupRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function findAll(): array
    {
        return $this->em->getRepository(Group::class)->findAll();
    }

    public function find(UuidInterface $id): ?Group
    {
        return $this->em->getRepository(Group::class)->find($id);
    }

    public function save(Group $group): void
    {
        $this->em->persist($group);
        $this->em->flush();
    }

    public function delete(Group $group): void
    {
        $this->em->remove($group);
        $this->em->flush();
    }
}
