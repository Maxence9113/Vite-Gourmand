<?php

namespace App\Repository;

use App\Document\OrderStats;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Repository pour récupérer les statistiques de commandes depuis MongoDB
 *
 * Ce repository centralise toutes les requêtes MongoDB pour les statistiques.
 * Il permet de :
 * - Récupérer les commandes par menu avec agrégation
 * - Calculer le chiffre d'affaires par menu
 * - Filtrer les statistiques par période et par menu
 *
 * Pourquoi un repository dédié ?
 * - Sépare la logique de requête de la logique métier
 * - Facilite les tests unitaires
 * - Centralise les requêtes MongoDB complexes
 */
class OrderStatsRepository
{
    private DocumentRepository $repository;

    public function __construct(private DocumentManager $documentManager)
    {
        $this->repository = $documentManager->getRepository(OrderStats::class);
    }

    /**
     * Récupère le nombre de commandes par menu
     *
     * Cette méthode utilise l'agrégation MongoDB pour grouper les commandes par menu
     * et compter le nombre de commandes pour chaque menu.
     *
     * @param string|null $menuName Filtre optionnel par nom de menu
     * @param \DateTime|null $startDate Date de début du filtre (optionnel)
     * @param \DateTime|null $endDate Date de fin du filtre (optionnel)
     * @return array Format: [['menuName' => 'Menu 1', 'count' => 10], ...]
     */
    public function getOrderCountByMenu(
        ?string $menuName = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $themeName = null
    ): array {
        $builder = $this->documentManager
            ->createAggregationBuilder(OrderStats::class);

        // ==================== ÉTAPE 1 : FILTRES ====================
        // Filtre par nom de menu si fourni
        if ($menuName !== null) {
            $builder->match()->field('menuName')->equals($menuName);
        }

        // Filtre par thème si fourni
        if ($themeName !== null) {
            $builder->match()->field('themeName')->equals($themeName);
        }

        // Filtre par période si fournie
        if ($startDate !== null) {
            $builder->match()->field('orderDate')->gte($startDate);
        }
        if ($endDate !== null) {
            $builder->match()->field('orderDate')->lte($endDate);
        }

        // ==================== ÉTAPE 2 : GROUPEMENT ====================
        // Grouper par menuName et compter les commandes
        $builder
            ->group()
            ->field('_id')->expression('$menuName')
            ->field('count')->sum(1);

        // ==================== ÉTAPE 3 : TRI ====================
        // Trier par nombre de commandes décroissant
        $builder->sort(['count' => -1]);

        // ==================== ÉTAPE 4 : PROJECTION ====================
        // Reformater les résultats pour avoir menuName au lieu de _id
        $builder
            ->project()
            ->excludeFields(['_id'])
            ->field('menuName')->expression('$_id')
            ->field('count')->expression('$count');

        // Exécution de l'agrégation
        $result = $builder->hydrate(false)->getAggregation()->getIterator();

        return iterator_to_array($result);
    }

    /**
     * Calcule le chiffre d'affaires par menu
     *
     * Cette méthode agrège les prix totaux par menu pour calculer le CA.
     *
     * @param string|null $menuName Filtre optionnel par nom de menu
     * @param \DateTime|null $startDate Date de début du filtre (optionnel)
     * @param \DateTime|null $endDate Date de fin du filtre (optionnel)
     * @return array Format: [['menuName' => 'Menu 1', 'totalRevenue' => 1250.50], ...]
     */
    public function getRevenueByMenu(
        ?string $menuName = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $themeName = null
    ): array {
        $builder = $this->documentManager
            ->createAggregationBuilder(OrderStats::class);

        // ==================== ÉTAPE 1 : FILTRES ====================
        // Filtre par nom de menu si fourni
        if ($menuName !== null) {
            $builder->match()->field('menuName')->equals($menuName);
        }

        // Filtre par thème si fourni
        if ($themeName !== null) {
            $builder->match()->field('themeName')->equals($themeName);
        }

        // Filtre par période si fournie
        if ($startDate !== null) {
            $builder->match()->field('orderDate')->gte($startDate);
        }
        if ($endDate !== null) {
            $builder->match()->field('orderDate')->lte($endDate);
        }

        // ==================== ÉTAPE 2 : GROUPEMENT ====================
        // Grouper par menuName et sommer les prix totaux
        $builder
            ->group()
            ->field('_id')->expression('$menuName')
            ->field('themeName')->first('$themeName')
            ->field('totalRevenue')->sum('$totalPrice');

        // ==================== ÉTAPE 3 : TRI ====================
        // Trier par chiffre d'affaires décroissant
        $builder->sort(['totalRevenue' => -1]);

        // ==================== ÉTAPE 4 : PROJECTION ====================
        // Reformater les résultats pour avoir menuName au lieu de _id
        $builder
            ->project()
            ->excludeFields(['_id'])
            ->field('menuName')->expression('$_id')
            ->field('themeName')->expression('$themeName')
            ->field('totalRevenue')->expression('$totalRevenue');

        // Exécution de l'agrégation
        $result = $builder->hydrate(false)->getAggregation()->getIterator();

        return iterator_to_array($result);
    }

