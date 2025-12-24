<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour les accès limités des employés
 *
 * Ces tests vérifient que :
 * - Les employés peuvent accéder à la gestion des utilisateurs
 * - Les employés ne voient QUE les utilisateurs ROLE_USER (clients)
 * - Les employés ne voient PAS les autres employés ni les admins
 * - Les employés ne peuvent PAS créer d'employés
 * - Les employés ne peuvent PAS modifier les rôles
 * - Les employés peuvent modifier les infos des clients (nom, prénom, email)
 * - Les employés peuvent activer/désactiver les comptes clients
 */
class EmployeeUserAccessTest extends WebTestCase
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

    private function createUser(string $email, string $firstname, string $lastname, array $roles, bool $isEnabled = true): User
    {
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
     * Test 1 : Un employé peut accéder à /admin/users
     */
    public function testEmployeeCanAccessUserList(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $crawler = $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des utilisateurs');
    }

    /**
     * Test 2 : Un employé ne voit QUE les utilisateurs ROLE_USER (pas les employés ni admins)
     */
    public function testEmployeeSeesOnlyRegularUsers(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $otherEmployee = $this->createUser('other-employee@test.fr', 'Other', 'Employee', ['ROLE_EMPLOYEE']);
        $user1 = $this->createUser('user1@test.fr', 'User', 'One', ['ROLE_USER']);
        $user2 = $this->createUser('user2@test.fr', 'User', 'Two', ['ROLE_USER']);

        $this->client->loginUser($employee);

        $crawler = $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Doit voir les utilisateurs ROLE_USER
        $this->assertStringContainsString('user1@test.fr', $content);
        $this->assertStringContainsString('user2@test.fr', $content);

        // Ne doit PAS voir l'admin
        $this->assertStringNotContainsString('admin@test.fr', $content);

        // Ne doit PAS voir les autres employés
        $this->assertStringNotContainsString('other-employee@test.fr', $content);
    }

    /**
     * Test 3 : Un employé ne peut PAS accéder à la page de création d'employé
     */
    public function testEmployeeCannotCreateEmployee(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $this->client->request('GET', '/admin/users/create-employee');

        // Doit être refusé (403 Forbidden)
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Test 4 : Un employé ne voit PAS le bouton "Créer un employé"
     */
    public function testEmployeeDoesNotSeeCreateEmployeeButton(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $crawler = $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();

        // Le bouton "Créer un employé" ne devrait pas être présent
        $this->assertEquals(0, $crawler->filter('a:contains("Créer un employé")')->count());
    }

    /**
     * Test 5 : Un employé ne voit PAS le filtre de rôle
     */
    public function testEmployeeDoesNotSeeRoleFilter(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $crawler = $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();

        // Le select de filtre par rôle ne devrait pas être présent
        $this->assertEquals(0, $crawler->filter('select#role')->count());
    }

    /**
     * Test 6 : Un employé peut modifier les informations d'un client
     */
    public function testEmployeeCanEditClientInfo(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);

        $this->client->loginUser($employee);

        // Modifier l'utilisateur
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/edit', [
            'firstname' => 'Modified',
            'lastname' => 'Name',
            'email' => 'user@test.fr'
            // Pas de champ 'role' car l'employé ne peut pas modifier les rôles
        ]);

        $this->assertResponseRedirects('/admin/users');

        // Vérifier les modifications
        $this->entityManager->refresh($user);
        $this->assertEquals('Modified', $user->getFirstname());
        $this->assertEquals('Name', $user->getLastname());
    }

    /**
     * Test 7 : Un employé ne peut PAS accéder à l'édition d'un autre employé
     */
    public function testEmployeeCannotEditOtherEmployee(): void
    {
        $employee1 = $this->createUser('employee1@test.fr', 'Employee', 'One', ['ROLE_EMPLOYEE']);
        $employee2 = $this->createUser('employee2@test.fr', 'Employee', 'Two', ['ROLE_EMPLOYEE']);

        $this->client->loginUser($employee1);

        $this->client->request('GET', '/admin/users/' . $employee2->getId() . '/edit');

        $this->assertResponseRedirects('/admin/users');

        $this->client->followRedirect();
        $content = $this->client->getResponse()->getContent();
        // Le message peut contenir des caractères HTML encodés, cherchons la partie clé
        $this->assertStringContainsString('droits pour modifier ce compte', $content);
    }

    /**
     * Test 8 : Un employé ne peut PAS accéder à l'édition d'un admin
     */
    public function testEmployeeCannotEditAdmin(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);

        $this->client->loginUser($employee);

        // Tenter d'accéder à l'édition d'un admin
        $this->client->request('GET', '/admin/users/' . $admin->getId() . '/edit');

        // Comme l'admin n'est pas visible dans la liste, la tentative doit échouer
        $this->assertResponseRedirects('/admin/users');
    }

    /**
     * Test 9 : Un employé ne peut PAS modifier le rôle d'un utilisateur
     */
    public function testEmployeeCannotChangeUserRole(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);

        $this->client->loginUser($employee);

        // Accéder à la page d'édition
        $crawler = $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        // Vérifier que le select de rôle n'est PAS présent
        $this->assertEquals(0, $crawler->filter('select#role')->count());
    }

    /**
     * Test 10 : Un employé peut activer/désactiver un compte client
     */
    public function testEmployeeCanToggleClientStatus(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER'], true);

        $this->client->loginUser($employee);

        // Désactiver le compte
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/toggle-status');

        $this->assertResponseRedirects('/admin/users');

        // Vérifier que le compte est désactivé
        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertFalse($updatedUser->isEnabled());
    }

    /**
     * Test 11 : Un employé peut rechercher parmi les clients
     */
    public function testEmployeeCanSearchClients(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $user1 = $this->createUser('john@test.fr', 'John', 'Doe', ['ROLE_USER']);
        $user2 = $this->createUser('jane@test.fr', 'Jane', 'Smith', ['ROLE_USER']);
        $otherEmployee = $this->createUser('other@test.fr', 'Other', 'Employee', ['ROLE_EMPLOYEE']);

        $this->client->loginUser($employee);

        // Rechercher "John"
        $crawler = $this->client->request('GET', '/admin/users?search=John');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Doit trouver John
        $this->assertStringContainsString('john@test.fr', $content);

        // Ne doit pas voir l'autre employé même si la recherche pourrait matcher
        $this->assertStringNotContainsString('other@test.fr', $content);
    }

    /**
     * Test 12 : Un employé peut filtrer par statut
     */
    public function testEmployeeCanFilterByStatus(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $activeUser = $this->createUser('active@test.fr', 'Active', 'User', ['ROLE_USER'], true);
        $inactiveUser = $this->createUser('inactive@test.fr', 'Inactive', 'User', ['ROLE_USER'], false);

        $this->client->loginUser($employee);

        // Filtrer par statut désactivé
        $crawler = $this->client->request('GET', '/admin/users?status=0');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('inactive@test.fr', $this->client->getResponse()->getContent());
    }

    /**
     * Test 13 : Vérifier que le paramètre 'role' dans l'URL est ignoré pour les employés
     */
    public function testEmployeeRoleFilterIsIgnored(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $user = $this->createUser('user@test.fr', 'User', 'Test', ['ROLE_USER']);

        $this->client->loginUser($employee);

        // Tenter de filtrer par ROLE_ADMIN (ne devrait rien changer)
        $crawler = $this->client->request('GET', '/admin/users?role=ROLE_ADMIN');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Ne doit toujours PAS voir l'admin
        $this->assertStringNotContainsString('admin@test.fr', $content);

        // Doit toujours voir les utilisateurs normaux
        $this->assertStringContainsString('user@test.fr', $content);
    }
}