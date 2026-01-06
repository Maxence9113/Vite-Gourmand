<?php

namespace App\Controller\Admin;

use App\Repository\OrderStatsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour afficher les statistiques de commandes depuis MongoDB
 *
 * Ce contrôleur permet aux administrateurs de :
 * - Visualiser le nombre de commandes par menu (avec graphique)
 * - Consulter le chiffre d'affaires par menu
 * - Filtrer les statistiques par menu et par période
 * - Voir les statistiques globales
 */
#[Route('/admin/stats')]
final class OrderStatsController extends AbstractController
{
    public function __construct(
        private OrderStatsRepository $orderStatsRepository
    ) {
    }

    /**
     * Affiche la page des statistiques avec graphiques
     *
     * @param Request $request Pour récupérer les paramètres de filtrage
     * @return Response
     */
    #[Route('', name: 'app_admin_order_stats')]
    public function index(Request $request): Response
    {
        // ==================== RÉCUPÉRATION DES FILTRES ====================
        $menuName = $request->query->get('menu');
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        // Conversion des dates string en DateTime
        $startDate = null;
        $endDate = null;

        if ($startDateStr) {
            try {
                $startDate = new \DateTime($startDateStr);
                $startDate->setTime(0, 0, 0); // Début de journée
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Format de date de début invalide');
            }
        }

        if ($endDateStr) {
            try {
                $endDate = new \DateTime($endDateStr);
                $endDate->setTime(23, 59, 59); // Fin de journée
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Format de date de fin invalide');
            }
        }

        // ==================== RÉCUPÉRATION DES DONNÉES ====================

        // 1. Nombre de commandes par menu (pour le graphique)
        $orderCountByMenu = $this->orderStatsRepository->getOrderCountByMenu(
            $menuName,
            $startDate,
            $endDate
        );

        // 2. Chiffre d'affaires par menu
        $revenueByMenu = $this->orderStatsRepository->getRevenueByMenu(
            $menuName,
            $startDate,
            $endDate
        );

        // 3. Statistiques globales
        $globalStats = $this->orderStatsRepository->getGlobalStats(
            $startDate,
            $endDate
        );

        // 4. Liste des menus disponibles (pour le select de filtrage)
        $availableMenus = $this->orderStatsRepository->getDistinctMenuNames();

        // ==================== PRÉPARATION DES DONNÉES POUR LE GRAPHIQUE ====================
        // Chart.js attend deux tableaux : labels et data
        $chartLabels = array_map(fn($item) => $item['menuName'], $orderCountByMenu);
        $chartData = array_map(fn($item) => $item['count'], $orderCountByMenu);

        return $this->render('admin/order_stats/index.html.twig', [
            'orderCountByMenu' => $orderCountByMenu,
            'revenueByMenu' => $revenueByMenu,
            'globalStats' => $globalStats,
            'availableMenus' => $availableMenus,
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
            // Filtres actuels (pour les réafficher dans le formulaire)
            'currentMenu' => $menuName,
            'currentStartDate' => $startDateStr,
            'currentEndDate' => $endDateStr,
        ]);
    }
}