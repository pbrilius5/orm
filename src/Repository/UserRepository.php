<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\DTO\UserApiDTO;
use Oryx\ORM\EntityManager;

class UserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithFilter(?string $search = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->like('u.email', ':search'))
               ->setParameter('search', "%{$search}%");
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all users with eager-loaded relations for API (avoids N+1).
     * Returns UserApiDTO[] with pre-computed roles and group data.
     */
    public function findAllForApi(): array
    {
        $results = $this->em->createQueryBuilder()
            ->select('u, ur, r, g')
            ->from(User::class, 'u')
            ->leftJoin('u.userRoles', 'ur')
            ->leftJoin('ur.role', 'r')
            ->leftJoin('u.group', 'g')
            ->getQuery()
            ->getResult();

        $dtos = [];
        $userRolesMap = [];
        $groupMap = [];

        // Single pass: group userRoles and groups by user
        foreach ($results as $row) {
            $user = $row[0];
            $userId = $user->getId()?->toString() ?? '';

            // Collect userRoles for this user
            if (isset($row[1]) && $row[1] !== null) {
                $userRolesMap[$userId][] = $row[1];
            }

            // Collect group for this user
            if (isset($row[3]) && $row[3] !== null && !isset($groupMap[$userId])) {
                $groupMap[$userId] = $row[3];
            }
        }

        // Create DTOs in second pass
        foreach ($results as $row) {
            $user = $row[0];
            $userId = $user->getId()?->toString() ?? '';

            // Only create DTO once per user
            if (!isset($dtos[$userId])) {
                $dtos[$userId] = UserApiDTO::fromEntity(
                    $user,
                    $userRolesMap[$userId] ?? [],
                    $groupMap[$userId] ?? null
                );
            }
        }

        return array_values($dtos);
    }

    /**
     * Find all users with filter and eager-loaded relations for API.
     */
    public function findAllWithFilterForApi(?string $search = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u, ur, r, g')
            ->from(User::class, 'u')
            ->leftJoin('u.userRoles', 'ur')
            ->leftJoin('ur.role', 'r')
            ->leftJoin('u.group', 'g');

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->like('u.email', ':search'))
               ->setParameter('search', "%{$search}%");
        }

        $results = $qb->getQuery()->getResult();

        $dtos = [];
        $userRolesMap = [];
        $groupMap = [];

        foreach ($results as $row) {
            $user = $row[0];
            $userId = $user->getId()?->toString() ?? '';

            if (isset($row[1]) && $row[1] !== null) {
                $userRolesMap[$userId][] = $row[1];
            }

            if (isset($row[3]) && $row[3] !== null && !isset($groupMap[$userId])) {
                $groupMap[$userId] = $row[3];
            }
        }

        foreach ($results as $row) {
            $user = $row[0];
            $userId = $user->getId()?->toString() ?? '';

            if (!isset($dtos[$userId])) {
                $dtos[$userId] = UserApiDTO::fromEntity(
                    $user,
                    $userRolesMap[$userId] ?? [],
                    $groupMap[$userId] ?? null
                );
            }
        }

        return array_values($dtos);
    }

    /**
     * Find user by ID (for internal use).
     */
    public function find(int|string $id): ?User
    {
        return $this->em->find(User::class, $id);
    }

    /**
     * Find user by ID with eager-loaded relations for API.
     */
    public function findForApi(int|string $id): ?UserApiDTO
    {
        $result = $this->em->createQueryBuilder()
            ->select('u, ur, r, g')
            ->from(User::class, 'u')
            ->leftJoin('u.userRoles', 'ur')
            ->leftJoin('ur.role', 'r')
            ->leftJoin('u.group', 'g')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result) {
            return null;
        }

        $user = $result[0];
        $userRoles = [];
        $group = null;

        // Collect userRoles
        if (isset($result[1]) && $result[1] !== null) {
            $userRoles[] = $result[1];
        }

        // Collect group
        if (isset($result[3]) && $result[3] !== null) {
            $group = $result[3];
        }

        return UserApiDTO::fromEntity($user, $userRoles, $group);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->em->persist($entity);

        if ($flush) {
            $this->em->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->em->remove($entity);

        if ($flush) {
            $this->em->flush();
        }
    }
}
