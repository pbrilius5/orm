<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DesignerGroup;
use App\Entity\DeveloperGroup;
use App\Entity\Group;
use App\DTO\GroupApiDTO;
use App\Entity\TesterGroup;
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

    public function findAllApiClasses(): array
    {
        return array_merge(
            $this->em->getRepository(DeveloperGroup::class)->findAll(),
            $this->em->getRepository(DesignerGroup::class)->findAll(),
            $this->em->getRepository(TesterGroup::class)->findAll(),
        );
    }

    /**
     * Find all groups with eager-loaded users for API (avoids N+1).
     * Returns Group[] with pre-loaded users.
     */
    public function findAllForApi(): array
    {
        return $this->findAllApiClasses();
    }

    /**
     * Find all groups with pagination and eager-loaded users.
     */
    public function findAllForApiPaginated(?int $limit, ?int $offset): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g')
            ->from(DeveloperGroup::class, 'g')
            ->setMaxResults($limit ?? 100)
            ->setFirstResult($offset ?? 0);

        $developerResults = $qb->getQuery()->getResult();

        $qb2 = $this->em->createQueryBuilder()
            ->select('g')
            ->from(DesignerGroup::class, 'g')
            ->setMaxResults($limit ?? 100)
            ->setFirstResult($offset ?? 0);
        $designerResults = $qb2->getQuery()->getResult();

        $qb3 = $this->em->createQueryBuilder()
            ->select('g')
            ->from(TesterGroup::class, 'g')
            ->setMaxResults($limit ?? 100)
            ->setFirstResult($offset ?? 0);
        $testerResults = $qb3->getQuery()->getResult();

        return array_merge($developerResults, $designerResults, $testerResults);
    }

    /**
     * Count all groups in database.
     */
    public function countAll(): int
    {
        return count($this->findAllApiClasses());
    }

    /**
     * Count all groups matching search filter.
     */
    public function countAllWithFilter(?string $search): int
    {
        return count($this->findAllWithFilter($search));
    }

    public function findAllWithFilter(?string $search = null): array
    {
        $results = [];
        foreach ([DeveloperGroup::class, DesignerGroup::class, TesterGroup::class] as $class) {
            $qb = $this->em->createQueryBuilder()
                ->select('g')
                ->from($class, 'g');

            if ($search !== null && $search !== '') {
                $qb->andWhere($qb->expr()->like('g.name', ':search'))
                   ->setParameter('search', "%{$search}%");
            }

            $results = array_merge($results, $qb->getQuery()->getResult());
        }
        return $results;
    }

    /**
     * Find all groups with filter and eager-loaded users for API.
     */
    public function findAllWithFilterForApi(?string $search = null): array
    {
        return $this->findAllWithFilter($search);
    }

    public function find(UuidInterface|string|int $id): ?Group
    {
        foreach ([DeveloperGroup::class, DesignerGroup::class, TesterGroup::class] as $class) {
            $result = $this->em->getRepository($class)->find($id);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }

    /**
     * Find group by ID with eager-loaded users for API.
     */
    public function findForApi(int|string $id): ?Group
    {
        return $this->find($id);
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
