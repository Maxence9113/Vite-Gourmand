<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifie le statut du compte utilisateur lors de l'authentification
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Vérifie l'utilisateur avant l'authentification
     *
     * @param UserInterface $user
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si le compte est activé
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été désactivé. Veuillez contacter l\'administration.');
        }
    }

    /**
     * Vérifie l'utilisateur après l'authentification
     *
     * @param UserInterface $user
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérification additionnelle après authentification si nécessaire
        // Par exemple, vérifier si le compte a été désactivé pendant la session
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été désactivé. Veuillez contacter l\'administration.');
        }
    }
}