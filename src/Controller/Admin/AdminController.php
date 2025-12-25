<?php

namespace App\Controller\Admin;

use App\Enum\OrderStatus;
use App\Repository\RecipeRepository;
use App\Repository\CategoryRepository;
use App\Repository\AllergenRepository;
use App\Repository\UserRepository;
use App\Repository\ThemeRepository;
use App\Repository\DietetaryRepository;
use App\Repository\MenuRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function index(
        RecipeRepository $recipeRepository,
        CategoryRepository $categoryRepository,
        AllergenRepository $allergenRepository,
        UserRepository $userRepository,
        ThemeRepository $themeRepository,
        DietetaryRepository $dietetaryRepository,
        MenuRepository $menuRepository,
        OrderRepository $orderRepository
    ): Response
    {
        // Compter les commandes en cours (ni COMPLETED ni CANCELLED)
        $pendingOrders = $orderRepository->createQueryBuilder('o')
            ->where('o.status != :completed')
            ->andWhere('o.status != :cancelled')
            ->setParameter('completed', OrderStatus::COMPLETED)
            ->setParameter('cancelled', OrderStatus::CANCELLED)
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Récupérer les statistiques
        $stats = [
            'totalRecipes' => $recipeRepository->count(),
            'totalCategories' => $categoryRepository->count(),
            'totalAllergens' => $allergenRepository->count(),
            'totalUsers' => $userRepository->count(),
            'totalThemes' => $themeRepository->count(),
            'totalDietary' => $dietetaryRepository->count(),
            'totalMenus' => $menuRepository->count(),
            'pendingOrders' => $pendingOrders,
        ];

        // Récupérer les dernières recettes créées
        $latestRecipes = $recipeRepository->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->addSelect('c')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'latestRecipes' => $latestRecipes,
        ]);
    }
}
