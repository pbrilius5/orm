<?php

declare(strict_types=1);

namespace App\Handler\Console;

use App\Command\Console\LoadFixturesCommand;
use App\Entity\Group;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserRole;
use App\Entity\WizardRole;
use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Faker\Factory;
use Faker\Generator;
use Ramsey\Uuid\Doctrine\UuidType;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;

class LoadFixturesHandler
{
    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function handle(LoadFixturesCommand $command): array
    {
        $this->logger?->info('Loading fixtures: ' . $command->users . ' users, ' . $command->groups . ' groups');

        $projectRoot = getcwd();
        $faker = Factory::create();

        if ($command->seed !== null) {
            $faker->seed($command->seed);
        }
        $faker->unique(true);

        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'path' => $projectRoot . '/var/data/orm.db',
        ];

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $connection = DriverManager::getConnection($connectionParams);

        $doctrineConfig = new Configuration();
        $xmlDriver = new SimplifiedXmlDriver([
            $projectRoot . '/schema' => 'App\Entity',
        ], '.orm.xml');
        $doctrineConfig->setMetadataDriverImpl($xmlDriver);
        $doctrineConfig->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_NEVER);
        $doctrineConfig->setProxyDir(sys_get_temp_dir());
        $doctrineConfig->setProxyNamespace('Oryx\ORM\Proxy');

        $em = DoctrineEntityManager::create($connection, $doctrineConfig);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        if ($command->purge || $command->seed !== null) {
            $schemaTool->dropSchema($metadatas);
        }
        $schemaTool->createSchema($metadatas);

        $groups = [];
        $groupNames = ['Developers', 'Designers', 'Testers', 'Managers', 'Analysts'];

        for ($i = 0; $i < $command->groups; $i++) {
            $group = new Group();
            $group->setName($groupNames[$i] ?? 'Group ' . ($i + 1));
            $group->setCreatedAt(new \DateTimeImmutable());
            $em->persist($group);
            $groups[] = $group;
        }

        $baseRole = new Role();
        $baseRole->setName(Role::USER);
        $em->persist($baseRole);

        $wizardRole = new WizardRole();
        $wizardRole->setName(WizardRole::NAME);
        $em->persist($wizardRole);

        $architectRole = new ArchitectRole();
        $architectRole->setName(ArchitectRole::NAME);
        $em->persist($architectRole);

        $gameMasterRole = new GameMasterRole();
        $gameMasterRole->setName(GameMasterRole::NAME);
        $em->persist($gameMasterRole);

        $em->flush();

        for ($i = 0; $i < $command->users; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail);
            $user->setPassword(password_hash($faker->password, PASSWORD_BCRYPT));
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->addGroup($faker->randomElement($groups));
            $em->persist($user);

            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($baseRole);
            $em->persist($userRole);

            $userRoleWiz = new UserRole();
            $userRoleWiz->setUser($user);
            $userRoleWiz->setRole($wizardRole);
            $em->persist($userRoleWiz);

            if ($faker->boolean(30)) {
                $userRoleArch = new UserRole();
                $userRoleArch->setUser($user);
                $userRoleArch->setRole($architectRole);
                $em->persist($userRoleArch);
            }

            if ($faker->boolean(10)) {
                $userRoleGm = new UserRole();
                $userRoleGm->setUser($user);
                $userRoleGm->setRole($gameMasterRole);
                $em->persist($userRoleGm);
            }
        }

        $em->flush();

        $this->logger?->info('Fixtures loaded successfully');

        return [
            'groups' => count($groups),
            'users' => $command->users,
        ];
    }
}
