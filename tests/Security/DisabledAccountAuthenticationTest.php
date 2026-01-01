<?php

namespace App\Tests\Security;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests pour l'authentification des comptes désactivés
 *
 * Ces tests vérifient que :
 * - Les comptes désactivés ne peuvent pas se connecter
 * - Les comptes activés peuvent se connecter normalement
 * - Le message d'erreur approprié est affiché pour les comptes désactivés
 */
class DisabledAccountAuthenticationTest extends WebTestCase
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

    private function createUser(string $email, string $password, array $roles = ['ROLE_USER'], bool $isEnabled = true): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles($roles);
        $user->setIsEnabled($isEnabled);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Test 1 : Un utilisateur avec un compte activé peut se connecter
     */
    public function testEnabledUserCanLogin(): void
    {
        $this->createUser('enabled@test.fr', 'Test1234!@', ['ROLE_USER'], true);

        $crawler = $this->client->request('GET', '/connexion');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'enabled@test.fr',
            '_password' => 'Test1234!@',
        ]);

        $this->client->submit($form);

        // Devrait rediriger après connexion réussie
        $this->assertResponseRedirects();

        $this->client->followRedirect();

        // Vérifier qu'on est bien connecté
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test 2 : Un utilisateur avec un compte désactivé ne peut PAS se connecter
     */
    public function testDisabledUserCannotLogin(): void
    {
        $this->createUser('disabled@test.fr', 'Test1234!@', ['ROLE_USER'], false);

        $crawler = $this->client->request('GET', '/connexion');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'disabled@test.fr',
            '_password' => 'Test1234!@',
        ]);

        $this->client->submit($form);

        // Devrait rester sur la page de connexion
        $this->assertResponseRedirects('/connexion');

        $crawler = $this->client->followRedirect();

        // Vérifier que le message d'erreur est affiché
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('compte a été désactivé', $content);
    }

    /**
     * Test 3 : Un employé avec un compte désactivé ne peut pas se connecter
     */
    public function testDisabledEmployeeCannotLogin(): void
    {
        $this->createUser('employee@test.fr', 'Test1234!@', ['ROLE_EMPLOYEE'], false);

        $crawler = $this->client->request('GET', '/connexion');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'employee@test.fr',
            '_password' => 'Test1234!@',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/connexion');

        $crawler = $this->client->followRedirect();

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('compte a été désactivé', $content);
    }

    /**
     * Test 4 : Un admin avec un compte désactivé ne peut pas se connecter
     */
    public function testDisabledAdminCannotLogin(): void
    {
        $this->createUser('admin@test.fr', 'Test1234!@', ['ROLE_ADMIN'], false);

        $crawler = $this->client->request('GET', '/connexion');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@test.fr',
            '_password' => 'Test1234!@',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/connexion');

        $crawler = $this->client->followRedirect();

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('compte a été désactivé', $content);
    }

    /**
     * Test 5 : Mauvais mot de passe avec compte activé affiche un message différent
     */
    public function testWrongPasswordShowsDifferentError(): void
    {
        $this->createUser('user@test.fr', 'CorrectPassword', ['ROLE_USER'], true);

        $crawler = $this->client->request('GET', '/connexion');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'user@test.fr',
            '_password' => 'WrongPassword',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/connexion');

        $crawler = $this->client->followRedirect();

        $content = $this->client->getResponse()->getContent();

        // Ne devrait PAS contenir le message de compte désactivé
        $this->assertStringNotContainsString('compte a été désactivé', $content);

        // Devrait contenir un message d'identifiants invalides (en anglais)
        $this->assertStringContainsString('Invalid credentials', $content);
    }

    /**
     * Test 6 : Un compte peut être réactivé et permet alors la connexion
     */
    public function testReenabledAccountCanLogin(): void
    {
        $user = $this->createUser('reactivated@test.fr', 'Test1234!@', ['ROLE_USER'], false);

        // Tenter de se connecter avec compte désactivé
        $crawler = $this->client->request('GET', '/connexion');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'reactivated@test.fr',
            '_password' => 'Test1234!@',
        ]);
        $this->client->submit($form);
        $this->assertResponseRedirects('/connexion');

        // Réactiver le compte
        $user->setIsEnabled(true);
        $this->entityManager->flush();

        // Tenter à nouveau de se connecter
        $crawler = $this->client->request('GET', '/connexion');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'reactivated@test.fr',
            '_password' => 'Test1234!@',
        ]);
        $this->client->submit($form);

        // Cette fois devrait fonctionner
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}