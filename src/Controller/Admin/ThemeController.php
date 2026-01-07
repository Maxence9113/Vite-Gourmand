<?php

namespace App\Controller\Admin;

use App\Entity\Theme;
use App\Form\ThemeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des thèmes
 */
#[Route('/admin/themes')]
final class ThemeController extends AbstractCrudController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    protected function getEntityClass(): string
    {
        return Theme::class;
    }

    protected function getFormTypeClass(): string
    {
        return ThemeType::class;
    }

    protected function getRoutePrefix(): string
    {
        return 'app_admin_themes';
    }

    protected function getIndexTemplate(): string
    {
        return 'admin/themes/index.html.twig';
    }

    protected function getFormTemplate(): string
    {
        return 'admin/themes/form.html.twig';
    }

    protected function getEntityTemplateVariable(): string
    {
        return 'theme';
    }

    protected function getEntityListTemplateVariable(): string
    {
        return 'themes';
    }

    protected function getEntityDisplayName(): string
    {
        return 'Le thème';
    }

    // Theme n'a pas de méthode optimisée, utilise findAll() par défaut
    // Theme n'a pas de vérification de relations, peut être supprimé directement

    #[Route('', name: 'app_admin_themes')]
    public function index(): Response
    {
        return $this->indexAction();
    }

    #[Route('/new', name: 'app_admin_themes_new')]
    public function new(Request $request): Response
    {
        return $this->newAction($request);
    }

    #[Route('/{id}/edit', name: 'app_admin_themes_edit')]
    public function edit(Theme $theme, Request $request): Response
    {
        return $this->editAction($theme, $request);
    }

    #[Route('/{id}/delete', name: 'app_admin_themes_delete', methods: ['POST'])]
    public function delete(Theme $theme): Response
    {
        return $this->deleteAction($theme);
    }
}