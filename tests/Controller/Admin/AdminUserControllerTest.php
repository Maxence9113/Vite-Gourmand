<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour la gestion des utilisateurs par les administrateurs
 *
 * Ces tests vérifient que :
 * - Les admins peuvent voir tous les utilisateurs
 * - Les admins peuvent créer des employés
 * - Les admins peuvent modifier les rôles
 * - Les admins peuvent activer/désactiver les comptes
 */
class AdminUserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private static $testCounter = 0;

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
        self::$testCounter++;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
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

    private function createUser(string $email, string $firstname, string $lastname, array $roles, bool $isEnabled = true): User
    {
        // Vérifier si l'utilisateur existe déjà
        $userRepo = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepo->findOneBy(['email' => $email]);

        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setRoles($roles);
        $user->setIsEnabled($isEnabled);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!@'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Test 1 : Un admin peut accéder à la liste des utilisateurs
     */
    public function testAdminCanAccessUserList(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des utilisateurs');
    }

    /**
     * Test 2 : Un admin voit tous les utilisateurs (USER, EMPLOYEE, ADMIN)
     */
    public function testAdminSeesAllUsers(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);

        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();

        // Vérifier que tous les utilisateurs sont affichés
        $this->assertStringContainsString('admin@test.fr', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('employee@test.fr', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('user@test.fr', $this->client->getResponse()->getContent());
    }

    /**
     * Test 3 : Un admin peut créer un employé
     */
    public function testAdminCanCreateEmployee(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/users/create-employee');
        $this->assertResponseIsSuccessful();

        // Soumettre le formulaire de création
        $this->client->request('POST', '/admin/users/create-employee', [
            'email' => 'newemployee@test.fr',
            'password' => 'Employee123!@',
            'firstname' => 'New',
            'lastname' => 'Employee'
        ]);

        $this->assertResponseRedirects('/admin/users');

        // Vérifier que l'employé a été créé
        $userRepo = $this->entityManager->getRepository(User::class);
        $newEmployee = $userRepo->findOneBy(['email' => 'newemployee@test.fr']);

        $this->assertNotNull($newEmployee);
        $this->assertContains('ROLE_EMPLOYEE', $newEmployee->getRoles());
        $this->assertEquals('New', $newEmployee->getFirstname());
        $this->assertEquals('Employee', $newEmployee->getLastname());
    }

    /**
     * Test 4 : Un admin peut modifier un utilisateur
     */
    public function testAdminCanEditUser(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);

        $this->client->loginUser($admin);

        // Modifier l'utilisateur
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/edit', [
            'firstname' => 'Modified',
            'lastname' => 'Name',
            'email' => 'user@test.fr',
            'role' => 'ROLE_USER'
        ]);

        $this->assertResponseRedirects('/admin/users');

        // Vérifier les modifications
        $this->entityManager->refresh($user);
        $this->assertEquals('Modified', $user->getFirstname());
        $this->assertEquals('Name', $user->getLastname());
    }

    /**
     * Test 5 : Un admin peut changer le rôle d'un utilisateur
     */
    public function testAdminCanChangeUserRole(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);

        $this->client->loginUser($admin);

        // Promouvoir l'utilisateur en employé
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/edit', [
            'firstname' => 'User',
            'lastname' => 'Test',
            'email' => 'user@test.fr',
            'role' => 'ROLE_EMPLOYEE'
        ]);

        $this->assertResponseRedirects('/admin/users');

        // Vérifier le changement de rôle
        $this->entityManager->refresh($user);
        $this->assertContains('ROLE_EMPLOYEE', $user->getRoles());
    }

    /**
     * Test 6 : Un admin peut activer/désactiver un compte utilisateur
     */
    public function testAdminCanToggleUserStatus(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER'], true);

        $this->client->loginUser($admin);

        // Désactiver le compte
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/toggle-status');

        $this->assertResponseRedirects('/admin/users');

        // Vérifier que le compte est désactivé
        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertFalse($updatedUser->isEnabled());

        // Réactiver le compte
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/toggle-status');

        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertTrue($updatedUser->isEnabled());
    }

    /**
     * Test 7 : Un admin ne peut pas désactiver un compte admin
     */
    public function testAdminCannotToggleAdminStatus(): void
    {
        $admin1 = $this->createUser('admin1@test.fr', 'Admin', 'One', ['ROLE_ADMIN']);
        $admin2 = $this->createUser('admin2@test.fr', 'Admin', 'Two', ['ROLE_ADMIN']);

        $this->client->loginUser($admin1);

        $this->client->request('POST', '/admin/users/' . $admin2->getId() . '/toggle-status');

        $this->assertResponseRedirects('/admin/users');

        // Vérifier le message d'erreur
        $this->client->followRedirect();
        $this->assertStringContainsString('Impossible de désactiver un compte administrateur', $this->client->getResponse()->getContent());
    }

    /**
     * Test 8 : Un admin ne peut pas modifier un compte admin
     */
    public function testAdminCannotEditAdminAccount(): void
    {
        $admin1 = $this->createUser('admin1@test.fr', 'Admin', 'One', ['ROLE_ADMIN']);
        $admin2 = $this->createUser('admin2@test.fr', 'Admin', 'Two', ['ROLE_ADMIN']);

        $this->client->loginUser($admin1);

        $this->client->request('GET', '/admin/users/' . $admin2->getId() . '/edit');

        $this->assertResponseRedirects('/admin/users');
    }

    /**
     * Test 9 : Un admin peut filtrer les utilisateurs par rôle
     */
    public function testAdminCanFilterUsersByRole(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);

        $this->client->loginUser($admin);

        // Filtrer par ROLE_EMPLOYEE
        $crawler = $this->client->request('GET', '/admin/users?role=ROLE_EMPLOYEE');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('employee@test.fr', $this->client->getResponse()->getContent());
    }

    /**
     * Test 10 : Un admin peut rechercher un utilisateur
     */
    public function testAdminCanSearchUsers(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user1 = $this->createUser('john@test.fr', 'John', 'Doe', ['ROLE_USER']);
        $user2 = $this->createUser('jane@test.fr', 'Jane', 'Smith', ['ROLE_USER']);

        $this->client->loginUser($admin);

        // Rechercher par prénom
        $crawler = $this->client->request('GET', '/admin/users?search=John');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('john@test.fr', $this->client->getResponse()->getContent());
    }

    /**
     * Test 11 : Un utilisateur non-admin ne peut pas accéder à la gestion des utilisateurs
     */
    public function testRegularUserCannotAccessUserManagement(): void
    {
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/users');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Test 12 : Un visiteur non-connecté ne peut pas accéder à la gestion des utilisateurs
     */
    public function testGuestCannotAccessUserManagement(): void
    {
        $this->client->request('GET', '/admin/users');

        // La route de connexion est /connexion (app_login)
        $this->assertResponseRedirects('http://localhost/connexion');
    }

    /**
     * Test 13 : Validation des champs obligatoires lors de la création d'un employé
     */
    public function testCreateEmployeeRequiresAllFields(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        // Essayer de créer sans tous les champs
        $this->client->request('POST', '/admin/users/create-employee', [
            'email' => 'incomplete@test.fr',
            // Manque password, firstname, lastname
        ]);

        $this->assertResponseRedirects('/admin/users/create-employee');

        $this->client->followRedirect();
        $this->assertStringContainsString('Tous les champs sont requis', $this->client->getResponse()->getContent());
    }

    /**
     * Test 14 : Un admin peut filtrer par statut (actif/désactivé)
     */
    public function testAdminCanFilterByStatus(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $activeUser = $this->createUser('active@test.fr', 'Active', 'User', ['ROLE_USER'], true);
        $inactiveUser = $this->createUser('inactive@test.fr', 'Inactive', 'User', ['ROLE_USER'], false);

        $this->client->loginUser($admin);

        // Filtrer par statut actif
        $crawler = $this->client->request('GET', '/admin/users?status=1');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('active@test.fr', $this->client->getResponse()->getContent());
    }
}