<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Group;
use Oryx\ORM\EntityManager;
use Ramsey\Uuid\UuidInterface;

class GroupRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function findAll(): array
    {
        return $this->em->getRepository(Group::class)->findAll();
    }

    public function findAllWithFilter(?string $search = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g');

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->like('g.name', ':search'))
               ->setParameter('search', "%{$search}%");
        }

        return $qb->getQuery()->getResult();
    }

    public function find(UuidInterface|string|int $id): ?Group
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
