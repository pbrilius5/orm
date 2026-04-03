<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleCommandsTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->application->setName('Oryx ORM Console');
        $this->application->setVersion('1.0.0');

        // Add commands
        $this->application->add(new \App\Command\Console\CacheStatusCommand());
        $this->application->add(new \App\Command\Console\CacheQueryCommand());
        $this->application->add(new \App\Command\Console\CacheClearCommand());
    }

    public function testCacheStatusCommandCanBeLoaded(): void
    {
        $command = $this->application->find('oryx:cache:status');
        $this->assertNotNull($command);
    }

    public function testCacheStatusCommandExecutes(): void
    {
        $command = $this->application->find('oryx:cache:status');
        $tester = new CommandTester($command);

        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Doctrine Regional Cache Status', $output);
    }

    public function testCacheQueryCommandCanBeLoaded(): void
    {
        $command = $this->application->find('oryx:cache:query');
        $this->assertNotNull($command);
    }

    public function testCacheQueryCommandExecutes(): void
    {
        $command = $this->application->find('oryx:cache:query');
        $tester = new CommandTester($command);

        $tester->execute(['--pattern' => 'App*']);

        $output = $tester->getDisplay();
        // Should show "Cache is not enabled" or key listing
        $this->assertStringContainsString('Cache', $output);
    }

    public function testCacheClearCommandCanBeLoaded(): void
    {
        $command = $this->application->find('oryx:cache:clear');
        $this->assertNotNull($command);
    }

    public function testCacheClearCommandWithoutForceShowsMessage(): void
    {
        $command = $this->application->find('oryx:cache:clear');
        $tester = new CommandTester($command);

        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('--force', $output);
    }

    public function testCacheClearCommandWithForceReturnsSuccess(): void
    {
        $command = $this->application->find('oryx:cache:clear');
        $tester = new CommandTester($command);

        $tester->execute(['--force' => true]);

        // Should complete without error (even if cache not enabled)
        $this->assertEquals(0, $tester->getStatusCode());
    }
}
