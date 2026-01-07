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
    private const COMPANY_EMAIL = 'contact@vitegourmand.fr';
    private const COMPANY_NAME = 'Vite & Gourmand';

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
            ->from(new Address($senderEmail, $senderName))
            ->to(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->subject('[Contact] ' . $subject)
            ->htmlTemplate('emails/contact.html.twig')
            ->context([
                'senderName' => $senderName,
                'senderEmail' => $senderEmail,
                'subject' => $subject,
                'message' => $message,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de bienvenue lors de la création d'un compte utilisateur
     *
     * @param string $userEmail Email de l'utilisateur
     * @param string $userFirstname Prénom de l'utilisateur
     * @param string $userLastname Nom de l'utilisateur
     */
    public function sendWelcomeEmail(
        string $userEmail,
        string $userFirstname,
        string $userLastname
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->to(new Address($userEmail, "$userFirstname $userLastname"))
            ->subject('Bienvenue chez ' . self::COMPANY_NAME . ' !')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'firstname' => $userFirstname,
                'lastname' => $userLastname,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     *
     * @param string $userEmail Email de l'utilisateur
     * @param string $userFirstname Prénom de l'utilisateur
     * @param string $resetToken Token de réinitialisation
     * @param string $resetUrl URL de réinitialisation
     */
    public function sendPasswordResetEmail(
        string $userEmail,
        string $userFirstname,
        string $resetToken,
        string $resetUrl
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->to(new Address($userEmail, $userFirstname))
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'firstname' => $userFirstname,
                'resetUrl' => $resetUrl,
                'resetToken' => $resetToken,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de validation de commande (quand l'employé accepte la commande)
     *
     * @param string $userEmail Email du client
     * @param string $userFirstname Prénom du client
     * @param string $orderNumber Numéro de commande
     * @param \DateTimeImmutable $deliveryDateTime Date et heure de livraison
     */
    public function sendOrderValidatedEmail(
        string $userEmail,
        string $userFirstname,
        string $orderNumber,
        \DateTimeImmutable $deliveryDateTime
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->to(new Address($userEmail, $userFirstname))
            ->subject('Votre commande ' . $orderNumber . ' a été validée !')
            ->htmlTemplate('emails/order_validated.html.twig')
            ->context([
                'firstname' => $userFirstname,
                'orderNumber' => $orderNumber,
                'deliveryDateTime' => $deliveryDateTime,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de confirmation de commande
     *
     * @param string $userEmail Email du client
     * @param string $userFirstname Prénom du client
     * @param string $userLastname Nom du client
     * @param string $orderNumber Numéro de commande
     * @param string $menuName Nom du menu
     * @param int $numberOfPersons Nombre de personnes
     * @param int $totalPrice Prix total en centimes
     * @param \DateTimeImmutable $deliveryDateTime Date et heure de livraison
     * @param string $deliveryAddress Adresse de livraison
     * @param int $menuSubtotal Sous-total du menu en centimes
     * @param int $deliveryCost Frais de livraison en centimes
     * @param int|null $discountAmount Montant de la réduction en centimes (null si pas de réduction)
     * @param int|null $deliveryDistanceKm Distance de livraison en km (null si dans Bordeaux)
     */
    public function sendOrderConfirmationEmail(
        string $userEmail,
        string $userFirstname,
        string $userLastname,
        string $orderNumber,
        string $menuName,
        int $numberOfPersons,
        int $totalPrice,
        \DateTimeImmutable $deliveryDateTime,
        string $deliveryAddress,
        int $menuSubtotal = 0,
        int $deliveryCost = 500,
        ?int $discountAmount = null,
        ?int $deliveryDistanceKm = null
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->to(new Address($userEmail, "$userFirstname $userLastname"))
            ->subject('Confirmation de votre commande ' . $orderNumber)
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'firstname' => $userFirstname,
                'lastname' => $userLastname,
                'orderNumber' => $orderNumber,
                'menuName' => $menuName,
                'numberOfPersons' => $numberOfPersons,
                'totalPrice' => $totalPrice / 100, // Conversion centimes en euros
                'deliveryDateTime' => $deliveryDateTime,
                'deliveryAddress' => $deliveryAddress,
                'menuSubtotal' => $menuSubtotal / 100, // Conversion centimes en euros
                'deliveryCost' => $deliveryCost / 100, // Conversion centimes en euros
                'discountAmount' => $discountAmount ? $discountAmount / 100 : null, // Conversion centimes en euros
                'deliveryDistanceKm' => $deliveryDistanceKm,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de notification lorsque la commande est terminée
     * pour inviter l'utilisateur à laisser un avis
     *
     * @param string $userEmail Email du client
     * @param string $userFirstname Prénom du client
     * @param string $orderNumber Numéro de commande
     * @param string $reviewUrl URL pour laisser un avis
     */
    public function sendOrderCompletedEmail(
        string $userEmail,
        string $userFirstname,
        string $orderNumber,
        string $reviewUrl
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->to(new Address($userEmail, $userFirstname))
            ->subject('Votre commande ' . $orderNumber . ' est terminée !')
            ->htmlTemplate('emails/order_completed.html.twig')
            ->context([
                'firstname' => $userFirstname,
                'orderNumber' => $orderNumber,
                'reviewUrl' => $reviewUrl,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de rappel de retour de matériel
     * Si le matériel n'est pas restitué sous 10 jours ouvrés, 600€ de frais seront facturés
     *
     * @param string $userEmail Email du client
     * @param string $userFirstname Prénom du client
     * @param string $orderNumber Numéro de commande
     * @param \DateTimeImmutable $deadline Date limite de retour
     */
    public function sendMaterialReturnReminderEmail(
        string $userEmail,
        string $userFirstname,
        string $orderNumber,
        \DateTimeImmutable $deadline
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->to(new Address($userEmail, $userFirstname))
            ->subject('Rappel : retour de matériel pour la commande ' . $orderNumber)
            ->htmlTemplate('emails/material_return_reminder.html.twig')
            ->context([
                'firstname' => $userFirstname,
                'orderNumber' => $orderNumber,
                'deadline' => $deadline,
                'penaltyAmount' => 600, // 600€ de frais selon le cahier des charges
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de notification à un nouvel employé
     * Le mot de passe n'est pas communiqué par email pour des raisons de sécurité
     *
     * @param string $employeeEmail Email de l'employé
     * @param string $employeeUsername Username de l'employé (son email)
     */
    public function sendEmployeeAccountCreatedEmail(
        string $employeeEmail,
        string $employeeUsername
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::COMPANY_EMAIL, self::COMPANY_NAME))
            ->to(new Address($employeeEmail))
            ->subject('Votre compte employé a été créé')
            ->htmlTemplate('emails/employee_account_created.html.twig')
            ->context([
                'username' => $employeeUsername,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * Envoie automatiquement les emails appropriés lors d'un changement de statut de commande
     * Centralise la logique d'envoi d'emails selon le nouveau statut
     *
     * @param \App\Entity\Order $order La commande concernée
     * @param \App\Enum\OrderStatus $newStatus Le nouveau statut
     * @param string|null $reviewUrl URL pour laisser un avis (requis pour statut COMPLETED)
     */
    public function sendStatusChangeNotification(
        \App\Entity\Order $order,
        \App\Enum\OrderStatus $newStatus,
        ?string $reviewUrl = null
    ): void {
        // Email de validation de commande (quand l'employé accepte la commande)
        if ($newStatus === \App\Enum\OrderStatus::VALIDATED) {
            $this->sendOrderValidatedEmail(
                userEmail: $order->getCustomerEmail(),
                userFirstname: $order->getCustomerFirstname(),
                orderNumber: $order->getOrderNumber(),
                deliveryDateTime: $order->getDeliveryDateTime()
            );
            return;
        }

        // Email de rappel de retour de matériel
        if ($newStatus === \App\Enum\OrderStatus::WAITING_MATERIAL_RETURN && $order->hasMaterialLoan()) {
            $this->sendMaterialReturnReminderEmail(
                userEmail: $order->getCustomerEmail(),
                userFirstname: $order->getCustomerFirstname(),
                orderNumber: $order->getOrderNumber(),
                deadline: $order->getMaterialReturnDeadline()
            );
            return;
        }

        // Email de commande terminée avec invitation à laisser un avis
        if ($newStatus === \App\Enum\OrderStatus::COMPLETED) {
            if (!$reviewUrl) {
                throw new \InvalidArgumentException(
                    'L\'URL de review est requise pour envoyer l\'email de commande terminée'
                );
            }

            $this->sendOrderCompletedEmail(
                userEmail: $order->getCustomerEmail(),
                userFirstname: $order->getCustomerFirstname(),
                orderNumber: $order->getOrderNumber(),
                reviewUrl: $reviewUrl
            );
            return;
        }

        // Pas d'email à envoyer pour les autres statuts
    }
}