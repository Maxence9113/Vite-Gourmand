<?php

namespace App\Tests\Security;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests pour la hiérarchie des rôles
 *
 * Ces tests vérifient que :
 * - ROLE_EMPLOYEE hérite de ROLE_USER
 * - ROLE_ADMIN hérite de ROLE_EMPLOYEE et ROLE_USER
 * - Les accès sont correctement contrôlés selon la hiérarchie
 */
class RoleHierarchyTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = $this->client->getContainer()
            ->get(UserPasswordHasherInterface::class);

        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();

        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function createUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles($roles);
        $user->setIsEnabled(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!@'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Test 1 : ROLE_USER peut accéder à /compte
     */
    public function testRoleUserCanAccessAccount(): void
    {
        $user = $this->createUser('user@test.fr', ['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/compte');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 2 : ROLE_USER ne peut PAS accéder à /admin
     */
    public function testRoleUserCannotAccessAdmin(): void
    {
        $user = $this->createUser('user@test.fr', ['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Test 3 : ROLE_EMPLOYEE peut accéder à /compte (hérité de ROLE_USER)
     */
    public function testRoleEmployeeCanAccessAccount(): void
    {
        $employee = $this->createUser('employee@test.fr', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $this->client->request('GET', '/compte');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 4 : ROLE_EMPLOYEE peut accéder à /admin
     */
    public function testRoleEmployeeCanAccessAdmin(): void
    {
        $employee = $this->createUser('employee@test.fr', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $this->client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 5 : ROLE_EMPLOYEE peut accéder à /admin/users
     */
    public function testRoleEmployeeCanAccessAdminUsers(): void
    {
        $employee = $this->createUser('employee@test.fr', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 6 : ROLE_EMPLOYEE ne peut PAS accéder à /admin/users/create-employee
     */
    public function testRoleEmployeeCannotCreateEmployee(): void
    {
        $employee = $this->createUser('employee@test.fr', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $this->client->request('GET', '/admin/users/create-employee');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Test 7 : ROLE_ADMIN peut accéder à /compte (hérité)
     */
    public function testRoleAdminCanAccessAccount(): void
    {
        $admin = $this->createUser('admin@test.fr', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/compte');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 8 : ROLE_ADMIN peut accéder à /admin (hérité de ROLE_EMPLOYEE)
     */
    public function testRoleAdminCanAccessAdmin(): void
    {
        $admin = $this->createUser('admin@test.fr', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 9 : ROLE_ADMIN peut accéder à /admin/users
     */
    public function testRoleAdminCanAccessAdminUsers(): void
    {
        $admin = $this->createUser('admin@test.fr', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 10 : ROLE_ADMIN peut accéder à /admin/users/create-employee
     */
    public function testRoleAdminCanCreateEmployee(): void
    {
        $admin = $this->createUser('admin@test.fr', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/create-employee');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 11 : Vérifier la hiérarchie avec is_granted dans le contrôleur
     */
    public function testRoleHierarchyWithIsGranted(): void
    {
        // Créer un utilisateur de chaque type
        $admin = $this->createUser('admin@test.fr', ['ROLE_ADMIN']);

        $this->client->loginUser($admin);

        // Un admin doit avoir is_granted pour ROLE_USER et ROLE_EMPLOYEE
        $crawler = $this->client->request('GET', '/compte');

        $this->assertResponseIsSuccessful();

        // Si on peut accéder à /compte, c'est que is_granted('ROLE_USER') fonctionne
        // même si on a ROLE_ADMIN
    }

    /**
     * Test 12 : Les pages protégées par ROLE_USER sont accessibles à tous les rôles
     */
    public function testAccountAccessForAllRoles(): void
    {
        $testCases = [
            ['ROLE_USER', 'user@test.fr'],
            ['ROLE_EMPLOYEE', 'employee@test.fr'],
            ['ROLE_ADMIN', 'admin@test.fr']
        ];

        foreach ($testCases as [$role, $email]) {
            $user = $this->createUser($email, [$role]);
            $this->client->loginUser($user);

            $this->client->request('GET', '/compte');

            $this->assertResponseIsSuccessful(
                sprintf('User with %s should be able to access /compte', $role)
            );

            // Nettoyer pour le prochain test
            $userToRemove = $this->entityManager->getRepository(User::class)->find($user->getId());
            if ($userToRemove) {
                $this->entityManager->remove($userToRemove);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Test 13 : Les pages admin sont accessibles seulement à EMPLOYEE et ADMIN
     */
    public function testAdminAccessHierarchy(): void
    {
        $testCases = [
            ['ROLE_USER', 'user@test.fr', 403],
            ['ROLE_EMPLOYEE', 'employee@test.fr', 200],
            ['ROLE_ADMIN', 'admin@test.fr', 200]
        ];

        foreach ($testCases as [$role, $email, $expectedStatus]) {
            $user = $this->createUser($email, [$role]);
            $this->client->loginUser($user);

            $this->client->request('GET', '/admin');

            $this->assertResponseStatusCodeSame(
                $expectedStatus,
                sprintf('User with %s should get status %d when accessing /admin', $role, $expectedStatus)
            );

            // Nettoyer pour le prochain test
            $userToRemove = $this->entityManager->getRepository(User::class)->find($user->getId());
            if ($userToRemove) {
                $this->entityManager->remove($userToRemove);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Test 14 : Seul ROLE_ADMIN peut créer des employés
     */
    public function testOnlyAdminCanCreateEmployee(): void
    {
        $testCases = [
            ['ROLE_USER', 'user@test.fr', 403],
            ['ROLE_EMPLOYEE', 'employee@test.fr', 403],
            ['ROLE_ADMIN', 'admin@test.fr', 200]
        ];

        foreach ($testCases as [$role, $email, $expectedStatus]) {
            $user = $this->createUser($email, [$role]);
            $this->client->loginUser($user);

            $this->client->request('GET', '/admin/users/create-employee');

            $this->assertResponseStatusCodeSame(
                $expectedStatus,
                sprintf('User with %s should get status %d when accessing /admin/users/create-employee', $role, $expectedStatus)
            );

            // Nettoyer pour le prochain test
            $userToRemove = $this->entityManager->getRepository(User::class)->find($user->getId());
            if ($userToRemove) {
                $this->entityManager->remove($userToRemove);
                $this->entityManager->flush();
            }
        }
    }
}