    /**
     * Récupère toutes les statistiques de commandes avec filtres optionnels
     *
     * Utile pour afficher un tableau détaillé dans l'admin
     *
     * @param string|null $menuName Filtre optionnel par nom de menu
     * @param \DateTime|null $startDate Date de début du filtre (optionnel)
     * @param \DateTime|null $endDate Date de fin du filtre (optionnel)
     * @return OrderStats[]
     */
    public function findWithFilters(
        ?string $menuName = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $queryBuilder = $this->repository->createQueryBuilder();

        // Filtre par nom de menu
        if ($menuName !== null) {
            $queryBuilder->field('menuName')->equals($menuName);
        }

        // Filtre par période
        if ($startDate !== null) {
            $queryBuilder->field('orderDate')->gte($startDate);
        }
        if ($endDate !== null) {
            $queryBuilder->field('orderDate')->lte($endDate);
        }

        // Tri par date de commande décroissante
        $queryBuilder->sort('orderDate', 'DESC');

        return $queryBuilder->getQuery()->execute()->toArray();
    }

    /**
     * Calcule les statistiques globales
     *
     * Retourne un résumé avec :
     * - Nombre total de commandes
     * - Chiffre d'affaires total
     * - Nombre moyen de personnes par commande
     *
     * @param \DateTime|null $startDate Date de début du filtre (optionnel)
     * @param \DateTime|null $endDate Date de fin du filtre (optionnel)
     * @return array
     */
    public function getGlobalStats(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $builder = $this->documentManager
            ->createAggregationBuilder(OrderStats::class);

        // Filtres par période
        if ($startDate !== null) {
            $builder->match()->field('orderDate')->gte($startDate);
        }
        if ($endDate !== null) {
            $builder->match()->field('orderDate')->lte($endDate);
        }

        // Agrégation globale (sans grouper par _id, on groupe tout ensemble)
        $builder
            ->group()
            ->field('_id')->expression(null)
            ->field('totalOrders')->sum(1)
            ->field('totalRevenue')->sum('$totalPrice')
            ->field('avgPeoplePerOrder')->avg('$numberOfPeople');

        // Exécution
        $result = $builder->hydrate(false)->getAggregation()->getIterator();
        $stats = iterator_to_array($result);

        // Si aucune commande, retourner des valeurs par défaut
        if (empty($stats)) {
            return [
                'totalOrders' => 0,
                'totalRevenue' => 0,
                'avgPeoplePerOrder' => 0
            ];
        }

        // Récupérer le premier résultat (et seul résultat car groupement global)
        $firstStat = reset($stats);
        return $firstStat !== false ? $firstStat : [
            'totalOrders' => 0,
            'totalRevenue' => 0,
            'avgPeoplePerOrder' => 0
        ];
    }

    /**
     * Récupère la liste des noms de menus distincts
     *
     * Utile pour alimenter le select de filtrage dans l'interface admin
     *
     * @return array Liste des noms de menus
     */
    public function getDistinctMenuNames(): array
    {
        $builder = $this->documentManager
            ->createAggregationBuilder(OrderStats::class);

        // Grouper par menuName pour obtenir les valeurs distinctes
        $builder
            ->group()
            ->field('_id')->expression('$menuName');

        // Trier par nom de menu
        $builder->sort(['_id' => 1]);

        // Exécution
        $result = $builder->hydrate(false)->getAggregation()->getIterator();
        $menus = iterator_to_array($result);

        // Extraire juste les noms de menu (retirer les _id)
        return array_map(fn($item) => $item['_id'], $menus);
    }

