<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Menu;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service centralisant la logique métier des commandes
 *
 * Responsabilités :
 * - Créer des commandes avec calcul automatique des prix (sous-total, livraison, réductions)
 * - Gérer le cycle de vie (changement de statut, annulation)
 * - Appliquer les règles métier (livraison gratuite Bordeaux, réduction 10% si ≥5 personnes, délai 48h)
 *
 * Utilisation :
 * - OrderController : créer et sauvegarder les commandes clients
 * - OrderAdminController : gérer les statuts et annulations
 */
class OrderManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Crée une nouvelle commande à partir d'un menu, d'une adresse et du nombre de personnes
     */
    public function createOrder(
        User $user,
        Menu $menu,
        Address $address,
        int $numberOfPersons,
        \DateTimeImmutable $deliveryDateTime,
        bool $hasMaterialLoan = false
    ): Order {
        // Vérifier si le menu est disponible en stock
        if (!$menu->isAvailable()) {
            throw new \LogicException('Ce menu n\'est plus disponible en stock');
        }

        // Vérifier si le stock est suffisant pour le nombre de personnes
        if ($menu->getStock() !== null && $menu->getStock() < $numberOfPersons) {
            throw new \LogicException(sprintf(
                'Stock insuffisant. Il reste %d place(s) disponible(s) pour ce menu, mais vous avez demandé %d personnes.',
                $menu->getStock(),
                $numberOfPersons
            ));
        }

        $order = new Order();

        // Informations utilisateur
        $order->setUser($user);
        $order->setCustomerFirstname($user->getFirstname());
        $order->setCustomerLastname($user->getLastname());
        $order->setCustomerEmail($user->getEmail());
        $order->setCustomerPhone($address->getPhone());

        // Informations de livraison
        $order->setDeliveryAddress(sprintf(
            '%s, %s %s',
            $address->getStreet(),
            $address->getPostalCode(),
            $address->getCity()
        ));
        $order->setDeliveryDateTime($deliveryDateTime);

        // Informations menu (snapshot au moment de la commande)
        $order->setMenuName($menu->getName());
        $order->setMenuPricePerPerson($menu->getPricePerPerson());
        $order->setNumberOfPersons($numberOfPersons);

        // Calcul des coûts
        $this->calculateOrderPricing($order, $menu, $address);

        // Emprunt de matériel
        $order->setHasMaterialLoan($hasMaterialLoan);
        if ($hasMaterialLoan) {
            // Deadline: 10 jours après la livraison
            $order->setMaterialReturnDeadline($deliveryDateTime->modify('+10 days'));
        }

        // Initialisation (statut, dates, numéro de commande)
        $order->initialize();

        return $order;
    }

    /**
     * Calcule tous les prix de la commande (sous-total, livraison, réduction, total)
     */
    public function calculateOrderPricing(Order $order, Menu $menu, Address $address): void
    {
        // Sous-total
        $menuSubtotal = $order->calculateMenuSubtotal();
        $order->setMenuSubtotal($menuSubtotal);

        // Coût de livraison
        $isInBordeaux = $this->isAddressInBordeaux($address);
        $distanceKm = $isInBordeaux ? null : $this->calculateDistance($address);
        $order->setDeliveryDistanceKm($distanceKm);

        $deliveryCost = $order->calculateDeliveryCost($isInBordeaux, $distanceKm);
        $order->setDeliveryCost($deliveryCost);

        // Réduction (10% si 5+ personnes au-delà du minimum)
        $discount = $order->calculateDiscount($menu->getNbPersonMin());
        $order->setDiscountAmount($discount);

        // Total
        $totalPrice = $order->calculateTotalPrice();
        $order->setTotalPrice($totalPrice);
    }

    /**
     * Vérifie si une adresse est dans Bordeaux
     */
    private function isAddressInBordeaux(Address $address): bool
    {
        $city = strtolower(trim($address->getCity()));
        $postalCode = $address->getPostalCode();

        // Bordeaux et ses quartiers
        return $city === 'bordeaux' || str_starts_with($postalCode, '330');
    }

    /**
     * Calcule la distance depuis Bordeaux (simulation basée sur le code postal)
     * Dans un vrai système, on utiliserait une API de géolocalisation
     */
    private function calculateDistance(Address $address): int
    {
        $postalCode = $address->getPostalCode();
        $firstTwoDigits = (int)substr($postalCode, 0, 2);

        // Simulation: plus le département est éloigné, plus la distance est grande
        // 33 = Gironde (base)
        $departmentDistance = abs($firstTwoDigits - 33);

        // Estimation: ~50km par département d'écart
        return max(10, $departmentDistance * 50);
    }

    /**
     * Change le statut d'une commande
     */
    public function changeOrderStatus(Order $order, OrderStatus $newStatus): void
    {
        $order->changeStatus($newStatus);
        $this->entityManager->flush();
    }

    /**
     * Annule une commande
     */
    public function cancelOrder(Order $order, string $reason): void
    {
        if (!$order->canBeCancelled()) {
            throw new \LogicException('Cette commande ne peut plus être annulée');
        }

        $order->setCancellationReason($reason);
        $order->changeStatus(OrderStatus::CANCELLED);

        $this->entityManager->flush();
    }

    /**
     * Persiste une commande en base de données et décrémente le stock du menu
     */
    public function saveOrder(Order $order, ?Menu $menu = null): void
    {
        $this->entityManager->persist($order);

        // Décrémenter le stock du menu si fourni (en fonction du nombre de personnes)
        if ($menu !== null) {
            $menu->decrementStock($order->getNumberOfPersons());
        }

        $this->entityManager->flush();
    }

    /**
     * Vérifie si la date de livraison est valide (minimum 48h à l'avance)
     */
    public function isValidDeliveryDate(\DateTimeImmutable $deliveryDateTime): bool
    {
        $now = new \DateTimeImmutable();
        $minDeliveryDate = $now->modify('+48 hours');

        return $deliveryDateTime >= $minDeliveryDate;
    }

    /**
     * Récupère les commandes d'un utilisateur
     */
    public function getUserOrders(User $user): array
    {
        return $this->entityManager
            ->getRepository(Order::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }
}