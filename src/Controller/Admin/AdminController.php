<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use App\Repository\CategoryRepository;
use App\Repository\AllergenRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
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
    public function new(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        // Créer une nouvelle recette vide
        $recipe = new Recipe();

        // Créer le formulaire lié à cette recette
        $form = $this->createForm(RecipeType::class, $recipe);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Parcourir chaque illustration et traiter les uploads
            // On utilise toArray() pour créer une copie et pouvoir supprimer pendant l'itération
            foreach ($recipe->getRecipeIllustrations()->toArray() as $illustration) {
                // Récupérer le fichier uploadé depuis l'objet RecipeIllustration
                $imageFile = $illustration->getImageFile();

                // Si un fichier a été uploadé
                if ($imageFile) {
                    // 1. Uploader le fichier et récupérer le nom généré
                    $fileName = $fileUploader->upload($imageFile);

                    // 2. Stocker le nom du fichier dans la base de données
                    $illustration->setName($fileName);

                    // 3. Créer l'URL relative pour afficher l'image dans les templates
                    // Ex: "/uploads/recipe_illustrations/mon-plat-507f1f77bcf86cd799439011.jpg"
                    $illustration->setUrl('/uploads/recipe_illustrations/' . $fileName);

                    // 4. Associer l'illustration à la recette (si ce n'est pas déjà fait)
                    $illustration->setRecipe($recipe);
                } else {
                    // Si aucun fichier n'a été uploadé, on retire cette illustration de la collection
                    // Cela évite d'avoir des enregistrements vides en base de données
                    $recipe->removeRecipeIllustration($illustration);
                }
            }

            // Sauvegarder la recette et ses illustrations en base de données
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
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        // Créer le formulaire lié à la recette existante
        $form = $this->createForm(RecipeType::class, $recipe);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Parcourir chaque illustration (nouvelles et existantes)
            foreach ($recipe->getRecipeIllustrations() as $illustration) {
                // Récupérer le fichier uploadé (sera null si aucun nouveau fichier)
                $imageFile = $illustration->getImageFile();

                // Si un nouveau fichier a été uploadé
                if ($imageFile) {
                    // 1. Supprimer l'ancien fichier s'il existe
                    if ($illustration->getName()) {
                        $fileUploader->remove($illustration->getName());
                    }

                    // 2. Uploader le nouveau fichier
                    $fileName = $fileUploader->upload($imageFile);

                    // 3. Mettre à jour le nom et l'URL
                    $illustration->setName($fileName);
                    $illustration->setUrl('/uploads/recipe_illustrations/' . $fileName);

                    // 4. S'assurer que l'illustration est bien liée à la recette
                    $illustration->setRecipe($recipe);
                }
            }

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
