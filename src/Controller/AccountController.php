<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Form\PasswordUserType;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AccountController extends AbstractController
{
    #[Route('/compte', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Récupérer les 3 dernières commandes de l'utilisateur
        $recentOrders = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            3
        );

        // Compter le nombre total de commandes
        $totalOrders = $orderRepository->count(['user' => $user]);

        return $this->render('account/index.html.twig', [
            'recentOrders' => $recentOrders,
            'totalOrders' => $totalOrders,
        ]);
    }

    #[Route('/compte/modifier-profil', name: 'app_account_edit')]
    #[IsGranted('ROLE_USER')]
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
    #[IsGranted('ROLE_USER')]
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

    #[Route('/compte/adresses', name: 'app_account_addresses')]
    #[IsGranted('ROLE_USER')]
    public function addresses(AddressRepository $addressRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $addresses = $addressRepository->findBy(['user' => $user], ['isDefault' => 'DESC', 'id' => 'DESC']);

        return $this->render('account/addresses/index.html.twig', [
            'addresses' => $addresses,
        ]);
    }

    #[Route('/compte/adresses/nouvelle', name: 'app_account_address_new')]
    #[IsGranted('ROLE_USER')]
    public function newAddress(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = new Address();
        $address->setUser($user);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si cette adresse est définie par défaut, retirer le flag des autres adresses
            if ($address->isDefault()) {
                $existingAddresses = $entityManager->getRepository(Address::class)
                    ->findBy(['user' => $user, 'isDefault' => true]);

                foreach ($existingAddresses as $existingAddress) {
                    $existingAddress->setIsDefault(false);
                }
            }

            $entityManager->persist($address);
            $entityManager->flush();

            $this->addFlash('success', 'Votre adresse a été ajoutée avec succès.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/addresses/form.html.twig', [
            'form' => $form,
            'isEdit' => false,
        ]);
    }

    #[Route('/compte/adresses/{id}/modifier', name: 'app_account_address_edit')]
    #[IsGranted('ROLE_USER')]
    public function editAddress(Address $address, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'adresse appartient bien à l'utilisateur
        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette adresse.');
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si cette adresse est définie par défaut, retirer le flag des autres adresses
            if ($address->isDefault()) {
                $existingAddresses = $entityManager->getRepository(Address::class)
                    ->createQueryBuilder('a')
                    ->where('a.user = :user')
                    ->andWhere('a.isDefault = true')
                    ->andWhere('a.id != :currentId')
                    ->setParameter('user', $user)
                    ->setParameter('currentId', $address->getId())
                    ->getQuery()
                    ->getResult();

                foreach ($existingAddresses as $existingAddress) {
                    $existingAddress->setIsDefault(false);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre adresse a été modifiée avec succès.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/addresses/form.html.twig', [
            'form' => $form,
            'isEdit' => true,
            'address' => $address,
        ]);
    }

    #[Route('/compte/adresses/{id}/supprimer', name: 'app_account_address_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAddress(Address $address, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'adresse appartient bien à l'utilisateur
        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette adresse.');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete-address-' . $address->getId(), $request->request->get('_token'))) {
            $entityManager->remove($address);
            $entityManager->flush();

            $this->addFlash('success', 'L\'adresse a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('app_account_addresses');
    }

    #[Route('/compte/adresses/{id}/definir-par-defaut', name: 'app_account_address_set_default', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function setDefaultAddress(Address $address, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'adresse appartient bien à l'utilisateur
        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette adresse.');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('set-default-' . $address->getId(), $request->request->get('_token'))) {
            // Retirer le flag par défaut de toutes les autres adresses
            $existingAddresses = $entityManager->getRepository(Address::class)
                ->findBy(['user' => $user, 'isDefault' => true]);

            foreach ($existingAddresses as $existingAddress) {
                $existingAddress->setIsDefault(false);
            }

            // Définir cette adresse comme par défaut
            $address->setIsDefault(true);
            $entityManager->flush();

            $this->addFlash('success', 'L\'adresse a été définie comme adresse par défaut.');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('app_account_addresses');
    }

    /**
     * API endpoint pour obtenir la ville depuis un code postal
     * Utilise l'API du gouvernement français (api-adresse.data.gouv.fr)
     */
    #[Route('/api/adresse/ville-depuis-code-postal/{postalCode}', name: 'app_address_get_city_from_postal_code', methods: ['GET'])]
    public function getCityFromPostalCode(string $postalCode, HttpClientInterface $httpClient): JsonResponse
    {
        // Validation basique du code postal
        if (!preg_match('/^[0-9]{5}$/', $postalCode)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Code postal invalide'
            ], 400);
        }

        try {
            // Appel à l'API du gouvernement français pour géocoder le code postal
            // On augmente la limite pour obtenir plusieurs résultats
            $response = $httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => [
                    'q' => $postalCode,
                    'limit' => 20
                ]
            ]);

            $data = $response->toArray();

            // Vérifier si on a des résultats
            if (empty($data['features'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Aucune ville trouvée pour ce code postal'
                ], 404);
            }

            // Extraire toutes les villes uniques pour ce code postal
            $cities = [];
            foreach ($data['features'] as $feature) {
                $city = $feature['properties']['city'] ?? null;
                $postcode = $feature['properties']['postcode'] ?? null;

                // Vérifier que la ville existe et que le code postal correspond exactement
                if ($city && $postcode === $postalCode && !in_array($city, $cities, true)) {
                    $cities[] = $city;
                }
            }

            if (empty($cities)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Aucune ville trouvée pour ce code postal'
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'cities' => $cities
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération de la ville : ' . $e->getMessage()
            ], 500);
        }
    }
}
