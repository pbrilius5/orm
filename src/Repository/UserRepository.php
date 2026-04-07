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
     * Returns User[] with pre-loaded userRoles and userGroups.
     */
    public function findAllForApi(): array
    {
        return $this->em->createQueryBuilder()
            ->select('u, ur, r, ug, g')
            ->from(User::class, 'u')
            ->leftJoin('u.userRoles', 'ur')
            ->leftJoin('ur.role', 'r')
            ->leftJoin('u.userGroups', 'ug')
            ->leftJoin('ug.group', 'g')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all users with filter and eager-loaded relations for API.
     */
    public function findAllWithFilterForApi(?string $search = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u, ur, r, ug, g')
            ->from(User::class, 'u')
            ->leftJoin('u.userRoles', 'ur')
            ->leftJoin('ur.role', 'r')
            ->leftJoin('u.userGroups', 'ug')
            ->leftJoin('ug.group', 'g');

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->like('u.email', ':search'))
               ->setParameter('search', "%{$search}%");
        }

        return $qb->getQuery()->getResult();
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
    public function findForApi(int|string $id): ?User
    {
        $result = $this->em->createQueryBuilder()
            ->select('u, ur, r, ug, g')
            ->from(User::class, 'u')
            ->leftJoin('u.userRoles', 'ur')
            ->leftJoin('ur.role', 'r')
            ->leftJoin('u.userGroups', 'ug')
            ->leftJoin('ug.group', 'g')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result) {
            return null;
        }

        return $result[0];
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
