<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use App\Service\RecipeFileUploader; // ✅ Utilisez le service spécialisé !
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/recipes')]
final class RecipeController extends AbstractController
{
    // ✅ Injection du service spécialisé dans le constructeur
    public function __construct(
        private RecipeFileUploader $fileUploader
    ) {
    }

    #[Route('', name: 'app_admin_recipes')]
    public function index(RecipeRepository $recipeRepository): Response
    {
        // Récupérer TOUTES les recettes avec leurs relations
        $recipes = $recipeRepository->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.allergen', 'a')
            ->addSelect('c', 'a')
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/recipes/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/new', name: 'app_admin_recipes_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($recipe->getRecipeIllustrations()->toArray() as $illustration) {
                $imageFile = $illustration->getImageFile();

                if ($imageFile) {
                    // ✅ Utilisation de $this->fileUploader
                    $fileName = $this->fileUploader->upload($imageFile);
                    $illustration->setName($fileName);
                    $illustration->setUrl('/uploads/recipe_illustrations/' . $fileName);
                    $illustration->setRecipe($recipe);
                } else {
                    $recipe->removeRecipeIllustration($illustration);
                }
            }

            $em->persist($recipe);
            $em->flush();

            $this->addFlash('success', 'La recette a été créée avec succès !');

            return $this->redirectToRoute('app_admin_recipes');
        }

        return $this->render('admin/recipes/form.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_recipes_edit')]
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($recipe->getRecipeIllustrations() as $illustration) {
                $imageFile = $illustration->getImageFile();

                if ($imageFile) {
                    // Supprimer l'ancien fichier
                    if ($illustration->getName()) {
                        $this->fileUploader->remove($illustration->getName());
                    }

                    // ✅ Uploader le nouveau
                    $fileName = $this->fileUploader->upload($imageFile);
                    $illustration->setName($fileName);
                    $illustration->setUrl('/uploads/recipe_illustrations/' . $fileName);
                    $illustration->setRecipe($recipe);
                }
            }

            $em->flush();

            $this->addFlash('success', 'La recette a été modifiée avec succès !');

            return $this->redirectToRoute('app_admin_recipes');
        }

        return $this->render('admin/recipes/form.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_recipes_delete', methods: ['POST'])]
    public function delete(Recipe $recipe, EntityManagerInterface $em): Response
    {
        // ✅ Supprimer les illustrations associées
        foreach ($recipe->getRecipeIllustrations() as $illustration) {
            if ($illustration->getName()) {
                $this->fileUploader->remove($illustration->getName());
            }
        }

        $recipeName = $recipe->getTitle();
        $em->remove($recipe);
        $em->flush();

        $this->addFlash('success', 'La recette "' . $recipeName . '" a été supprimée avec succès !');

        return $this->redirectToRoute('app_admin_recipes');
    }
}
