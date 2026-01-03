<?php

namespace App\Tests\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels du contrôleur de réinitialisation de mot de passe
 *
 * Ces tests vérifient le comportement complet de la fonctionnalité de réinitialisation de mot de passe
 * selon les exigences du cahier des charges (page 5)
 */
class PasswordResetControllerTest extends WebTestCase
{
    use \Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

    /**
     * Test 1 : Vérifier que la page de demande de réinitialisation est accessible
     */
    public function testPasswordResetRequestPageIsAccessible(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/password-reset/request');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mot de passe oublié');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    /**
     * Test 2 : Demande de réinitialisation réussie avec un email valide
     *
     * Scénario :
     * 1. Un utilisateur entre son email
     * 2. Un email est envoyé avec un lien de réinitialisation
     * 3. Un token est créé en base de données
     * 4. Le token a une durée de validité de 1 heure
     */
    public function testSuccessfulPasswordResetRequest(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Créer un utilisateur de test
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('test.reset@example.com');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'Password123!'));

        $entityManager->persist($user);
        $entityManager->flush();

        // Accéder à la page de demande de réinitialisation
        $crawler = $client->request('GET', '/password-reset/request');

        // Soumettre le formulaire avec un email valide
        $form = $crawler->filter('form')->form([
            'email' => 'test.reset@example.com',
        ]);

        $client->submit($form);

        // Vérifier la redirection
        $this->assertResponseRedirects('/password-reset/request');

        // Vérifier qu'un email a été envoyé
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();
        $this->assertInstanceOf(Email::class, $email);
        $this->assertEmailAddressContains($email, 'To', 'test.reset@example.com');
        $this->assertEmailHeaderSame($email, 'Subject', 'Réinitialisation de votre mot de passe');

        // Vérifier que le corps de l'email contient le prénom et des informations importantes
        $this->assertEmailHtmlBodyContains($email, 'Test');
        $this->assertEmailHtmlBodyContains($email, 'réinitialiser votre mot de passe');
        $this->assertEmailHtmlBodyContains($email, 'valable pendant 1 heure');

        // Vérifier qu'un token a été créé en base de données
        $tokenRepository = $entityManager->getRepository(PasswordResetToken::class);
        $tokens = $tokenRepository->findBy(['user' => $user]);

        $this->assertCount(1, $tokens);
        $this->assertTrue($tokens[0]->isValid());
        $this->assertNotNull($tokens[0]->getToken());
        $this->assertEquals(64, strlen($tokens[0]->getToken())); // 32 bytes en hexadécimal = 64 caractères

        // Nettoyer
        foreach ($tokens as $token) {
            $entityManager->remove($token);
        }
        $user = $entityManager->find(User::class, $user->getId());
        if ($user) {
            $entityManager->remove($user);
        }
        $entityManager->flush();
    }

    /**
     * Test 3 : Demande de réinitialisation avec un email inexistant
     *
     * Le message doit être identique pour ne pas révéler si l'email existe ou non (sécurité)
     */
    public function testPasswordResetRequestWithNonExistentEmail(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $crawler = $client->request('GET', '/password-reset/request');

        // Soumettre avec un email qui n'existe pas
        $form = $crawler->filter('form')->form([
            'email' => 'nonexistent@example.com',
        ]);

        $client->submit($form);

        // Vérifier la redirection
        $this->assertResponseRedirects('/password-reset/request');

        // Vérifier qu'aucun email n'a été envoyé
        $this->assertEmailCount(0);

        // Mais le message flash doit être identique pour des raisons de sécurité
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Si un compte existe avec cet email');
    }

    /**
     * Test 4 : Demande de réinitialisation pour un utilisateur désactivé
     *
     * Aucun email ne doit être envoyé pour un compte désactivé
     */
    public function testPasswordResetRequestWithDisabledUser(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Créer un utilisateur désactivé
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('disabled@example.com');
        $user->setFirstname('Disabled');
        $user->setLastname('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'Password123!'));
        $user->setIsEnabled(false); // Utilisateur désactivé

        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/password-reset/request');

        $form = $crawler->filter('form')->form([
            'email' => 'disabled@example.com',
        ]);

        $client->submit($form);

        // Vérifier qu'aucun email n'a été envoyé
        $this->assertEmailCount(0);

        // Le message doit être identique (sécurité)
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Si un compte existe avec cet email');

        // Nettoyer
        $user = $entityManager->find(User::class, $user->getId());
        if ($user) {
            $entityManager->remove($user);
            $entityManager->flush();
        }
    }

    /**
     * Test 5 : Réinitialisation réussie avec un token valide
     */
    public function testSuccessfulPasswordReset(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Créer un utilisateur de test
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('reset.success@example.com');
        $user->setFirstname('Reset');
        $user->setLastname('Success');
        $user->setPassword($passwordHasher->hashPassword($user, 'OldPassword123!'));

        $entityManager->persist($user);
        $entityManager->flush();

        // Créer un token valide
        $token = bin2hex(random_bytes(32));
        $passwordResetToken = new PasswordResetToken();
        $passwordResetToken->setUser($user);
        $passwordResetToken->setToken($token);

        $entityManager->persist($passwordResetToken);
        $entityManager->flush();

        // Accéder à la page de réinitialisation avec le token
        $crawler = $client->request('GET', '/password-reset/reset/' . $token);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Nouveau mot de passe');

        // Remplir le formulaire avec un nouveau mot de passe
        $form = $crawler->filter('form')->form([
            'password' => 'NewPassword123!',
            'confirm_password' => 'NewPassword123!',
        ]);

        $client->submit($form);

        // Vérifier la redirection vers la page de connexion
        $this->assertResponseRedirects('/connexion');

        // Vérifier que le mot de passe a été changé
        $user = $entityManager->find(User::class, $user->getId());
        $this->assertTrue($passwordHasher->isPasswordValid($user, 'NewPassword123!'));
        $this->assertFalse($passwordHasher->isPasswordValid($user, 'OldPassword123!'));

        // Vérifier que le token a été marqué comme utilisé
        $passwordResetToken = $entityManager->find(PasswordResetToken::class, $passwordResetToken->getId());
        $this->assertTrue($passwordResetToken->isUsed());
        $this->assertFalse($passwordResetToken->isValid());

        // Nettoyer
        $entityManager->remove($passwordResetToken);
        $entityManager->remove($user);
        $entityManager->flush();
    }

    /**
     * Test 6 : Tentative de réinitialisation avec un token invalide
     */
    public function testPasswordResetWithInvalidToken(): void
    {
        $client = static::createClient();

        // Utiliser un token qui n'existe pas
        $client->request('GET', '/password-reset/reset/invalid-token-12345');

        // Vérifier la redirection vers la page de demande
        $this->assertResponseRedirects('/password-reset/request');

        // Suivre la redirection et vérifier le message d'erreur
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Ce lien de réinitialisation est invalide ou a expiré', $crawler->text());
    }

    /**
     * Test 7 : Validation du mot de passe - trop court
     */
    public function testPasswordResetWithTooShortPassword(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Créer un utilisateur et un token
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('short.password@example.com');
        $user->setFirstname('Short');
        $user->setLastname('Password');
        $user->setPassword($passwordHasher->hashPassword($user, 'Password123!'));

        $entityManager->persist($user);
        $entityManager->flush();

        $token = bin2hex(random_bytes(32));
        $passwordResetToken = new PasswordResetToken();
        $passwordResetToken->setUser($user);
        $passwordResetToken->setToken($token);

        $entityManager->persist($passwordResetToken);
        $entityManager->flush();

        // Tenter avec un mot de passe trop court
        $crawler = $client->request('GET', '/password-reset/reset/' . $token);
        $form = $crawler->filter('form')->form([
            'password' => 'Short1!',
            'confirm_password' => 'Short1!',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/password-reset/reset/' . $token);

        $crawler = $client->followRedirect();
        $this->assertStringContainsString('10 caractères', $crawler->text());

        // Nettoyer
        $passwordResetToken = $entityManager->find(PasswordResetToken::class, $passwordResetToken->getId());
        if ($passwordResetToken) {
            $entityManager->remove($passwordResetToken);
        }
        $user = $entityManager->find(User::class, $user->getId());
        if ($user) {
            $entityManager->remove($user);
        }
        $entityManager->flush();
    }

    /**
     * Test 8 : Les mots de passe doivent correspondre
     */
    public function testPasswordResetWithMismatchedPasswords(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('mismatch@example.com');
        $user->setFirstname('Mismatch');
        $user->setLastname('Test');
        $user->setPassword($passwordHasher->hashPassword($user, 'Password123!'));

        $entityManager->persist($user);
        $entityManager->flush();

        $token = bin2hex(random_bytes(32));
        $passwordResetToken = new PasswordResetToken();
        $passwordResetToken->setUser($user);
        $passwordResetToken->setToken($token);

        $entityManager->persist($passwordResetToken);
        $entityManager->flush();

        // Soumettre avec des mots de passe différents
        $crawler = $client->request('GET', '/password-reset/reset/' . $token);
        $form = $crawler->filter('form')->form([
            'password' => 'Password123!',
            'confirm_password' => 'DifferentPassword123!',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/password-reset/reset/' . $token);

        $crawler = $client->followRedirect();
        $this->assertStringContainsString('ne correspondent pas', $crawler->text());

        // Nettoyer
        $passwordResetToken = $entityManager->find(PasswordResetToken::class, $passwordResetToken->getId());
        if ($passwordResetToken) {
            $entityManager->remove($passwordResetToken);
        }
        $user = $entityManager->find(User::class, $user->getId());
        if ($user) {
            $entityManager->remove($user);
        }
        $entityManager->flush();
    }

    /**
     * Test 9 : Un utilisateur connecté est redirigé s'il accède à la page de réinitialisation
     */
    public function testLoggedInUserCannotAccessPasswordReset(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('logged@example.com');
        $user->setFirstname('Logged');
        $user->setLastname('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'Password123!'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        // Tenter d'accéder à la page de demande de réinitialisation
        $client->request('GET', '/password-reset/request');
        $this->assertResponseRedirects('/compte');

        // Nettoyer
        $user = $entityManager->find(User::class, $user->getId());
        if ($user) {
            $entityManager->remove($user);
            $entityManager->flush();
        }
    }

    /**
     * Test 10 : Vérifier que le lien "Mot de passe oublié" est présent sur la page de connexion
     */
    public function testForgotPasswordLinkExistsOnLoginPage(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/password-reset/request"]');
        $this->assertSelectorTextContains('a[href="/password-reset/request"]', 'Mot de passe oublié');
    }
}
