<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;

/**
 * Tests fonctionnels du contrôleur d'inscription
 *
 * Ces tests vérifient le comportement complet de la fonctionnalité d'inscription
 * en simulant les interactions d'un utilisateur avec l'application
 */
class RegisterControllerTest extends WebTestCase
{
    use \Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
    /**
     * Test 1 : Vérifier que la page d'inscription est accessible
     *
     * On teste que :
     * - La route /inscription répond avec un code 200 (OK)
     * - La page contient bien le titre "Créer un compte"
     * - Le formulaire d'inscription est présent
     */
    public function testRegisterPageIsAccessible(): void
    {
        // Créer un client HTTP pour simuler un navigateur
        $client = static::createClient();

        // Effectuer une requête GET sur /inscription
        $crawler = $client->request('GET', '/inscription');

        // Vérifier que la réponse est un succès (code 200)
        $this->assertResponseIsSuccessful();

        // Vérifier que le titre "Créer un compte" est présent sur la page
        $this->assertSelectorTextContains('h1', 'Créer un compte');

        // Vérifier que le formulaire contient bien les champs attendus
        $this->assertSelectorExists('input[name="register_user[firstname]"]');
        $this->assertSelectorExists('input[name="register_user[lastname]"]');
        $this->assertSelectorExists('input[name="register_user[email]"]');
        $this->assertSelectorExists('input[name="register_user[plainPassword][first]"]');
        $this->assertSelectorExists('input[name="register_user[plainPassword][second]"]');
    }

