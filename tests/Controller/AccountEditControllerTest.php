<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour l'édition du profil utilisateur
 *
 * Ces tests vérifient que :
 * - Les utilisateurs peuvent modifier leur prénom et nom
 * - L'email n'est pas modifiable
 * - La validation des champs fonctionne
 * - Les utilisateurs ne peuvent modifier que leur propre profil
 */
class AccountEditControllerTest extends WebTestCase
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

    private function createUser(string $email, string $firstname, string $lastname, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setRoles($roles);
        $user->setIsEnabled(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!@'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Test 1 : Un utilisateur connecté peut accéder à la page de modification de profil
     */
    public function testUserCanAccessEditProfile(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/modifier-profil');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier mon profil');
    }

    /**
     * Test 2 : Un utilisateur non-connecté ne peut pas accéder à la page
     */
    public function testGuestCannotAccessEditProfile(): void
    {
        $this->client->request('GET', '/compte/modifier-profil');

        // La route de connexion est /connexion (app_login)
        $this->assertResponseRedirects('http://localhost/connexion');
    }

    /**
     * Test 3 : Le formulaire affiche les valeurs actuelles
     */
    public function testFormDisplaysCurrentValues(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/modifier-profil');

        $this->assertResponseIsSuccessful();

        // Vérifier que les valeurs actuelles sont pré-remplies
        $this->assertEquals('John', $crawler->filter('input#firstname')->attr('value'));
        $this->assertEquals('Doe', $crawler->filter('input#lastname')->attr('value'));
    }

    /**
     * Test 4 : L'email est affiché mais non modifiable
     */
    public function testEmailIsDisplayedButNotEditable(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/modifier-profil');

        $this->assertResponseIsSuccessful();

        // Vérifier qu'il n'y a pas de champ input pour l'email
        $this->assertEquals(0, $crawler->filter('input[name="email"]')->count());

        // Mais l'email est affiché quelque part
        $this->assertStringContainsString('user@test.fr', $this->client->getResponse()->getContent());
    }

    /**
     * Test 5 : Un utilisateur peut modifier son prénom et nom
     */
    public function testUserCanUpdateProfile(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $this->client->request('POST', '/compte/modifier-profil', [
            'firstname' => 'Jane',
            'lastname' => 'Smith'
        ]);

        $this->assertResponseRedirects('/compte');

        // Vérifier que les modifications ont été enregistrées
        $this->entityManager->refresh($user);
        $this->assertEquals('Jane', $user->getFirstname());
        $this->assertEquals('Smith', $user->getLastname());

        // Vérifier que l'email n'a pas changé
        $this->assertEquals('user@test.fr', $user->getEmail());
    }

    /**
     * Test 6 : Un message de succès est affiché après la modification
     */
    public function testSuccessMessageAfterUpdate(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $this->client->request('POST', '/compte/modifier-profil', [
            'firstname' => 'Jane',
            'lastname' => 'Smith'
        ]);

        $this->assertResponseRedirects('/compte');

        $crawler = $this->client->followRedirect();

        $this->assertStringContainsString('Votre profil a été mis à jour avec succès', $this->client->getResponse()->getContent());
    }

    /**
     * Test 7 : La validation refuse les champs vides
     */
    public function testValidationRequiresFirstname(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        // Envoyer avec prénom vide
        $this->client->request('POST', '/compte/modifier-profil', [
            'firstname' => '',
            'lastname' => 'Doe'
        ]);

        $this->assertResponseRedirects('/compte/modifier-profil');

        $crawler = $this->client->followRedirect();

        $this->assertStringContainsString('Tous les champs sont requis', $this->client->getResponse()->getContent());

        // Vérifier que les données n'ont pas changé
        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertEquals('John', $updatedUser->getFirstname());
    }

    /**
     * Test 8 : La validation refuse le nom vide
     */
    public function testValidationRequiresLastname(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        // Envoyer avec nom vide
        $this->client->request('POST', '/compte/modifier-profil', [
            'firstname' => 'John',
            'lastname' => ''
        ]);

        $this->assertResponseRedirects('/compte/modifier-profil');

        $crawler = $this->client->followRedirect();

        $this->assertStringContainsString('Tous les champs sont requis', $this->client->getResponse()->getContent());

        // Vérifier que les données n'ont pas changé
        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertEquals('Doe', $updatedUser->getLastname());
    }

    /**
     * Test 9 : Un employé peut aussi modifier son profil
     */
    public function testEmployeeCanUpdateProfile(): void
    {
        $employee = $this->createUser('employee@test.fr', 'Employee', 'Test', ['ROLE_EMPLOYEE']);
        $this->client->loginUser($employee);

        $this->client->request('POST', '/compte/modifier-profil', [
            'firstname' => 'Updated',
            'lastname' => 'Employee'
        ]);

        $this->assertResponseRedirects('/compte');

        $this->entityManager->refresh($employee);
        $this->assertEquals('Updated', $employee->getFirstname());
        $this->assertEquals('Employee', $employee->getLastname());
    }

    /**
     * Test 10 : Un admin peut aussi modifier son profil
     */
    public function testAdminCanUpdateProfile(): void
    {
        $admin = $this->createUser('admin@test.fr', 'Admin', 'Test', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $this->client->request('POST', '/compte/modifier-profil', [
            'firstname' => 'Updated',
            'lastname' => 'Admin'
        ]);

        $this->assertResponseRedirects('/compte');

        $this->entityManager->refresh($admin);
        $this->assertEquals('Updated', $admin->getFirstname());
        $this->assertEquals('Admin', $admin->getLastname());
    }

    /**
     * Test 11 : Le bouton de modification est présent sur la page compte
     */
    public function testEditButtonPresentOnAccountPage(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte');

        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a un lien vers la page de modification
        $this->assertGreaterThan(0, $crawler->filter('a[href*="/compte/modifier-profil"]')->count());
    }

    /**
     * Test 12 : Vérifier que l'utilisateur peut annuler la modification
     */
    public function testUserCanCancelEdit(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/modifier-profil');

        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a un bouton d'annulation qui renvoie vers /compte
        $this->assertGreaterThan(0, $crawler->filter('a[href*="/compte"]')->count());
    }
}