    /**
     * Récupère la liste des noms de thèmes distincts
     *
     * @return array Liste des noms de thèmes
     */
    public function getDistinctThemeNames(): array
    {
        $builder = $this->documentManager
            ->createAggregationBuilder(OrderStats::class);

        $builder
            ->group()
            ->field('_id')->expression('$themeName');

        $builder->sort(['_id' => 1]);

        $result = $builder->hydrate(false)->getAggregation()->getIterator();
        $themes = iterator_to_array($result);

        return array_map(fn($item) => $item['_id'], $themes);
    }

    /**
     * Récupère le nombre de commandes par thème (pour le graphique principal)
     *
     * @param string|null $themeName Filtrer par thème
     * @param \DateTime|null $startDate Date de début
     * @param \DateTime|null $endDate Date de fin
     * @return array Tableau avec themeName et count
     */
    public function getOrderCountByTheme(
        ?string $themeName = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $builder = $this->documentManager
            ->createAggregationBuilder(OrderStats::class);

        // Filtre par période
        if ($startDate || $endDate) {
            $matchConditions = [];
            if ($startDate) {
                $matchConditions['orderDate']['$gte'] = $startDate;
            }
            if ($endDate) {
                $matchConditions['orderDate']['$lte'] = $endDate;
            }
            if (!empty($matchConditions)) {
                $builder->match()->field('orderDate')->range($matchConditions['orderDate']['$gte'] ?? null, $matchConditions['orderDate']['$lte'] ?? null);
            }
        }

        // Filtre par thème si spécifié
        if ($themeName) {
            $builder->match()->field('themeName')->equals($themeName);
        }

        // Grouper par themeName et compter
        $builder
            ->group()
            ->field('_id')->expression('$themeName')
            ->field('count')->sum(1);

        // Trier par nombre de commandes décroissant
        $builder->sort(['count' => -1]);

        $result = $builder->hydrate(false)->getAggregation()->getIterator();
        $stats = iterator_to_array($result);

        // Reformater pour avoir themeName et count
        return array_map(function ($item) {
            return [
                'themeName' => $item['_id'],
                'count' => $item['count']
            ];
        }, $stats);
    }

    /**
     * Récupère le chiffre d'affaires par thème
     *
     * @param string|null $themeName Filtrer par thème
     * @param \DateTime|null $startDate Date de début
     * @param \DateTime|null $endDate Date de fin
     * @return array Tableau avec themeName et totalRevenue
     */
    public function getRevenueByTheme(
        ?string $themeName = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $builder = $this->documentManager
            ->createAggregationBuilder(OrderStats::class);

        // Filtre par période
        if ($startDate || $endDate) {
            $matchConditions = [];
            if ($startDate) {
                $matchConditions['orderDate']['$gte'] = $startDate;
            }
            if ($endDate) {
                $matchConditions['orderDate']['$lte'] = $endDate;
            }
            if (!empty($matchConditions)) {
                $builder->match()->field('orderDate')->range($matchConditions['orderDate']['$gte'] ?? null, $matchConditions['orderDate']['$lte'] ?? null);
            }
        }

        // Filtre par thème si spécifié
        if ($themeName) {
            $builder->match()->field('themeName')->equals($themeName);
        }

        // Grouper par themeName et sommer les prix
        $builder
            ->group()
            ->field('_id')->expression('$themeName')
            ->field('totalRevenue')->sum('$totalPrice');

        // Trier par CA décroissant
        $builder->sort(['totalRevenue' => -1]);

        $result = $builder->hydrate(false)->getAggregation()->getIterator();
        $stats = iterator_to_array($result);

        return array_map(function ($item) {
            return [
                'themeName' => $item['_id'],
                'totalRevenue' => round($item['totalRevenue'], 2)
            ];
        }, $stats);
    }
}
