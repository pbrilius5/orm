<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Group;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\EnvironmentConfig;

class FixturesLoadCommand extends Command
{
    protected static $defaultName = 'oryx:fixtures:load';
    protected static $defaultDescription = 'Load demo fixtures using Faker';

    private Generator $faker;

    public function __construct()
    {
        parent::__construct();
        $this->faker = Factory::create();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'groups',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of groups to generate',
                3
            )
            ->addOption(
                'users',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of users to generate',
                10
            )
            ->addOption(
                'posts',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of posts per user',
                2
            )
            ->addOption(
                'purge',
                null,
                InputOption::VALUE_NONE,
                'Purge existing data before loading'
            )
            ->addOption(
                'seed',
                null,
                InputOption::VALUE_REQUIRED,
                'Random seed for reproducibility',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = getcwd();
        $envConfig = new EnvironmentConfig($projectRoot);

        if ($input->getOption('seed') !== null) {
            $this->faker->seed((int) $input->getOption('seed'));
        }

        // Reset Faker's unique generator to prevent collisions across runs
        $this->faker->unique(true);

        $connectionParams = $envConfig->getDatabaseParams();

        if ($connectionParams['driver'] === 'pdo_mysql') {
            try {
                $connection = DriverManager::getConnection($connectionParams);
            } catch (\Throwable $e) {
                $io->warning('MySQL unavailable, falling back to SQLite');
                $connectionParams = [
                    'driver' => 'pdo_sqlite',
                    'path' => $projectRoot . '/var/data/orm.db',
                ];
                $connection = DriverManager::getConnection($connectionParams);
            }
        } else {
            $connection = DriverManager::getConnection($connectionParams);
        }

        $doctrineConfig = new Configuration();
        $xmlDriver = new SimplifiedXmlDriver([
            $projectRoot . '/schema' => 'App\Entity',
        ], '.orm.xml');
        $doctrineConfig->setMetadataDriverImpl($xmlDriver);
        $doctrineConfig->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_NEVER);
        $doctrineConfig->setProxyDir(sys_get_temp_dir());
        $doctrineConfig->setProxyNamespace('Oryx\ORM\Proxy');

        $em = DoctrineEntityManager::create($connection, $doctrineConfig);

        try {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
            $schemaTool->createSchema($metadatas);
        } catch (\Throwable $e) {
            // Schema may already exist, continue
        }

        $groupCount = (int) $input->getOption('groups');
        $userCount = (int) $input->getOption('users');
        $postCount = (int) $input->getOption('posts');

        // Auto-purge when seed is provided to ensure deterministic reproducibility
        if ($input->getOption('seed') !== null) {
            $io->text('Auto-purging existing data (seeded fixtures require clean database)...');
            $connection->executeStatement('DELETE FROM posts');
            $connection->executeStatement('DELETE FROM users');
            $connection->executeStatement('DELETE FROM `groups`');
        } elseif ($input->getOption('purge')) {
            $io->text('Purging existing data...');
            $connection->executeStatement('DELETE FROM posts');
            $connection->executeStatement('DELETE FROM users');
            $connection->executeStatement('DELETE FROM `groups`');
        }

        $io->title('Loading Fixtures');

        $groups = [];
        for ($i = 0; $i < $groupCount; $i++) {
            $group = new Group();
            $group->setName(ucfirst($this->faker->unique()->word) . ' Group');
            $group->setCreatedAt(new \DateTimeImmutable());
            $em->persist($group);
            $groups[] = $group;
            $io->text(sprintf('  Group: <info>%s</info>', $group->getName()));
        }

        $users = [];
        for ($i = 0; $i < $userCount; $i++) {
            $user = new User();
            $user->setEmail($this->faker->unique()->safeEmail);
            $user->setPassword(password_hash($this->faker->password, PASSWORD_BCRYPT));
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setGroup($this->faker->randomElement($groups));
            $em->persist($user);
            $users[] = $user;
            $io->text(sprintf('  User: <info>%s</info>', $user->getEmail()));
        }

        for ($i = 0; $i < $postCount; $i++) {
            foreach ($users as $user) {
                $post = new Post();
                $post->setTitle($this->faker->sentence);
                $post->setContent($this->faker->paragraphs(3, true));
                $post->setCreatedAt(new \DateTimeImmutable());
                $post->setAuthor($user);
                $em->persist($post);
            }
        }

        $em->flush();

        $io->success(sprintf(
            'Loaded %d groups, %d users, %d posts',
            $groupCount,
            $userCount,
            $userCount * $postCount
        ));

        return Command::SUCCESS;
    }
}
