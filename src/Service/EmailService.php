<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service centralisé pour l'envoi d'emails
 * Ce service utilise Symfony Mailer et les templates Twig
 */
class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * Envoie un email de contact depuis le formulaire public
     *
     * @param string $senderName Nom de l'expéditeur
     * @param string $senderEmail Email de l'expéditeur
     * @param string $subject Sujet du message
     * @param string $message Contenu du message
     */
    public function sendContactEmail(
        string $senderName,
        string $senderEmail,
        string $subject,
        string $message
    ): void {
        $email = (new TemplatedEmail())
            // De qui vient l'email (l'utilisateur qui remplit le formulaire)
            ->from(new Address($senderEmail, $senderName))

            // À qui envoyer (l'adresse du restaurant)
            ->to(new Address('contact@vitegourmand.fr', 'Vite & Gourmand'))

            // Sujet de l'email
            ->subject('[Contact] ' . $subject)

            // Template Twig pour le rendu HTML
            ->htmlTemplate('emails/contact.html.twig')

            // Variables passées au template
            ->context([
                'senderName' => $senderName,
                'senderEmail' => $senderEmail,
                'subject' => $subject,
                'message' => $message,
            ])
        ;

        // Envoi de l'email
        $this->mailer->send($email);
    }
}