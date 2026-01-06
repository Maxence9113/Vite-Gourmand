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
        $viewType = $request->query->get('view', 'theme'); // 'theme' ou 'menu'
        $themeName = $request->query->get('theme') ?: null;
        $menuName = $request->query->get('menu') ?: null;
        $period = $request->query->get('period', 'all'); // 'week', 'month', '3months', '6months', 'all', 'custom'
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        // Conversion des dates selon la période sélectionnée
        $startDate = null;
        $endDate = null;

        if ($period === 'custom') {
            if ($startDateStr) {
                try {
                    $startDate = new \DateTime($startDateStr);
                    $startDate->setTime(0, 0, 0);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Format de date de début invalide');
                }
            }
            if ($endDateStr) {
                try {
                    $endDate = new \DateTime($endDateStr);
                    $endDate->setTime(23, 59, 59);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Format de date de fin invalide');
                }
            }
        } else {
            $endDate = new \DateTime();
            $endDate->setTime(23, 59, 59);

            switch ($period) {
                case 'week':
                    $startDate = (new \DateTime())->modify('-7 days')->setTime(0, 0, 0);
                    break;
                case 'month':
                    $startDate = (new \DateTime())->modify('-30 days')->setTime(0, 0, 0);
                    break;
                case '3months':
                    $startDate = (new \DateTime())->modify('-90 days')->setTime(0, 0, 0);
                    break;
                case '6months':
                    $startDate = (new \DateTime())->modify('-180 days')->setTime(0, 0, 0);
                    break;
                case 'all':
                default:
                    $startDate = null;
                    $endDate = null;
                    break;
            }
        }

        // ==================== RÉCUPÉRATION DES DONNÉES ====================

        // Statistiques globales (KPIs)
        $globalStats = $this->orderStatsRepository->getGlobalStats($startDate, $endDate);

        // Données selon le type de vue
        if ($viewType === 'theme') {
            // Vue par thème (par défaut)
            $orderCountData = $this->orderStatsRepository->getOrderCountByTheme($themeName, $startDate, $endDate);
            $revenueData = $this->orderStatsRepository->getRevenueByTheme($themeName, $startDate, $endDate);

            $chartLabels = array_map(fn($item) => $item['themeName'], $orderCountData);
            $chartData = array_map(fn($item) => $item['count'], $orderCountData);
        } else {
            // Vue par menu (avec filtre optionnel par thème)
            $orderCountData = $this->orderStatsRepository->getOrderCountByMenu($menuName, $startDate, $endDate, $themeName);
            $revenueData = $this->orderStatsRepository->getRevenueByMenu($menuName, $startDate, $endDate, $themeName);

            $chartLabels = array_map(fn($item) => $item['menuName'], $orderCountData);
            $chartData = array_map(fn($item) => $item['count'], $orderCountData);
        }

        // Listes pour les filtres
        $availableThemes = $this->orderStatsRepository->getDistinctThemeNames();
        $availableMenus = $this->orderStatsRepository->getDistinctMenuNames();

        // Générer un mapping thème => couleur dynamiquement
        $themeColorMap = $this->generateThemeColorMap($availableThemes);

        // Appliquer les couleurs aux données du graphique
        if ($viewType === 'theme') {
            // En vue thème, attribuer la couleur de chaque thème
            $chartBackgroundColors = array_map(fn($item) => $themeColorMap[$item['themeName']]['background'], $orderCountData);
            $chartBorderColors = array_map(fn($item) => $themeColorMap[$item['themeName']]['border'], $orderCountData);
        } else {
            // En vue menu, récupérer le thème de chaque menu pour lui attribuer sa couleur
            $chartBackgroundColors = [];
            $chartBorderColors = [];
            foreach ($orderCountData as $item) {
                $stats = $this->orderStatsRepository->findWithFilters($item['menuName']);
                if (!empty($stats)) {
                    $menuTheme = $stats[0]->getThemeName();
                    $chartBackgroundColors[] = $themeColorMap[$menuTheme]['background'] ?? 'rgba(201, 203, 207, 0.6)';
                    $chartBorderColors[] = $themeColorMap[$menuTheme]['border'] ?? 'rgba(201, 203, 207, 1)';
                } else {
                    $chartBackgroundColors[] = 'rgba(201, 203, 207, 0.6)';
                    $chartBorderColors[] = 'rgba(201, 203, 207, 1)';
                }
            }
        }

        // Calculer les KPIs supplémentaires
        $topTheme = !empty($orderCountData) ? $orderCountData[0] : null;
        $topMenu = !empty($orderCountData) ? $orderCountData[0] : null;

        return $this->render('admin/order_stats/index.html.twig', [
            'viewType' => $viewType,
            'orderCountData' => $orderCountData,
            'revenueData' => $revenueData,
            'globalStats' => $globalStats,
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
            'chartBackgroundColors' => json_encode($chartBackgroundColors),
            'chartBorderColors' => json_encode($chartBorderColors),
            // Tableaux bruts pour utilisation dans Twig
            'chartBackgroundColorsArray' => $chartBackgroundColors,
            'chartBorderColorsArray' => $chartBorderColors,
            // KPIs
            'topTheme' => $topTheme,
            'topMenu' => $topMenu,
            // Filtres disponibles
            'availableThemes' => $availableThemes,
            'availableMenus' => $availableMenus,
            // Filtres actuels
            'currentTheme' => $themeName,
            'currentMenu' => $menuName,
            'currentPeriod' => $period,
            'currentStartDate' => $startDateStr,
            'currentEndDate' => $endDateStr,
        ]);
    }

    /**
     * Génère dynamiquement des couleurs distinctes pour chaque thème
     *
     * Utilise le Golden Ratio pour espacer uniformément les teintes sur le cercle chromatique.
     * Garantit des couleurs bien distinctes visuellement.
     * Chaque thème aura toujours la même couleur grâce au tri alphabétique.
     *
     * @param array $themeNames Liste des noms de thèmes
     * @return array Mapping [themeName => ['background' => '...', 'border' => '...']]
     */
    private function generateThemeColorMap(array $themeNames): array
    {
        $colorMap = [];

        // Trier les thèmes par ordre alphabétique pour garantir la cohérence
        sort($themeNames);

        // Golden Ratio pour espacer les couleurs uniformément
        $goldenRatio = 0.618033988749895;
        $hue = 0; // Commencer à 0 degré (rouge)

        foreach ($themeNames as $index => $themeName) {
            // Utiliser le Golden Ratio pour espacer les teintes
            // Chaque thème sera espacé de ~222 degrés du précédent
            $hue = fmod($hue + ($goldenRatio * 360), 360);

            // Alterner légèrement la saturation et la luminosité pour encore plus de distinction
            $saturation = 65 + (($index % 3) * 10); // 65%, 75%, 85%
            $lightness = 55 + (($index % 2) * 10);   // 55%, 65%

            // Conversion HSL vers RGB
            $rgb = $this->hslToRgb($hue, $saturation, $lightness);

            // Créer les couleurs avec transparence pour le fond et opaque pour la bordure
            $colorMap[$themeName] = [
                'background' => sprintf('rgba(%d, %d, %d, 0.6)', $rgb[0], $rgb[1], $rgb[2]),
                'border' => sprintf('rgba(%d, %d, %d, 1)', $rgb[0], $rgb[1], $rgb[2])
            ];
        }

        return $colorMap;
    }

    /**
     * Convertit une couleur HSL en RGB
     *
     * @param float $h Teinte (0-360)
     * @param float $s Saturation (0-100)
     * @param float $l Luminosité (0-100)
     * @return array [R, G, B] avec valeurs entre 0 et 255
     */
    private function hslToRgb(float $h, float $s, float $l): array
    {
        $h = $h / 360;
        $s = $s / 100;
        $l = $l / 100;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hueToRgb($p, $q, $h + 1/3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1/3);
        }

        return [
            (int)round($r * 255),
            (int)round($g * 255),
            (int)round($b * 255)
        ];
    }

    /**
     * Fonction helper pour la conversion HSL vers RGB
     */
    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
}