<?php

namespace App\Tests\Service;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailServiceTest extends KernelTestCase
{
    private EmailService $emailService;
    private MailerInterface $mailer;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->mailer = $container->get(MailerInterface::class);
        $this->emailService = $container->get(EmailService::class);
    }

    public function testSendContactEmail(): void
    {
        // Cette méthode teste que l'email est bien envoyé sans erreur
        // En environnement de test, les emails ne sont pas réellement envoyés
        
        $this->emailService->sendContactEmail(
            senderName: 'Jean Dupont',
            senderEmail: 'jean.dupont@example.com',
            subject: 'Question sur un menu',
            message: 'Bonjour, je souhaite des informations sur vos menus.'
        );

        // Si on arrive ici sans exception, le test passe
        $this->assertTrue(true);
    }

    public function testSendContactEmailWithSpecialCharacters(): void
    {
        // Test avec des caractères spéciaux
        $this->emailService->sendContactEmail(
            senderName: 'Jean-François O\'Reilly',
            senderEmail: 'jean.francois@example.com',
            subject: 'Événement spécial été 2024',
            message: 'Bonjour, voici mon message avec des caractères accentués: été, noël, côté.'
        );

        $this->assertTrue(true);
    }

    public function testSendContactEmailWithLongMessage(): void
    {
        // Test avec un message long
        $longMessage = str_repeat('Ceci est un test de message très long. ', 50);
        
        $this->emailService->sendContactEmail(
            senderName: 'Test User',
            senderEmail: 'test@example.com',
            subject: 'Message très long',
            message: $longMessage
        );

        $this->assertTrue(true);
    }
}
