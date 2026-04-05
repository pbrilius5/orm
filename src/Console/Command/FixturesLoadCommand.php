<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\Group;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\WizardRole;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Faker\Factory;
use Faker\Generator;
use Ramsey\Uuid\Doctrine\UuidType;
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

        $this->faker->unique(true);

        $connectionParams = $envConfig->getDatabaseParams();

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

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
            $schemaTool->dropSchema($metadatas);
            $schemaTool->createSchema($metadatas);
        } catch (\Throwable $e) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
            $schemaTool->createSchema($metadatas);
        }

        $groupCount = (int) $input->getOption('groups');
        $userCount = (int) $input->getOption('users');

        if ($input->getOption('seed') !== null) {
            $io->text('Auto-purging existing data (seeded fixtures require clean database)...');
        } elseif ($input->getOption('purge')) {
            $io->text('Purging existing data...');
        }

        $io->title('Loading Fixtures');

        $groups = $this->createGroups($io, $em, $groupCount);
        $roles = $this->createRoles($io, $em);
        $users = $this->createUsers($io, $em, $groups, $userCount);
        $this->createUserRoles($io, $em, $users, $roles);

        $em->flush();

        $io->success(sprintf(
            'Loaded %d groups, %d roles, %d users',
            $groupCount,
            count($roles),
            $userCount
        ));

        return Command::SUCCESS;
    }

    private function createGroups(SymfonyStyle $io, $em, int $count): array
    {
        $groups = [];
        $groupNames = [Group::USERS, 'Developers', 'Designers', 'Testers'];

        for ($i = 0; $i < $count; $i++) {
            $group = new Group();
            $group->setName($groupNames[$i] ?? 'Group ' . ($i + 1));
            $group->setCreatedAt(new \DateTimeImmutable());
            $em->persist($group);
            $groups[] = $group;
            $io->text(sprintf('  Group: <info>%s</info>', $group->getName()));
        }

        return $groups;
    }

    private function createRoles(SymfonyStyle $io, $em): array
    {
        $roles = [];

        $baseRole = new Role();
        $baseRole->setName(Role::USER);
        $baseRole->setDescription('Base user role');
        $em->persist($baseRole);
        $roles[Role::USER] = $baseRole;
        $io->text(sprintf('  Role: <info>%s</info>', Role::USER));

        $wizardRole = new WizardRole();
        $wizardRole->setName(WizardRole::NAME);
        $wizardRole->setDescription('Wizard role');
        $em->persist($wizardRole);
        $roles[WizardRole::NAME] = $wizardRole;
        $io->text(sprintf('  Role: <info>%s</info>', WizardRole::NAME));

        $architectRole = new ArchitectRole();
        $architectRole->setName(ArchitectRole::NAME);
        $architectRole->setDescription('Architect role');
        $em->persist($architectRole);
        $roles[ArchitectRole::NAME] = $architectRole;
        $io->text(sprintf('  Role: <info>%s</info>', ArchitectRole::NAME));

        $gameMasterRole = new GameMasterRole();
        $gameMasterRole->setName(GameMasterRole::NAME);
        $gameMasterRole->setDescription('Game Master role');
        $em->persist($gameMasterRole);
        $roles[GameMasterRole::NAME] = $gameMasterRole;
        $io->text(sprintf('  Role: <info>%s</info>', GameMasterRole::NAME));

        return $roles;
    }

    private function createUsers(SymfonyStyle $io, $em, array $groups, int $count): array
    {
        $users = [];

        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setEmail($this->faker->unique()->safeEmail);
            $user->setPassword(password_hash($this->faker->password, PASSWORD_BCRYPT));
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->addGroup($this->faker->randomElement($groups));
            $em->persist($user);
            $users[] = $user;
            $io->text(sprintf('  User: <info>%s</info>', $user->getEmail()));
        }

        return $users;
    }

    private function createUserRoles(SymfonyStyle $io, $em, array $users, array $roles): void
    {
        foreach ($users as $user) {
            $baseRole = $roles[Role::USER];
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($baseRole);
            $userRole->setGrantedAt(new \DateTimeImmutable());
            $em->persist($userRole);

            $wizardRole = $roles[WizardRole::NAME];
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($wizardRole);
            $userRole->setGrantedAt(new \DateTimeImmutable());
            $em->persist($userRole);

            if ($this->faker->boolean(30)) {
                $architectRole = $roles[ArchitectRole::NAME];
                $userRole = new UserRole();
                $userRole->setUser($user);
                $userRole->setRole($architectRole);
                $userRole->setGrantedAt(new \DateTimeImmutable());
                $em->persist($userRole);
            }

            if ($this->faker->boolean(10)) {
                $gameMasterRole = $roles[GameMasterRole::NAME];
                $userRole = new UserRole();
                $userRole->setUser($user);
                $userRole->setRole($gameMasterRole);
                $userRole->setGrantedAt(new \DateTimeImmutable());
                $em->persist($userRole);
            }
        }
    }
}
