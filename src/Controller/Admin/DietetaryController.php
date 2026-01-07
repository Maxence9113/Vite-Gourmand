<?php

namespace App\Controller\Admin;

use App\Entity\Dietetary;
use App\Form\DietetaryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des régimes alimentaires
 */
#[Route('/admin/dietetaries')]
final class DietetaryController extends AbstractCrudController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    protected function getEntityClass(): string
    {
        return Dietetary::class;
    }

    protected function getFormTypeClass(): string
    {
        return DietetaryType::class;
    }

    protected function getRoutePrefix(): string
    {
        return 'app_admin_dietetaries';
    }

    protected function getIndexTemplate(): string
    {
        return 'admin/dietetaries/index.html.twig';
    }

    protected function getFormTemplate(): string
    {
        return 'admin/dietetaries/form.html.twig';
    }

    protected function getEntityTemplateVariable(): string
    {
        return 'dietetary';
    }

    protected function getEntityListTemplateVariable(): string
    {
        return 'dietetaries';
    }

    protected function getEntityDisplayName(): string
    {
        return 'Le régime alimentaire';
    }

    protected function getRepositoryFindAllMethod(): string
    {
        return 'findAllWithMenuCount';
    }

    protected function canDelete(object $entity): ?string
    {
        /** @var Dietetary $entity */
        if ($entity->getMenus()->count() > 0) {
            return 'Impossible de supprimer le régime alimentaire "' . $entity->getName() . '" car il est utilisé par ' . $entity->getMenus()->count() . ' menu(s).';
        }
        return null;
    }

    #[Route('', name: 'app_admin_dietetaries')]
    public function index(): Response
    {
        return $this->indexAction();
    }

    #[Route('/new', name: 'app_admin_dietetaries_new')]
    public function new(Request $request): Response
    {
        return $this->newAction($request);
    }

    #[Route('/{id}/edit', name: 'app_admin_dietetaries_edit')]
    public function edit(Dietetary $dietetary, Request $request): Response
    {
        return $this->editAction($dietetary, $request);
    }

    #[Route('/{id}/delete', name: 'app_admin_dietetaries_delete', methods: ['POST'])]
    public function delete(Dietetary $dietetary): Response
    {
        return $this->deleteAction($dietetary);
    }
}