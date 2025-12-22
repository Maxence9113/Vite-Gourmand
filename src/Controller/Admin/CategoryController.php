<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories')]
final class CategoryController extends AbstractController
{
    #[Route('', name: 'app_admin_categories')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Utiliser la méthode optimisée pour éviter le problème N+1
        $categories = $categoryRepository->findAllWithRecipeCount();

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_admin_categories_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'La catégorie "' . $category->getName() . '" a été créée avec succès !');

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'form' => $form,
            'category' => $category,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_categories_edit')]
    public function edit(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'La catégorie "' . $category->getName() . '" a été modifiée avec succès !');

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'form' => $form,
            'category' => $category,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_categories_delete', methods: ['POST'])]
    public function delete(Category $category, EntityManagerInterface $em): Response
    {
        $categoryName = $category->getName();

        // Vérifier si la catégorie est utilisée par des recettes
        if ($category->getRecipes()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer la catégorie "' . $categoryName . '" car elle est utilisée par ' . $category->getRecipes()->count() . ' recette(s).');
            return $this->redirectToRoute('app_admin_categories');
        }

        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'La catégorie "' . $categoryName . '" a été supprimée avec succès !');

        return $this->redirectToRoute('app_admin_categories');
    }
}