    /**
     * Test 2 : Inscription réussie avec des données valides
     *
     * Scénario :
     * 1. Un visiteur remplit le formulaire avec des données valides
     * 2. Il soumet le formulaire
     * 3. Le compte est créé en base de données
     * 4. Le mot de passe est bien hashé (sécurisé)
     * 5. L'utilisateur a le rôle ROLE_USER par défaut
     * 6. Il est redirigé vers la page de connexion
     */
    public function testSuccessfulRegistration(): void
    {
        $client = static::createClient();

        // Activer le profiler pour capturer les emails
        $client->enableProfiler();

        // 1. Accéder à la page d'inscription
        $crawler = $client->request('GET', '/inscription');

        // 2. Remplir le formulaire avec des données valides
        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'jean.dupont@test.fr',
            'register_user[plainPassword][first]' => 'Password123!@', // Mot de passe valide
            'register_user[plainPassword][second]' => 'Password123!@', // Confirmation identique
        ]);

        // 3. Soumettre le formulaire
        $client->submit($form);

        // 4. Vérifier la redirection vers la page de connexion
        $this->assertResponseRedirects('/connexion');

        // 5. Vérifier qu'un email de bienvenue a été envoyé (AVANT de suivre la redirection)
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();
        $this->assertInstanceOf(Email::class, $email);

        // Vérifier le destinataire
        $this->assertEmailAddressContains($email, 'To', 'jean.dupont@test.fr');

        // Vérifier l'expéditeur
        $this->assertEmailAddressContains($email, 'From', 'contact@vitegourmand.fr');

        // Vérifier le sujet
        $this->assertEmailHeaderSame($email, 'Subject', 'Bienvenue chez Vite & Gourmand !');

        // Vérifier que le corps de l'email contient le prénom et nom
        $this->assertEmailHtmlBodyContains($email, 'Jean');
        $this->assertEmailHtmlBodyContains($email, 'Dupont');

        // Suivre la redirection
        $client->followRedirect();

        // 6. Vérifier que l'utilisateur a bien été créé en base de données
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'jean.dupont@test.fr']);

        // Vérifier que l'utilisateur existe
        $this->assertNotNull($user, 'L\'utilisateur devrait être créé en base de données');

        // Vérifier les informations de l'utilisateur
        $this->assertEquals('Jean', $user->getFirstname());
        $this->assertEquals('Dupont', $user->getLastname());
        $this->assertEquals('jean.dupont@test.fr', $user->getEmail());

        // Vérifier que le mot de passe est bien hashé (et non en clair)
        $this->assertNotEquals('Password123!@', $user->getPassword(),
            'Le mot de passe ne doit PAS être stocké en clair');
        $this->assertStringStartsWith('$2y$', $user->getPassword(),
            'Le mot de passe doit être hashé avec bcrypt');

        // Vérifier que l'utilisateur a le rôle ROLE_USER par défaut
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    /**
     * Test 3 : Rejet d'un mot de passe trop court
     *
     * Le mot de passe doit contenir au moins 10 caractères
     */
    public function testRegistrationWithShortPassword(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'jean.court@test.fr',
            'register_user[plainPassword][first]' => 'Pass1!', // Seulement 6 caractères (< 10)
            'register_user[plainPassword][second]' => 'Pass1!',
        ]);

        $client->submit($form);

        // Vérifier qu'on reste sur la même page (pas de redirection)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier que le message d'erreur est affiché
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 10 caractères');
    }

    /**
     * Test 4 : Rejet d'un mot de passe sans caractère spécial
     */
    public function testRegistrationWithoutSpecialCharacter(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'jean.nospecial@test.fr',
            'register_user[plainPassword][first]' => 'Password123', // Pas de caractère spécial
            'register_user[plainPassword][second]' => 'Password123',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial');
    }

    /**
     * Test 5 : Rejet d'un mot de passe sans majuscule
     */
    public function testRegistrationWithoutUppercase(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'jean.noupper@test.fr',
            'register_user[plainPassword][first]' => 'password123!@', // Pas de majuscule
            'register_user[plainPassword][second]' => 'password123!@',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial');
    }

    /**
     * Test 6 : Rejet d'un mot de passe sans chiffre
     */
    public function testRegistrationWithoutNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'jean.nonumber@test.fr',
            'register_user[plainPassword][first]' => 'Password!@#$', // Pas de chiffre
            'register_user[plainPassword][second]' => 'Password!@#$',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.invalid-feedback',
            'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial');
    }

    /**
     * Test 7 : Rejet si les deux mots de passe ne correspondent pas
     */
    public function testRegistrationWithMismatchedPasswords(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'jean.mismatch@test.fr',
            'register_user[plainPassword][first]' => 'Password123!@',
            'register_user[plainPassword][second]' => 'DifferentPass123!@', // Différent !
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('.invalid-feedback');
    }

    /**
     * Test 8 : Rejet d'un email invalide
     */
    public function testRegistrationWithInvalidEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'email-invalide', // Format email incorrect
            'register_user[plainPassword][first]' => 'Password123!@',
            'register_user[plainPassword][second]' => 'Password123!@',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('.invalid-feedback');
    }

    /**
     * Test 9 : Rejet si l'email existe déjà (unicité)
     *
     * Scénario :
     * 1. Un utilisateur avec l'email test@test.fr existe déjà (fixtures)
     * 2. On essaie de créer un nouveau compte avec le même email
     * 3. Le système doit rejeter l'inscription
     */
    public function testRegistrationWithExistingEmail(): void
    {
        $client = static::createClient();

        // D'abord, créer un utilisateur avec cet email
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $passwordHasher = $client->getContainer()->get('security.user_password_hasher');

        $existingUser = new User();
        $existingUser->setEmail('existing@test.fr');
        $existingUser->setFirstname('Existing');
        $existingUser->setLastname('User');
        $existingUser->setPassword(
            $passwordHasher->hashPassword($existingUser, 'Password123!@')
        );

        $entityManager->persist($existingUser);
        $entityManager->flush();

        // Maintenant, essayer de créer un compte avec le même email
        $crawler = $client->request('GET', '/inscription');

        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Jean',
            'register_user[lastname]' => 'Dupont',
            'register_user[email]' => 'existing@test.fr', // Email déjà utilisé
            'register_user[plainPassword][first]' => 'Password123!@',
            'register_user[plainPassword][second]' => 'Password123!@',
        ]);

        $client->submit($form);

        // L'inscription doit être rejetée
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('.invalid-feedback');
    }

    /**
     * Test 10 : Vérifier que l'email de bienvenue est envoyé lors d'une inscription réussie
     *
     * Ce test se concentre spécifiquement sur la vérification de l'envoi d'email
     */
    public function testWelcomeEmailIsSentOnSuccessfulRegistration(): void
    {
        $client = static::createClient();

        // Activer le profiler pour capturer les emails
        $client->enableProfiler();

        // 1. Accéder à la page d'inscription
        $crawler = $client->request('GET', '/inscription');

        // 2. Remplir et soumettre le formulaire
        $form = $crawler->selectButton('Créer mon compte')->form([
            'register_user[firstname]' => 'Marie',
            'register_user[lastname]' => 'Martin',
            'register_user[email]' => 'marie.martin@test.fr',
            'register_user[plainPassword][first]' => 'SecurePass123!@',
            'register_user[plainPassword][second]' => 'SecurePass123!@',
        ]);

        $client->submit($form);

        // 3. Vérifier qu'exactement 1 email a été envoyé
        $this->assertEmailCount(1);

        // 4. Récupérer l'email envoyé
        $email = $this->getMailerMessage();

        // 5. Vérifications sur l'email
        $this->assertInstanceOf(Email::class, $email);

        // Vérifier le destinataire
        $this->assertEmailAddressContains($email, 'To', 'marie.martin@test.fr');

        // Vérifier l'expéditeur
        $this->assertEmailAddressContains($email, 'From', 'contact@vitegourmand.fr');

        // Vérifier le sujet
        $this->assertEmailHeaderSame($email, 'Subject', 'Bienvenue chez Vite & Gourmand !');

        // Vérifier le contenu HTML de l'email
        $this->assertEmailHtmlBodyContains($email, 'Marie Martin');
        $this->assertEmailHtmlBodyContains($email, 'Bienvenue chez Vite & Gourmand !');
        $this->assertEmailHtmlBodyContains($email, 'Nous sommes ravis de vous compter parmi nos clients');
        $this->assertEmailHtmlBodyContains($email, 'Découvrir nos menus');
    }
}
