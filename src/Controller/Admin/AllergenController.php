<?php

namespace App\Controller\Admin;

use App\Entity\Allergen;
use App\Form\AllergenType;
use App\Repository\AllergenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/allergens')]
final class AllergenController extends AbstractController
{
    #[Route('', name: 'app_admin_allergens')]
    public function index(AllergenRepository $allergenRepository): Response
    {
        // Utiliser la méthode optimisée pour éviter le problème N+1
        $allergens = $allergenRepository->findAllWithRecipeCount();

        return $this->render('admin/allergens/index.html.twig', [
            'allergens' => $allergens,
        ]);
    }

    #[Route('/new', name: 'app_admin_allergens_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $allergen = new Allergen();
        $form = $this->createForm(AllergenType::class, $allergen);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($allergen);
            $em->flush();

            $this->addFlash('success', 'L\'allergène "' . $allergen->getName() . '" a été créé avec succès !');

            return $this->redirectToRoute('app_admin_allergens');
        }

        return $this->render('admin/allergens/form.html.twig', [
            'form' => $form,
            'allergen' => $allergen,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_allergens_edit')]
    public function edit(Allergen $allergen, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AllergenType::class, $allergen);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'L\'allergène "' . $allergen->getName() . '" a été modifié avec succès !');

            return $this->redirectToRoute('app_admin_allergens');
        }

        return $this->render('admin/allergens/form.html.twig', [
            'form' => $form,
            'allergen' => $allergen,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_allergens_delete', methods: ['POST'])]
    public function delete(Allergen $allergen, EntityManagerInterface $em): Response
    {
        $allergenName = $allergen->getName();

        // Vérifier si l'allergène est utilisé par des recettes
        if ($allergen->getRecipes()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer l\'allergène "' . $allergenName . '" car il est utilisé par ' . $allergen->getRecipes()->count() . ' recette(s).');
            return $this->redirectToRoute('app_admin_allergens');
        }

        $em->remove($allergen);
        $em->flush();

        $this->addFlash('success', 'L\'allergène "' . $allergenName . '" a été supprimé avec succès !');

        return $this->redirectToRoute('app_admin_allergens');
    }
}
