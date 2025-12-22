<?php

namespace App\Controller\Admin;

use App\Entity\Theme;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use App\Service\FileUploader;
use App\Service\ThemeFileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/themes')]
final class ThemeController extends AbstractController
{
    public function __construct(
        private ThemeFileUploader $fileUploader
    ) {
    }

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
            // Récupérer le fichier uploadé
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // 1. Uploader le fichier
                $fileName = $this->fileUploader->upload($imageFile);

                // 2. Stocker l'URL dans l'entité
                $theme->setIllustration('/uploads/theme_illustrations/' . $fileName);
            }

            // Si pas de texte alternatif, utiliser le nom du thème
            if (!$theme->getTextAlt()) {
                $theme->setTextAlt($theme->getName());
            }

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
        $form = $this->createForm(ThemeType::class, $theme, [
            'is_edit' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le nouveau fichier (peut être null)
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // Supprimer l'ancien fichier si il existe
                $oldIllustration = $theme->getIllustration();
                if ($oldIllustration) {
                    // Extraire le nom du fichier de l'URL
                    $oldFileName = basename($oldIllustration);
                    $this->fileUploader->remove($oldFileName);
                }

                // Uploader le nouveau fichier
                $fileName = $this->fileUploader->upload($imageFile);
                $theme->setIllustration('/uploads/theme_illustrations/' . $fileName);
            }

            // Si pas de texte alternatif, utiliser le nom du thème
            if (!$theme->getTextAlt()) {
                $theme->setTextAlt($theme->getName());
            }

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
        // Supprimer le fichier image associé
        $illustration = $theme->getIllustration();
        if ($illustration) {
            $fileName = basename($illustration);
            $this->fileUploader->remove($fileName);
        }

        $themeName = $theme->getName();
        $em->remove($theme);
        $em->flush();

        $this->addFlash('success', 'Le thème "' . $themeName . '" a été supprimé avec succès !');

        return $this->redirectToRoute('app_admin_themes');
    }
}