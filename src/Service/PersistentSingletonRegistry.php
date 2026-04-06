<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PersistentSingleton;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PersistentSingletonRegistry
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private array $cache = [];

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $record = $this->entityManager->getRepository(PersistentSingleton::class)->findOneBy(['key' => $key]);

        if ($record === null) {
            return null;
        }

        try {
            $value = unserialize($record->getValue(), ['allowed_classes' => false]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to unserialize persistent singleton', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        $this->cache[$key] = $value;

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $record = $this->entityManager->getRepository(PersistentSingleton::class)->findOneBy(['key' => $key]);

        if ($record === null) {
            $record = new PersistentSingleton();
            $record->setKey($key);
        }

        $record->setValue(serialize($value));
        $record->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        $this->cache[$key] = $value;
    }

    public function remove(string $key): void
    {
        $record = $this->entityManager->getRepository(PersistentSingleton::class)->findOneBy(['key' => $key]);

        if ($record !== null) {
            $this->entityManager->remove($record);
            $this->entityManager->flush();
        }

        unset($this->cache[$key]);
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->cache)) {
            return true;
        }

        $count = $this->entityManager->getRepository(PersistentSingleton::class)->count(['key' => $key]);

        return $count > 0;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}
