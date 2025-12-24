<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/compte', name: 'app_account')]
    public function index(): Response
    {
        return $this->render('account/index.html.twig');
    }

    #[Route('/compte/modifier-profil', name: 'app_account_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');

            // Validation
            if (!$firstname || !$lastname) {
                $this->addFlash('error', 'Tous les champs sont requis.');
                return $this->redirectToRoute('app_account_edit');
            }

            // Mise à jour des informations
            $user->setFirstname($firstname);
            $user->setLastname($lastname);

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/edit.html.twig');
    }

    #[Route('/compte/modifier-mot-de-passe', name: 'app_account_pwd_modify')]
    public function password(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        // Récupération du User connecté
        $user = $this->getUser();

        // passing options to form (symfony Docs)
        $form = $this->createForm(PasswordUserType::class, $user, [
            'userPasswordHasher' => $userPasswordHasher
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) { 

            $entityManager->flush();
            $this->addFlash(
                'success',
                'Votre mot de passe a été mis à jour'
            );

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/password.html.twig', [
            'modifyPwd' => $form->createView(),
        ]);
    }
}
