<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_EMPLOYEE')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'admin_users_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');

        $qb = $this->userRepository->createQueryBuilder('u');

        // Si l'utilisateur est EMPLOYEE (mais pas ADMIN), ne montrer que les utilisateurs ROLE_USER
        if ($this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('u.roles LIKE :roleUser')
               ->andWhere('u.roles NOT LIKE :roleEmployee')
               ->andWhere('u.roles NOT LIKE :roleAdmin')
               ->setParameter('roleUser', '%ROLE_USER%')
               ->setParameter('roleEmployee', '%ROLE_EMPLOYEE%')
               ->setParameter('roleAdmin', '%ROLE_ADMIN%');
        }

        // Filtre de recherche (email, nom ou prénom)
        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.lastname LIKE :search OR u.firstname LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par rôle (seulement pour les admins)
        if ($role && $this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%' . $role . '%');
        }

        // Filtre par statut
        if ($status !== '') {
            $qb->andWhere('u.isEnabled = :status')
               ->setParameter('status', (bool) $status);
        }

        $users = $qb->getQuery()->getResult();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/create-employee', name: 'admin_users_create_employee', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createEmployee(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');

            if ($email && $password && $firstname && $lastname) {
                $user = new User();
                $user->setEmail($email);
                $user->setFirstname($firstname);
                $user->setLastname($lastname);
                $user->setRoles(['ROLE_EMPLOYEE']);
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // TODO: Implémenter l'envoi d'email de notification

                $this->addFlash('success', 'Le compte employé a été créé avec succès.');

                return $this->redirectToRoute('admin_users_index');
            }

            $this->addFlash('error', 'Tous les champs sont requis.');
        }

        return $this->render('admin/user/create_employee.html.twig');
    }

    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, Request $request): Response
    {
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de désactiver un compte administrateur.');
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setIsEnabled(!$user->isEnabled());
        $this->entityManager->flush();

        $status = $user->isEnabled() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Le compte a été $status avec succès.");

        // Rediriger vers la page d'édition si on vient de là, sinon vers la liste
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, '/edit')) {
            return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request): Response
    {
        // Empêcher la modification des comptes admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de modifier un compte administrateur.');
            return $this->redirectToRoute('admin_users_index');
        }

        // Empêcher les employés de modifier d'autres employés
        if ($this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            if (in_array('ROLE_EMPLOYEE', $user->getRoles())) {
                $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier ce compte.');
                return $this->redirectToRoute('admin_users_index');
            }
        }

        if ($request->isMethod('POST')) {
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $newRole = $request->request->get('role');

            // Validation de base
            if (!$firstname || !$lastname || !$email) {
                $this->addFlash('error', 'Tous les champs sont requis.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
            }

            // Les employés ne peuvent pas modifier les rôles
            if ($this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
                if ($newRole && $newRole !== $user->getRoles()[0]) {
                    $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier le rôle.');
                    return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
                }
            } else {
                // Validation du rôle uniquement pour les admins
                if (!$newRole || !in_array($newRole, ['ROLE_USER', 'ROLE_EMPLOYEE'])) {
                    $this->addFlash('error', 'Rôle invalide.');
                    return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
                }
            }

            // Vérifier si l'email n'est pas déjà utilisé par un autre utilisateur
            $existingUser = $this->userRepository->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $user->getId()]);
            }

            // Mise à jour des informations
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);

            // Modifier le rôle seulement si admin et si fourni
            if ($this->isGranted('ROLE_ADMIN') && $newRole) {
                $user->setRoles([$newRole]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Les informations de l\'utilisateur ont été modifiées avec succès.');

            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/change-role', name: 'admin_users_change_role', methods: ['POST'])]
    public function changeRole(User $user, Request $request): Response
    {
        $newRole = $request->request->get('role');

        if (!in_array($newRole, ['ROLE_USER', 'ROLE_EMPLOYEE'])) {
            $this->addFlash('error', 'Rôle invalide.');
            return $this->redirectToRoute('admin_users_index');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de modifier le rôle d\'un administrateur.');
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setRoles([$newRole]);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le rôle a été modifié avec succès.');

        return $this->redirectToRoute('admin_users_index');
    }
}