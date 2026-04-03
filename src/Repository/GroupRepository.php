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
     * Returns GroupApiDTO[] with pre-computed users data.
     */
    public function findAllForApi(): array
    {
        $results = $this->em->createQueryBuilder()
            ->select('g, u')
            ->from(Group::class, 'g')
            ->leftJoin('g.users', 'u')
            ->getQuery()
            ->getResult();

        $dtos = [];
        $groupUsersMap = [];

        // Group users by group
        foreach ($results as $row) {
            $group = $row[0];
            $groupId = $group->getId()?->toString() ?? '';

            if (!isset($groupUsersMap[$groupId])) {
                $groupUsersMap[$groupId] = [];
            }

            // If there's a joined user (not the group itself)
            if (isset($row[1]) && $row[1] !== null) {
                $groupUsersMap[$groupId][] = $row[1];
            }
        }

        // Create DTOs
        foreach ($results as $row) {
            $group = $row[0];
            $groupId = $group->getId()?->toString() ?? '';

            // Only create DTO once per group
            if (!isset($dtos[$groupId])) {
                $dtos[$groupId] = GroupApiDTO::fromEntity(
                    $group,
                    $groupUsersMap[$groupId] ?? []
                );
            }
        }

        return array_values($dtos);
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
        $results = $this->em->createQueryBuilder()
            ->select('g, u')
            ->from(Group::class, 'g')
            ->leftJoin('g.users', 'u');

        if ($search !== null && $search !== '') {
            $results->andWhere($results->expr()->like('g.name', ':search'))
               ->setParameter('search', "%{$search}%");
        }

        $results = $results->getQuery()->getResult();

        $dtos = [];
        $groupUsersMap = [];

        foreach ($results as $row) {
            $group = $row[0];
            $groupId = $group->getId()?->toString() ?? '';

            if (!isset($groupUsersMap[$groupId])) {
                $groupUsersMap[$groupId] = [];
            }

            if (isset($row[1]) && $row[1] !== null) {
                $groupUsersMap[$groupId][] = $row[1];
            }
        }

        foreach ($results as $row) {
            $group = $row[0];
            $groupId = $group->getId()?->toString() ?? '';

            if (!isset($dtos[$groupId])) {
                $dtos[$groupId] = GroupApiDTO::fromEntity(
                    $group,
                    $groupUsersMap[$groupId] ?? []
                );
            }
        }

        return array_values($dtos);
    }

    public function find(UuidInterface|string|int $id): ?Group
    {
        return $this->em->getRepository(Group::class)->find($id);
    }

    /**
     * Find group by ID with eager-loaded users for API.
     */
    public function findForApi(int|string $id): ?GroupApiDTO
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

        $users = [];
        foreach ($results as $row) {
            if (isset($row[1]) && $row[1] !== null) {
                $users[] = $row[1];
            }
        }

        return GroupApiDTO::fromEntity($results[0][0], $users);
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
