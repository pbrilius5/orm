<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\ArchitectRole;
use App\Entity\DeveloperGroup;
use App\Entity\DesignerGroup;
use App\Entity\GameMasterRole;
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

        $userCount = (int) $input->getOption('users');

        if ($input->getOption('seed') !== null) {
            $io->text('Auto-purging existing data (seeded fixtures require clean database)...');
        } elseif ($input->getOption('purge')) {
            $io->text('Purging existing data...');
        }

        $io->title('Loading Fixtures');

        $groups = $this->createGroups($io, $em);
        $roles = $this->createRoles($io, $em);
        $users = $this->createUsers($io, $em, $groups, $userCount);
        $this->createUserRoles($io, $em, $users, $roles, $groups);

        $em->flush();

        $io->success(sprintf(
            'Loaded %d groups, %d roles, %d users',
            count($groups),
            count($roles),
            $userCount
        ));

        return Command::SUCCESS;
    }

    private function createGroups(SymfonyStyle $io, $em): array
    {
        $groups = [];
        $groupClasses = [
            DeveloperGroup::class,
            DesignerGroup::class,
            TesterGroup::class,
        ];
        $groupNames = ['Developers', 'Designers', 'Testers'];

        foreach ($groupClasses as $i => $groupClass) {
            $group = new $groupClass();
            $group->setName($groupNames[$i]);
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
        $gamificationRoles = [
            $roles[WizardRole::NAME],
            $roles[ArchitectRole::NAME],
            $roles[GameMasterRole::NAME],
        ];

        foreach ($users as $user) {
            $selectedRole = $this->faker->randomElement($gamificationRoles);

            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($selectedRole);
            $userRole->setGrantedAt(new \DateTimeImmutable());
            $em->persist($userRole);
        }
    }
}
