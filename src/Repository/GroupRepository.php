<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Group;
use App\DTO\GroupApiDTO;
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

    /**
     * Find all groups with eager-loaded users for API (avoids N+1).
     * Returns Group[] with pre-loaded users.
     */
    public function findAllForApi(): array
    {
        return $this->em->createQueryBuilder()
            ->select('g, u')
            ->from(Group::class, 'g')
            ->leftJoin('g.users', 'u')
            ->getQuery()
            ->getResult();
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

    /**
     * Find all groups with filter and eager-loaded users for API.
     */
    public function findAllWithFilterForApi(?string $search = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g, u')
            ->from(Group::class, 'g')
            ->leftJoin('g.users', 'u');

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

    /**
     * Find group by ID with eager-loaded users for API.
     */
    public function findForApi(int|string $id): ?Group
    {
        $results = $this->em->createQueryBuilder()
            ->select('g, u')
            ->from(Group::class, 'g')
            ->leftJoin('g.users', 'u')
            ->where('g.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        if (!$results) {
            return null;
        }

        return $results[0][0];
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
