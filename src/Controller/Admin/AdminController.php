<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use App\Repository\CategoryRepository;
use App\Repository\AllergenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        UserRepository $userRepository
    ): Response
    {
        // Récupérer les statistiques
        $stats = [
            'totalRecipes' => $recipeRepository->count(),
            'totalCategories' => $categoryRepository->count(),
            'totalAllergens' => $allergenRepository->count(),
            'totalUsers' => $userRepository->count(),
        ];

        // Récupérer les dernières recettes créées avec leurs relations (optimisé)
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
