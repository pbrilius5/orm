<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Role;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\Wand;
use App\Entity\Patronus;
use App\Entity\InvisibilityCloak;
use App\Entity\Post;
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
                'teams',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of teams to generate',
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
            $schemaTool->dropSchema($metadatas);
            $schemaTool->createSchema($metadatas);
        } catch (\Throwable $e) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
            $schemaTool->createSchema($metadatas);
        }

        $teamCount = (int) $input->getOption('teams');
        $userCount = (int) $input->getOption('users');
        $postCount = (int) $input->getOption('posts');

        if ($input->getOption('seed') !== null) {
            $io->text('Auto-purging existing data (seeded fixtures require clean database)...');
        } elseif ($input->getOption('purge')) {
            $io->text('Purging existing data...');
        }

        $io->title('Loading Wizard Platform Fixtures');

        $teams = $this->createTeams($io, $em, $teamCount);
        $roles = $this->createRoles($io, $em, $teams);
        $users = $this->createUsers($io, $em, $teams, $roles, $userCount);
        $this->createUserRoles($io, $em, $users, $teams, $roles);
        $this->createWands($io, $em, $users, $roles);
        $this->createPatronuses($io, $em, $users, $teams, $roles);
        $this->createInvisibilityCloaks($io, $em, $users, $teams);
        $this->createPosts($io, $em, $users, $postCount);

        $em->flush();

        $io->success(sprintf(
            'Loaded %d teams, %d roles, %d users, %d posts',
            $teamCount,
            count($roles),
            $userCount,
            $userCount * $postCount
        ));

        return Command::SUCCESS;
    }

    private function createTeams(SymfonyStyle $io, $em, int $count): array
    {
        $teams = [];
        $teamNames = ['Level Design', 'Character Art', 'Audio Engineering'];

        for ($i = 0; $i < $count; $i++) {
            $team = new Team();
            $team->setName($teamNames[$i] ?? 'Team ' . ($i + 1));
            $team->setDescription('Magic team for wizard platform');
            $team->setCreatedAt(new \DateTimeImmutable());
            $em->persist($team);
            $teams[] = $team;
            $io->text(sprintf('  Team: <info>%s</info>', $team->getName()));
        }

        return $teams;
    }

    private function createRoles(SymfonyStyle $io, $em, array $teams): array
    {
        $roles = [];
        $roleNames = [Role::WIZARD, Role::ARCHITECT, Role::GAME_MASTER];

        foreach ($roleNames as $roleName) {
            $role = new Role();
            $role->setName($roleName);
            $role->setDescription('Role: ' . $roleName);
            $em->persist($role);
            $roles[$roleName] = $role;
            $io->text(sprintf('  Role: <info>%s</info>', $roleName));
        }

        return $roles;
    }

    private function createUsers(SymfonyStyle $io, $em, array $teams, array $roles, int $count): array
    {
        $users = [];

        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setEmail($this->faker->unique()->safeEmail);
            $user->setPassword(password_hash($this->faker->password, PASSWORD_BCRYPT));
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setTeam($this->faker->randomElement($teams));
            $em->persist($user);
            $users[] = $user;
            $io->text(sprintf('  User: <info>%s</info>', $user->getEmail()));
        }

        return $users;
    }

    private function createUserRoles(SymfonyStyle $io, $em, array $users, array $teams, array $roles): void
    {
        foreach ($users as $user) {
            $team = $user->getTeam();
            if ($team === null) {
                continue;
            }

            $wizardRole = $roles[Role::WIZARD];
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($wizardRole);
            $userRole->setTeam($team);
            $userRole->setGrantedAt(new \DateTimeImmutable());
            $em->persist($userRole);

            if ($this->faker->boolean(30)) {
                $architectRole = $roles[Role::ARCHITECT];
                $userRole = new UserRole();
                $userRole->setUser($user);
                $userRole->setRole($architectRole);
                $userRole->setTeam($team);
                $userRole->setGrantedAt(new \DateTimeImmutable());
                $em->persist($userRole);
            }

            if ($this->faker->boolean(10)) {
                $gameMasterRole = $roles[Role::GAME_MASTER];
                $userRole = new UserRole();
                $userRole->setUser($user);
                $userRole->setRole($gameMasterRole);
                $userRole->setTeam($team);
                $userRole->setGrantedAt(new \DateTimeImmutable());
                $em->persist($userRole);
            }
        }
    }

    private function createWands(SymfonyStyle $io, $em, array $users, array $roles): void
    {
        foreach ($users as $user) {
            if ($this->faker->boolean(50)) {
                $wand = new Wand();
                $wand->setUser($user);
                $wand->setRole($roles[Role::WIZARD]);
                $wand->setName('Wand of ' . ucfirst($this->faker->randomElement(['Power', 'Protection', 'Creation'])));
                $wand->setPermissions(json_encode(['read', 'write']));
                $wand->setCreatedAt(new \DateTimeImmutable());
                $em->persist($wand);
                $io->text(sprintf('  Wand: <info>%s</info> for %s', $wand->getName(), $user->getEmail()));
            }
        }
    }

    private function createPatronuses(SymfonyStyle $io, $em, array $users, array $teams, array $roles): void
    {
        foreach ($users as $user) {
            if ($this->faker->boolean(40)) {
                $patronus = new Patronus();
                $patronus->setUser($user);
                $patronus->setRole($roles[Role::WIZARD]);
                $patronus->setTeam($user->getTeam() ?? $this->faker->randomElement($teams));
                $patronus->setToken(bin2hex(random_bytes(32)));
                $patronus->setIssuedAt(new \DateTimeImmutable());
                $patronus->setExpiresAt(new \DateTimeImmutable('+24 hours'));
                $em->persist($patronus);
                $io->text(sprintf('  Patronus: <info>token created</info> for %s', $user->getEmail()));
            }
        }
    }

    private function createInvisibilityCloaks(SymfonyStyle $io, $em, array $users, array $teams): void
    {
        foreach ($users as $user) {
            if ($this->faker->boolean(20)) {
                $cloak = new InvisibilityCloak();
                $cloak->setUser($user);
                $cloak->setTeam($user->getTeam() ?? $this->faker->randomElement($teams));
                $cloak->setGrantedAt(new \DateTimeImmutable());
                $cloak->activate();
                $em->persist($cloak);
                $io->text(sprintf('  Invisibility Cloak: <info>granted</info> to %s', $user->getEmail()));
            }
        }
    }

    private function createPosts(SymfonyStyle $io, $em, array $users, int $postCount): void
    {
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
    }
}
