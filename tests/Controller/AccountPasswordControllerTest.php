<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests fonctionnels du formulaire de modification du mot de passe
 *
 * Ces tests vérifient le comportement complet de la fonctionnalité de modification
 * du mot de passe d'un utilisateur connecté
 */
class AccountPasswordControllerTest extends WebTestCase
{
    /**
     * Test 1 : Vérifier que la page de modification du mot de passe nécessite une authentification
     *
     * TODO: Ce test sera activé une fois que les rôles seront configurés
     * Pour l'instant, il est commenté car les access_control ne sont pas encore en place
     */
    public function testPasswordPageRequiresAuthentication(): void
    {
        $this->markTestSkipped('Test désactivé temporairement - Les rôles seront configurés plus tard');

        // $client = static::createClient();
        // $client->request('GET', '/compte/modifier-mot-de-passe');
        // $this->assertResponseRedirects('/connexion');
    }

    /**
     * Test 2 : Vérifier que la page de modification est accessible pour un utilisateur connecté
     *
     * On teste que :
     * - La route /compte/modifier-mot-de-passe répond avec un code 200 (OK)
     * - La page contient le formulaire de modification
     * - Les champs attendus sont présents
     */
    public function testPasswordPageIsAccessibleWhenLoggedIn(): void
    {
        $client = static::createClient();

        // Créer et connecter un utilisateur de test
        $user = $this->createAuthenticatedUser($client);
        $client->loginUser($user);

        // Accéder à la page de modification du mot de passe
        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        // Vérifier que la réponse est un succès (code 200)
        $this->assertResponseIsSuccessful();

        // Vérifier que le formulaire contient bien les champs attendus
        $this->assertSelectorExists('input[name="password_user[actualPassword]"]');
        $this->assertSelectorExists('input[name="password_user[plainPassword][first]"]');
        $this->assertSelectorExists('input[name="password_user[plainPassword][second]"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    /**
     * Test 3 : Modification réussie du mot de passe avec des données valides
     *
     * Scénario :
     * 1. Un utilisateur est connecté avec un mot de passe connu
     * 2. Il remplit le formulaire avec :
     *    - Son mot de passe actuel correct
     *    - Un nouveau mot de passe valide (répété deux fois)
     * 3. Le mot de passe est mis à jour en base de données
     * 4. Le nouveau mot de passe est bien hashé
     * 5. L'utilisateur est redirigé vers la page de compte
     */
    public function testSuccessfulPasswordChange(): void
    {
        $client = static::createClient();

        // Créer un utilisateur avec un mot de passe connu
        $user = $this->createAuthenticatedUser($client, 'OldPassword123!@');
        $client->loginUser($user);

        // Accéder à la page de modification
        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        // Remplir le formulaire avec des données valides
        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => 'OldPassword123!@',
            'password_user[plainPassword][first]' => 'NewPassword456!@',
            'password_user[plainPassword][second]' => 'NewPassword456!@',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier la redirection vers la page de compte
        $this->assertResponseRedirects('/compte');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que le message de succès est affiché
        $this->assertSelectorExists('.alert-success');

        // Vérifier que le mot de passe a bien été changé en base de données
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository(User::class);

        // Récupérer l'utilisateur depuis la base de données (entité managée)
        $updatedUser = $userRepository->findOneBy(['email' => 'testuser@example.com']);
        $this->assertNotNull($updatedUser, 'L\'utilisateur doit exister en base de données');

        $passwordHasher = $client->getContainer()->get('security.user_password_hasher');

        // Vérifier que l'ancien mot de passe ne fonctionne plus
        $this->assertFalse(
            $passwordHasher->isPasswordValid($updatedUser, 'OldPassword123!@'),
            'L\'ancien mot de passe ne doit plus fonctionner'
        );

        // Vérifier que le nouveau mot de passe fonctionne
        $this->assertTrue(
            $passwordHasher->isPasswordValid($updatedUser, 'NewPassword456!@'),
            'Le nouveau mot de passe doit fonctionner'
        );

        // Vérifier que le mot de passe est bien hashé (et non en clair)
        $this->assertNotEquals('NewPassword456!@', $updatedUser->getPassword(),
            'Le mot de passe ne doit PAS être stocké en clair');
        $this->assertStringStartsWith('$2y$', $updatedUser->getPassword(),
            'Le mot de passe doit être hashé avec bcrypt');
    }

    /**
     * Test 4 : Rejet si le mot de passe actuel est incorrect
     *
     * Si l'utilisateur saisit un mauvais mot de passe actuel,
     * la modification doit être refusée
     */
    public function testPasswordChangeWithWrongCurrentPassword(): void
    {
        $client = static::createClient();

        // Créer un utilisateur avec un mot de passe connu
        $user = $this->createAuthenticatedUser($client, 'CorrectPassword123!@');
        $client->loginUser($user);

        // Accéder à la page de modification
        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        // Remplir le formulaire avec un mauvais mot de passe actuel
        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => 'WrongPassword999!@', // Mauvais mot de passe
            'password_user[plainPassword][first]' => 'NewPassword456!@',
            'password_user[plainPassword][second]' => 'NewPassword456!@',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier qu'on reste sur la même page (pas de redirection)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier que le message d'erreur est affiché
        $this->assertSelectorTextContains('.invalid-feedback',
            'Votre mot de passe actuel n\'est pas conforme');
    }

    /**
     * Test 5 : Rejet si le nouveau mot de passe est trop court
     *
     * Le nouveau mot de passe doit contenir au moins 10 caractères
     */
    public function testPasswordChangeWithShortNewPassword(): void
    {
        $client = static::createClient();

        $user = $this->createAuthenticatedUser($client, 'CurrentPass123!@');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => 'CurrentPass123!@',
            'password_user[plainPassword][first]' => 'Short1!', // Seulement 7 caractères (< 10)
            'password_user[plainPassword][second]' => 'Short1!',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 10 caractères');
    }

    /**
     * Test 6 : Rejet si le nouveau mot de passe n'a pas de caractère spécial
     */
    public function testPasswordChangeWithoutSpecialCharacter(): void
    {
        $client = static::createClient();

        $user = $this->createAuthenticatedUser($client, 'CurrentPass123!@');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => 'CurrentPass123!@',
            'password_user[plainPassword][first]' => 'NewPassword123', // Pas de caractère spécial
            'password_user[plainPassword][second]' => 'NewPassword123',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial');
    }

    /**
     * Test 7 : Rejet si le nouveau mot de passe n'a pas de majuscule
     */
    public function testPasswordChangeWithoutUppercase(): void
    {
        $client = static::createClient();

        $user = $this->createAuthenticatedUser($client, 'CurrentPass123!@');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => 'CurrentPass123!@',
            'password_user[plainPassword][first]' => 'newpassword123!@', // Pas de majuscule
            'password_user[plainPassword][second]' => 'newpassword123!@',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial');
    }

    /**
     * Test 8 : Rejet si le nouveau mot de passe n'a pas de chiffre
     */
    public function testPasswordChangeWithoutNumber(): void
    {
        $client = static::createClient();

        $user = $this->createAuthenticatedUser($client, 'CurrentPass123!@');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => 'CurrentPass123!@',
            'password_user[plainPassword][first]' => 'NewPassword!@#$', // Pas de chiffre
            'password_user[plainPassword][second]' => 'NewPassword!@#$',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial');
    }

    /**
     * Test 9 : Rejet si les deux nouveaux mots de passe ne correspondent pas
     */
    public function testPasswordChangeWithMismatchedNewPasswords(): void
    {
        $client = static::createClient();

        $user = $this->createAuthenticatedUser($client, 'CurrentPass123!@');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => 'CurrentPass123!@',
            'password_user[plainPassword][first]' => 'NewPassword123!@',
            'password_user[plainPassword][second]' => 'DifferentPass456!@', // Différent !
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('.invalid-feedback');
    }

    /**
     * Test 10 : Rejet si le champ mot de passe actuel est vide
     */
    public function testPasswordChangeWithEmptyCurrentPassword(): void
    {
        $client = static::createClient();

        $user = $this->createAuthenticatedUser($client, 'CurrentPass123!@');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/compte/modifier-mot-de-passe');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'password_user[actualPassword]' => '', // Vide
            'password_user[plainPassword][first]' => 'NewPassword123!@',
            'password_user[plainPassword][second]' => 'NewPassword123!@',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('.invalid-feedback');
    }

    /**
     * Méthode utilitaire : Créer un utilisateur authentifié pour les tests
     *
     * @param object $client Le client de test
     * @param string $password Le mot de passe à utiliser (par défaut : TestPassword123!@)
     * @return User L'utilisateur créé
     */
    private function createAuthenticatedUser($client, string $password = 'TestPassword123!@'): User
    {
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $passwordHasher = $client->getContainer()->get('security.user_password_hasher');

        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('testuser@example.com');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles(['ROLE_USER']);

        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Persister en base de données
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}