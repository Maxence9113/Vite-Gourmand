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

    #[Route('/recipes', name: 'app_admin_recipes')]
    public function recipes(RecipeRepository $recipeRepository): Response
    {
        // Récupérer TOUTES les recettes avec leurs relations (JOIN) pour éviter le problème N+1
        $recipes = $recipeRepository->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.allergen', 'a')
            ->addSelect('c', 'a')  // Charger les relations en même temps
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/recipes/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/recipes/new', name: 'app_admin_recipes_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // Créer une nouvelle recette vide
        $recipe = new Recipe();

        // Créer le formulaire lié à cette recette
        $form = $this->createForm(RecipeType::class, $recipe);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder en base de données
            $em->persist($recipe);
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'La recette a été créée avec succès !');

            // Rediriger vers la liste des recettes
            return $this->redirectToRoute('app_admin_recipes');
        }

        return $this->render('admin/recipes/form.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
            'isEdit' => false,
        ]);
    }

    #[Route('/recipes/{id}/edit', name: 'app_admin_recipes_edit')]
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $em): Response
    {
        // Créer le formulaire lié à la recette existante
        $form = $this->createForm(RecipeType::class, $recipe);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder les modifications
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'La recette a été modifiée avec succès !');

            // Rediriger vers la liste des recettes
            return $this->redirectToRoute('app_admin_recipes');
        }

        return $this->render('admin/recipes/form.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
            'isEdit' => true,
        ]);
    }

    #[Route('/recipes/{id}/delete', name: 'app_admin_recipes_delete', methods: ['POST'])]
    public function delete(Recipe $recipe, EntityManagerInterface $em): Response
    {
        // Supprimer la recette de la base de données
        $em->remove($recipe);
        $em->flush();

        // Message flash de succès
        $this->addFlash('success', 'La recette "' . $recipe->getTitle() . '" a été supprimée avec succès !');

        // Rediriger vers la liste des recettes
        return $this->redirectToRoute('app_admin_recipes');
    }


}
