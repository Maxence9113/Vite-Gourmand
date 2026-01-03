<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/password-reset')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordResetTokenRepository $tokenRepository,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Page de demande de réinitialisation de mot de passe
     */
    #[Route('/request', name: 'app_password_reset_request', methods: ['GET', 'POST'])]
    public function request(Request $request): Response
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers son compte
        if ($this->getUser()) {
            return $this->redirectToRoute('app_account');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            // Toujours afficher le même message pour des raisons de sécurité
            // (ne pas révéler si un email existe dans la base)
            $this->addFlash('success', 'Si un compte existe avec cet email, un lien de réinitialisation vous a été envoyé.');

            // Rechercher l'utilisateur
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user && $user->isEnabled()) {
                // Supprimer les anciens tokens pour cet utilisateur
                $this->tokenRepository->deleteExpiredOrUsedTokensForUser($user);

                // Générer un token sécurisé
                $token = bin2hex(random_bytes(32));

                // Créer l'entité token
                $passwordResetToken = new PasswordResetToken();
                $passwordResetToken->setUser($user);
                $passwordResetToken->setToken($token);

                $this->entityManager->persist($passwordResetToken);
                $this->entityManager->flush();

                // Générer l'URL de réinitialisation
                $resetUrl = $this->generateUrl(
                    'app_password_reset_reset',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                // Envoyer l'email
                $this->emailService->sendPasswordResetEmail(
                    userEmail: $user->getEmail(),
                    userFirstname: $user->getFirstname(),
                    resetToken: $token,
                    resetUrl: $resetUrl
                );
            }

            return $this->redirectToRoute('app_password_reset_request');
        }

        return $this->render('password_reset/request.html.twig');
    }

    /**
     * Page de réinitialisation du mot de passe avec le token
     */
    #[Route('/reset/{token}', name: 'app_password_reset_reset', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request): Response
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers son compte
        if ($this->getUser()) {
            return $this->redirectToRoute('app_account');
        }

        // Vérifier si le token existe et est valide
        $passwordResetToken = $this->tokenRepository->findValidToken($token);

        if (!$passwordResetToken) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_password_reset_request');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validation du mot de passe
            if (!$password || !$confirmPassword) {
                $this->addFlash('error', 'Tous les champs sont requis.');
                return $this->redirectToRoute('app_password_reset_reset', ['token' => $token]);
            }

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_password_reset_reset', ['token' => $token]);
            }

            // Validation de la complexité du mot de passe (selon le cahier des charges)
            if (strlen($password) < 10) {
                $this->addFlash('error', 'Le mot de passe doit contenir au minimum 10 caractères.');
                return $this->redirectToRoute('app_password_reset_reset', ['token' => $token]);
            }

            if (!preg_match('/[A-Z]/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule.');
                return $this->redirectToRoute('app_password_reset_reset', ['token' => $token]);
            }

            if (!preg_match('/[a-z]/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une minuscule.');
                return $this->redirectToRoute('app_password_reset_reset', ['token' => $token]);
            }

            if (!preg_match('/[0-9]/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins un chiffre.');
                return $this->redirectToRoute('app_password_reset_reset', ['token' => $token]);
            }

            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins un caractère spécial.');
                return $this->redirectToRoute('app_password_reset_reset', ['token' => $token]);
            }

            // Récupérer l'utilisateur et mettre à jour son mot de passe
            $user = $passwordResetToken->getUser();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Marquer le token comme utilisé
            $passwordResetToken->setIsUsed(true);

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('password_reset/reset.html.twig', [
            'token' => $token,
        ]);
    }
}