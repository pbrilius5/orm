<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Group;
use App\Repository\GroupRepository;
use Oryx\ORM\EntityManager;

class GroupController
{
    private EntityManager $em;
    private GroupRepository $repository;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repository = new GroupRepository($em);
    }

    public function index(): array
    {
        $groups = $this->repository->findAll();
        return ['groups' => $groups];
    }

    public function show(int $id): ?Group
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Group
    {
        $group = new Group();
        $group->setName($data['name']);
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($group);
        $this->em->flush();

        return $group;
    }

    public function update(int $id, array $data): ?Group
    {
        $group = $this->repository->find($id);

        if (!$group) {
            return null;
        }

        if (isset($data['name'])) {
            $group->setName($data['name']);
        }

        $this->em->flush();

        return $group;
    }

    public function delete(int $id): bool
    {
        $group = $this->repository->find($id);

        if (!$group) {
            return false;
        }

        $this->em->remove($group);
        $this->em->flush();

        return true;
    }
}
