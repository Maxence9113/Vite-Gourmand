<?php

namespace App\Service;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;

/**
 * Service de calcul des statistiques sur les commandes
 * Centralise les comptages et métriques liées aux commandes
 */
final class OrderStatisticsService
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {
    }

    /**
     * Calcule les statistiques rapides pour le dashboard admin
     * Retourne le nombre de commandes par statut
     *
     * @return array{
     *     total: int,
     *     pending: int,
     *     validated: int,
     *     preparing: int,
     *     delivering: int,
     *     waitingMaterial: int,
     *     completed: int,
     *     cancelled: int
     * }
     */
    public function getQuickStats(): array
    {
        return [
            // Nombre total de commandes
            'total' => $this->orderRepository->count(),

            // Commandes en attente de validation
            'pending' => $this->orderRepository->count(['status' => OrderStatus::PENDING]),

            // Commandes validées (acceptées par l'admin)
            'validated' => $this->orderRepository->count(['status' => OrderStatus::VALIDATED]),

            // Commandes en cours de préparation
            'preparing' => $this->orderRepository->count(['status' => OrderStatus::PREPARING]),

            // Commandes en cours de livraison
            'delivering' => $this->orderRepository->count(['status' => OrderStatus::DELIVERING]),

            // Commandes en attente du retour de matériel
            'waitingMaterial' => $this->orderRepository->count(['status' => OrderStatus::WAITING_MATERIAL_RETURN]),

            // Commandes terminées
            'completed' => $this->orderRepository->count(['status' => OrderStatus::COMPLETED]),

            // Commandes annulées
            'cancelled' => $this->orderRepository->count(['status' => OrderStatus::CANCELLED]),
        ];
    }

    /**
     * Calcule le nombre de commandes "actives" (en cours de traitement)
     * Exclut les commandes terminées et annulées
     *
     * @return int Nombre de commandes actives
     */
    public function getActiveOrdersCount(): int
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [OrderStatus::COMPLETED, OrderStatus::CANCELLED])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le revenu total des commandes terminées
     *
     * @return float Revenu total en euros
     */
    public function getTotalRevenue(): float
    {
        $result = $this->orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status = :completed')
            ->setParameter('completed', OrderStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Compte les commandes en retard (deadline de retour matériel dépassée)
     *
     * @return int Nombre de commandes en retard
     */
    public function getOverdueMaterialReturnsCount(): int
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :waitingMaterial')
            ->andWhere('o.materialReturnDeadline < :now')
            ->andWhere('o.materialReturned = false')
            ->setParameter('waitingMaterial', OrderStatus::WAITING_MATERIAL_RETURN)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }
}