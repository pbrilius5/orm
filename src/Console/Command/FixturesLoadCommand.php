<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\ArchitectRole;
use App\Entity\DeveloperGroup;
use App\Entity\DesignerGroup;
use App\Entity\GameMasterRole;
use App\Entity\Group;
use App\Entity\Role;
use App\Entity\TesterGroup;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserRole;
use App\Entity\WizardRole;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Oryx\ORM\Mapping\Driver\XmlThenAttributeDriver;
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
        parent::__construct('oryx:fixtures:load');
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
                4
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
        $driver = new XmlThenAttributeDriver($projectRoot . '/schema', $projectRoot . '/src/Entity');
        $doctrineConfig->setMetadataDriverImpl($driver);
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
        $this->createUserRoles($io, $em, $users, $roles, $groups);

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
        $groupClasses = [
            Group::class,
            DeveloperGroup::class,
            DesignerGroup::class,
            TesterGroup::class,
        ];
        $groupNames = [Group::USERS, 'Developers', 'Designers', 'Testers'];

        for ($i = 0; $i < $count; $i++) {
            $groupClass = $groupClasses[$i] ?? Group::class;
            $group = new $groupClass();
            $group->setName($groupNames[$i] ?? 'Group ' . ($i + 1));
            $group->setCreatedAt(new \DateTimeImmutable());
            $em->persist($group);
            $groups[] = $group;
            $io->text(sprintf('  Group: <info>%s</info> (%s)', $group->getName(), (new \ReflectionClass($group))->getShortName()));
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
            $em->persist($user);
            $users[] = $user;
            $io->text(sprintf('  User: <info>%s</info>', $user->getEmail()));
        }

        $em->flush();

        foreach ($users as $user) {
            $group = $this->faker->randomElement($groups);
            $userGroup = new UserGroup();
            $userGroup->setUser($user);
            $userGroup->setGroup($group);
            $userGroup->setGrantedAt(new \DateTimeImmutable());
            $em->persist($userGroup);
            $user->getUserGroups()->add($userGroup);
            $group->getUserGroups()->add($userGroup);
        }

        return $users;
    }

    private function createUserRoles(SymfonyStyle $io, $em, array $users, array $roles, array $groups): void
    {
        $groupGamificationMap = [];
        foreach ($groups as $group) {
            $groupClass = get_class($group);
            if (!isset($groupGamificationMap[$groupClass])) {
                $groupGamificationMap[$groupClass] = [];
            }
            switch ($groupClass) {
                case TesterGroup::class:
                    $groupGamificationMap[$groupClass][] = $roles[WizardRole::NAME];
                    break;
                case DesignerGroup::class:
                    $groupGamificationMap[$groupClass][] = $roles[ArchitectRole::NAME];
                    break;
                case DeveloperGroup::class:
                    $groupGamificationMap[$groupClass][] = $roles[GameMasterRole::NAME];
                    break;
            }
        }

        foreach ($users as $user) {
            $candidateRoles = [$roles[Role::USER]];

            foreach ($user->getUserGroups() as $userGroup) {
                $group = $userGroup->getGroup();
                $groupClass = get_class($group);
                if (isset($groupGamificationMap[$groupClass])) {
                    foreach ($groupGamificationMap[$groupClass] as $groupRole) {
                        $candidateRoles[] = $groupRole;
                    }
                }
            }

            $selectedRole = $this->faker->randomElement($candidateRoles);

            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($selectedRole);
            $userRole->setGrantedAt(new \DateTimeImmutable());
            $em->persist($userRole);
        }
    }
}
