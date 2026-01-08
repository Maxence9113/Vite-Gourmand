<?php

namespace App\Controller\Public;

use App\Repository\AllergenRepository;
use App\Repository\DietetaryRepository;
use App\Repository\MenuRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/menus')]
final class MenuController extends AbstractController
{
    #[Route('', name: 'app_menu_catalog')]
    public function catalog(
        Request $request,
        MenuRepository $menuRepository,
        ThemeRepository $themeRepository,
        DietetaryRepository $dietetaryRepository,
        AllergenRepository $allergenRepository
    ): Response {
        $themeId = $request->query->get('theme');
        $dietetaryIds = $request->query->all('dietetary');
        $allergenIds = $request->query->all('allergen');
        $priceMin = $request->query->get('price_min') ? (float) $request->query->get('price_min') : null;
        $priceMax = $request->query->get('price_max') ? (float) $request->query->get('price_max') : null;
        $nbPersonMin = $request->query->get('nb_person_min') ? (int) $request->query->get('nb_person_min') : null;

        $menus = $menuRepository->findByFilters(
            themeId: $themeId,
            dietetaryIds: $dietetaryIds,
            allergenIds: $allergenIds,
            priceMin: $priceMin,
            priceMax: $priceMax,
            nbPersonMin: $nbPersonMin
        );

        $themes = $themeRepository->findAll();
        $dietetaries = $dietetaryRepository->findAll();
        $allergens = $allergenRepository->findAll();

        return $this->render('menu/catalog.html.twig', [
            'menus' => $menus,
            'themes' => $themes,
            'dietetaries' => $dietetaries,
            'allergens' => $allergens,
            'currentFilters' => [
                'theme' => $themeId,
                'dietetary' => $dietetaryIds,
                'allergen' => $allergenIds,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'nb_person_min' => $nbPersonMin,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_menu_show', requirements: ['id' => '\d+'])]
    public function show(int $id, MenuRepository $menuRepository): Response
    {
        $menu = $menuRepository->findOneWithRelations($id);

        if (!$menu) {
            throw $this->createNotFoundException('Menu non trouvé');
        }

        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }
}