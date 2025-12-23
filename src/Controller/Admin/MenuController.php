<?php

namespace App\Controller\Admin;

use App\Entity\Menu;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use App\Service\MenuFileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/menus')]
final class MenuController extends AbstractController
{
    public function __construct(
        private MenuFileUploader $fileUploader
    ) {
    }
    #[Route('', name: 'app_admin_menus')]
    public function index(MenuRepository $menuRepository): Response
    {
        // Utiliser la méthode optimisée pour éviter le problème N+1
        $menus = $menuRepository->findAllWithRelations();

        return $this->render('admin/menus/index.html.twig', [
            'menus' => $menus,
        ]);
    }

    #[Route('/new', name: 'app_admin_menus_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le fichier uploadé
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // 1. Uploader le fichier
                $fileName = $this->fileUploader->upload($imageFile);

                // 2. Stocker l'URL dans l'entité
                $menu->setIllustration('/uploads/menu_illustrations/' . $fileName);
            }

            // Si pas de texte alternatif, utiliser le nom du menu
            if (!$menu->getTextAlt()) {
                $menu->setTextAlt($menu->getName());
            }

            // Récupérer les recettes sélectionnées dans chaque catégorie
            $entrees = $form->get('entrees')->getData();
            $plats = $form->get('plats')->getData();
            $fromages = $form->get('fromages')->getData();
            $desserts = $form->get('desserts')->getData();

            // Ajouter toutes les recettes au menu
            if ($entrees) {
                foreach ($entrees as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }
            if ($plats) {
                foreach ($plats as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }
            if ($fromages) {
                foreach ($fromages as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }
            if ($desserts) {
                foreach ($desserts as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }

            $em->persist($menu);
            $em->flush();

            $this->addFlash('success', 'Le menu "' . $menu->getName() . '" a été créé avec succès !');

            return $this->redirectToRoute('app_admin_menus');
        }

        return $this->render('admin/menus/form.html.twig', [
            'form' => $form,
            'menu' => $menu,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_menus_edit')]
    public function edit(Menu $menu, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MenuType::class, $menu, [
            'is_edit' => true
        ]);

        // Pré-remplir les champs de recettes par catégorie
        $currentRecipes = $menu->getRecipes();
        $entrees = [];
        $plats = [];
        $fromages = [];
        $desserts = [];

        foreach ($currentRecipes as $recipe) {
            $categoryName = $recipe->getCategory()?->getName();
            match ($categoryName) {
                'Entrée' => $entrees[] = $recipe,
                'Plat' => $plats[] = $recipe,
                'Fromage' => $fromages[] = $recipe,
                'Dessert' => $desserts[] = $recipe,
                default => null,
            };
        }

        $form->get('entrees')->setData($entrees);
        $form->get('plats')->setData($plats);
        $form->get('fromages')->setData($fromages);
        $form->get('desserts')->setData($desserts);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le nouveau fichier (peut être null)
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // Supprimer l'ancien fichier si il existe
                $oldIllustration = $menu->getIllustration();
                if ($oldIllustration) {
                    // Extraire le nom du fichier de l'URL
                    $oldFileName = basename($oldIllustration);
                    $this->fileUploader->remove($oldFileName);
                }

                // Uploader le nouveau fichier
                $fileName = $this->fileUploader->upload($imageFile);
                $menu->setIllustration('/uploads/menu_illustrations/' . $fileName);
            }

            // Si pas de texte alternatif, utiliser le nom du menu
            if (!$menu->getTextAlt()) {
                $menu->setTextAlt($menu->getName());
            }

            // Supprimer toutes les recettes existantes
            foreach ($menu->getRecipes() as $recipe) {
                $menu->removeRecipe($recipe);
            }

            // Récupérer les recettes sélectionnées dans chaque catégorie
            $entrees = $form->get('entrees')->getData();
            $plats = $form->get('plats')->getData();
            $fromages = $form->get('fromages')->getData();
            $desserts = $form->get('desserts')->getData();

            // Ajouter toutes les recettes au menu
            if ($entrees) {
                foreach ($entrees as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }
            if ($plats) {
                foreach ($plats as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }
            if ($fromages) {
                foreach ($fromages as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }
            if ($desserts) {
                foreach ($desserts as $recipe) {
                    $menu->addRecipe($recipe);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Le menu "' . $menu->getName() . '" a été modifié avec succès !');

            return $this->redirectToRoute('app_admin_menus');
        }

        return $this->render('admin/menus/form.html.twig', [
            'form' => $form,
            'menu' => $menu,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_menus_delete', methods: ['POST'])]
    public function delete(Menu $menu, EntityManagerInterface $em): Response
    {
        // Supprimer le fichier image associé
        $illustration = $menu->getIllustration();
        if ($illustration) {
            $fileName = basename($illustration);
            $this->fileUploader->remove($fileName);
        }

        $menuName = $menu->getName();
        $em->remove($menu);
        $em->flush();

        $this->addFlash('success', 'Le menu "' . $menuName . '" a été supprimé avec succès !');

        return $this->redirectToRoute('app_admin_menus');
    }
}