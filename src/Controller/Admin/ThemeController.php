<?php

namespace App\Controller\Admin;

use App\Entity\Theme;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/themes')]
final class ThemeController extends AbstractController
{

    #[Route('', name: 'app_admin_themes')]
    public function index(ThemeRepository $themeRepository): Response
    {
        // Récupérer tous les thèmes
        $themes = $themeRepository->findAll();

        return $this->render('admin/themes/index.html.twig', [
            'themes' => $themes,
        ]);
    }

    #[Route('/new', name: 'app_admin_themes_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $theme = new Theme();
        $form = $this->createForm(ThemeType::class, $theme);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($theme);
            $em->flush();

            $this->addFlash('success', 'Le thème "' . $theme->getName() . '" a été créé avec succès !');

            return $this->redirectToRoute('app_admin_themes');
        }

        return $this->render('admin/themes/form.html.twig', [
            'form' => $form,
            'theme' => $theme,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_themes_edit')]
    public function edit(Theme $theme, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ThemeType::class, $theme);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Le thème "' . $theme->getName() . '" a été modifié avec succès !');

            return $this->redirectToRoute('app_admin_themes');
        }

        return $this->render('admin/themes/form.html.twig', [
            'form' => $form,
            'theme' => $theme,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_themes_delete', methods: ['POST'])]
    public function delete(Theme $theme, EntityManagerInterface $em): Response
    {
        $themeName = $theme->getName();
        $em->remove($theme);
        $em->flush();

        $this->addFlash('success', 'Le thème "' . $themeName . '" a été supprimé avec succès !');

        return $this->redirectToRoute('app_admin_themes');
    }
}