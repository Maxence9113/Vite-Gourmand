<?php

namespace App\Service;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Service de filtrage des commandes
 * Centralise toute la logique de filtrage, recherche et tri des commandes
 */
final class OrderFilterService
{
    /**
     * Champs autorisés pour le tri
     */
    private const VALID_SORT_FIELDS = ['createdAt', 'deliveryDateTime', 'totalPrice', 'status'];

    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {
    }

    /**
     * Filtre les commandes selon les critères fournis
     *
     * @param string|null $statusFilter Statut à filtrer (ex: 'pending', 'validated', 'all')
     * @param string|null $searchFilter Terme de recherche (numéro commande, nom, email)
     * @param string|null $dateFilter Filtre de date ('today', 'week', 'month')
     * @param string $sortBy Champ de tri (par défaut 'createdAt')
     * @param string $sortOrder Ordre de tri ('ASC' ou 'DESC', par défaut 'DESC')
     * @return array Liste des commandes filtrées
     */
    public function filterOrders(
        ?string $statusFilter = null,
        ?string $searchFilter = null,
        ?string $dateFilter = null,
        string $sortBy = 'createdAt',
        string $sortOrder = 'DESC'
    ): array {
        // Créer le QueryBuilder de base
        $qb = $this->createBaseQueryBuilder();

        // Appliquer les filtres
        $this->applyStatusFilter($qb, $statusFilter);
        $this->applySearchFilter($qb, $searchFilter);
        $this->applyDateFilter($qb, $dateFilter);
        $this->applySorting($qb, $sortBy, $sortOrder);

        return $qb->getQuery()->getResult();
    }

    /**
     * Crée le QueryBuilder de base avec les jointures nécessaires
     */
    private function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u');
    }

    /**
     * Applique le filtre de statut
     *
     * @param QueryBuilder $qb Le QueryBuilder à modifier
     * @param string|null $statusFilter Le statut à filtrer
     */
    private function applyStatusFilter(QueryBuilder $qb, ?string $statusFilter): void
    {
        // Si pas de filtre ou filtre "all", ne rien faire
        if (!$statusFilter || $statusFilter === 'all') {
            return;
        }

        try {
            // Convertir la chaîne en enum OrderStatus
            $status = OrderStatus::from($statusFilter);

            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        } catch (\ValueError $e) {
            // Si le statut est invalide, on ignore le filtre
            // (évite les erreurs si l'utilisateur manipule l'URL)
        }
    }

    /**
     * Applique le filtre de recherche textuelle
     * Recherche dans : numéro de commande, prénom client, nom client, email client
     *
     * @param QueryBuilder $qb Le QueryBuilder à modifier
     * @param string|null $searchFilter Le terme de recherche
     */
    private function applySearchFilter(QueryBuilder $qb, ?string $searchFilter): void
    {
        if (!$searchFilter || trim($searchFilter) === '') {
            return;
        }

        // Recherche dans plusieurs champs avec LIKE
        $qb->andWhere(
            'o.orderNumber LIKE :search
            OR o.customerFirstname LIKE :search
            OR o.customerLastname LIKE :search
            OR o.customerEmail LIKE :search'
        )->setParameter('search', '%' . $searchFilter . '%');
    }

    /**
     * Applique le filtre de date
     * Permet de filtrer par : aujourd'hui, 7 derniers jours, 30 derniers jours
     *
     * @param QueryBuilder $qb Le QueryBuilder à modifier
     * @param string|null $dateFilter Le type de filtre de date
     */
    private function applyDateFilter(QueryBuilder $qb, ?string $dateFilter): void
    {
        if (!$dateFilter) {
            return;
        }

        switch ($dateFilter) {
            case 'today':
                // Commandes créées aujourd'hui
                $qb->andWhere('DATE(o.createdAt) = CURRENT_DATE()');
                break;

            case 'week':
                // Commandes des 7 derniers jours
                $qb->andWhere('o.createdAt >= :weekStart')
                    ->setParameter('weekStart', new \DateTimeImmutable('-7 days'));
                break;

            case 'month':
                // Commandes des 30 derniers jours
                $qb->andWhere('o.createdAt >= :monthStart')
                    ->setParameter('monthStart', new \DateTimeImmutable('-30 days'));
                break;

            // Si le filtre n'est pas reconnu, on l'ignore
        }
    }

    /**
     * Applique le tri sur les résultats
     *
     * @param QueryBuilder $qb Le QueryBuilder à modifier
     * @param string $sortBy Le champ sur lequel trier
     * @param string $sortOrder L'ordre de tri (ASC ou DESC)
     */
    private function applySorting(QueryBuilder $qb, string $sortBy, string $sortOrder): void
    {
        // Valider le champ de tri pour éviter les injections SQL
        if (!in_array($sortBy, self::VALID_SORT_FIELDS)) {
            $sortBy = 'createdAt'; // Valeur par défaut
        }

        // Valider l'ordre de tri
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy('o.' . $sortBy, $sortOrder);
    }
}