<?php

namespace App\Controller\Api;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API pour le filtrage dynamique des menus
 */
#[Route('/api/menus')]
final class MenuApiController extends AbstractController
{
    /**
     * Filtre les menus selon les critères fournis et retourne le résultat en JSON
     *
     * Cette API permet un filtrage dynamique sans rechargement de page (AJAX)
     *
     * @param Request $request
     * @param MenuRepository $menuRepository
     * @return JsonResponse Liste des menus filtrés avec leurs informations
     */
    #[Route('/filter', name: 'api_menu_filter', methods: ['GET'])]
    public function filter(
        Request $request,
        MenuRepository $menuRepository
    ): JsonResponse {
        try {
            // Récupération des paramètres de filtrage depuis la query string
            $themeId = $request->query->get('theme');
            $dietetaryIds = $request->query->all('dietetary');
            $allergenIds = $request->query->all('allergen');

            // Conversion des prix en centimes (la base de données stocke en centimes)
            $priceMin = $request->query->get('price_min')
                ? (float) $request->query->get('price_min') * 100
                : null;
            $priceMax = $request->query->get('price_max')
                ? (float) $request->query->get('price_max') * 100
                : null;
            $nbPersonMin = $request->query->get('nb_person_min')
                ? (int) $request->query->get('nb_person_min')
                : null;

            // Appel au repository pour récupérer les menus filtrés
            $menus = $menuRepository->findByFilters(
                themeId: $themeId,
                dietetaryIds: $dietetaryIds,
                allergenIds: $allergenIds,
                priceMin: $priceMin,
                priceMax: $priceMax,
                nbPersonMin: $nbPersonMin
            );

            // Transformation des entités Menu en tableau JSON
            $menusData = array_map(function ($menu) {
                $dietetaries = [];
                foreach ($menu->getDietetary() as $dietetary) {
                    $dietetaries[] = [
                        'id' => $dietetary->getId(),
                        'name' => $dietetary->getName(),
                    ];
                }

                return [
                    'id' => $menu->getId(),
                    'name' => $menu->getName(),
                    'description' => $menu->getDescription(),
                    'illustration' => $menu->getIllustration(),
                    'textAlt' => $menu->getTextAlt(),
                    'pricePerPerson' => $menu->getPricePerPerson(), // en centimes
                    'nbPersonMin' => $menu->getNbPersonMin(),
                    'theme' => [
                        'id' => $menu->getTheme()->getId(),
                        'name' => $menu->getTheme()->getName(),
                    ],
                    'dietetaries' => $dietetaries,
                ];
            }, $menus);

            return new JsonResponse([
                'success' => true,
                'menus' => $menusData,
                'count' => count($menusData),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors du filtrage des menus : ' . $e->getMessage()
            ], 500);
        }
    }
}
