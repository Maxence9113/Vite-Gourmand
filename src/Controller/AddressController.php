<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use App\Service\AddressManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des adresses utilisateur
 */
#[Route('/compte/adresses')]
#[IsGranted('ROLE_USER')]
final class AddressController extends AbstractController
{
    public function __construct(
        private readonly AddressManager $addressManager,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Liste des adresses de l'utilisateur connecté
     */
    #[Route('', name: 'app_account_addresses')]
    public function index(AddressRepository $addressRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $addresses = $addressRepository->findBy(
            ['user' => $user],
            ['isDefault' => 'DESC', 'id' => 'DESC']
        );

        return $this->render('account/addresses/index.html.twig', [
            'addresses' => $addresses,
        ]);
    }

    /**
     * Création d'une nouvelle adresse
     */
    #[Route('/nouvelle', name: 'app_account_address_new')]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = new Address();
        $address->setUser($user);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer les adresses par défaut via le service
            $this->addressManager->prepareNewAddress($address);

            $this->entityManager->persist($address);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre adresse a été ajoutée avec succès.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/addresses/form.html.twig', [
            'form' => $form,
            'isEdit' => false,
        ]);
    }

    /**
     * Modification d'une adresse existante
     */
    #[Route('/{id}/modifier', name: 'app_account_address_edit')]
    public function edit(Address $address, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'adresse appartient bien à l'utilisateur
        if (!$this->addressManager->belongsToUser($address, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette adresse.');
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer les adresses par défaut via le service
            $this->addressManager->prepareUpdateAddress($address);

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre adresse a été modifiée avec succès.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/addresses/form.html.twig', [
            'form' => $form,
            'isEdit' => true,
            'address' => $address,
        ]);
    }

    /**
     * Suppression d'une adresse
     */
    #[Route('/{id}/supprimer', name: 'app_account_address_delete', methods: ['POST'])]
    public function delete(Address $address, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'adresse appartient bien à l'utilisateur
        if (!$this->addressManager->belongsToUser($address, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette adresse.');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete-address-' . $address->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($address);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'adresse a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('app_account_addresses');
    }

    /**
     * Définir une adresse comme adresse par défaut
     */
    #[Route('/{id}/definir-par-defaut', name: 'app_account_address_set_default', methods: ['POST'])]
    public function setDefault(Address $address, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'adresse appartient bien à l'utilisateur
        if (!$this->addressManager->belongsToUser($address, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette adresse.');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('set-default-' . $address->getId(), $request->request->get('_token'))) {
            // Utiliser le service pour gérer les adresses par défaut
            $this->addressManager->setAsDefault($address);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'adresse a été définie comme adresse par défaut.');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('app_account_addresses');
    }
}