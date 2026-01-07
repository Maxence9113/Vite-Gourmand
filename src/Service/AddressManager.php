<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des adresses utilisateur
 * Centralise la logique métier liée aux adresses
 */
final class AddressManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository $addressRepository
    ) {
    }

    /**
     * Définit une adresse comme adresse par défaut
     * Retire automatiquement le flag des autres adresses de l'utilisateur
     */
    public function setAsDefault(Address $address): void
    {
        $user = $address->getUser();

        if (!$user) {
            throw new \LogicException('L\'adresse doit être associée à un utilisateur.');
        }

        // Retirer le flag par défaut de toutes les autres adresses de l'utilisateur
        $this->clearDefaultAddresses($user);

        // Définir cette adresse comme par défaut
        $address->setIsDefault(true);
    }

    /**
     * Retire le flag "par défaut" de toutes les adresses d'un utilisateur
     */
    public function clearDefaultAddresses(User $user): void
    {
        $defaultAddresses = $this->addressRepository->findBy([
            'user' => $user,
            'isDefault' => true
        ]);

        foreach ($defaultAddresses as $defaultAddress) {
            $defaultAddress->setIsDefault(false);
        }
    }

    /**
     * Retire le flag "par défaut" de toutes les adresses d'un utilisateur
     * sauf celle spécifiée
     */
    public function clearDefaultAddressesExcept(User $user, Address $exceptAddress): void
    {
        $defaultAddresses = $this->entityManager
            ->getRepository(Address::class)
            ->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.isDefault = true')
            ->andWhere('a.id != :exceptId')
            ->setParameter('user', $user)
            ->setParameter('exceptId', $exceptAddress->getId())
            ->getQuery()
            ->getResult();

        foreach ($defaultAddresses as $defaultAddress) {
            $defaultAddress->setIsDefault(false);
        }
    }

    /**
     * Vérifie que l'adresse appartient bien à l'utilisateur spécifié
     */
    public function belongsToUser(Address $address, User $user): bool
    {
        return $address->getUser() === $user;
    }

    /**
     * Prépare une nouvelle adresse pour un utilisateur
     * Gère automatiquement les adresses par défaut si nécessaire
     */
    public function prepareNewAddress(Address $address): void
    {
        // Si cette adresse est définie par défaut, retirer le flag des autres
        if ($address->isDefault()) {
            $user = $address->getUser();
            if ($user) {
                $this->clearDefaultAddresses($user);
            }
        }
    }

    /**
     * Prépare la mise à jour d'une adresse existante
     * Gère automatiquement les adresses par défaut si nécessaire
     */
    public function prepareUpdateAddress(Address $address): void
    {
        // Si cette adresse est définie par défaut, retirer le flag des autres
        if ($address->isDefault()) {
            $user = $address->getUser();
            if ($user) {
                $this->clearDefaultAddressesExcept($user, $address);
            }
        }
    }
}