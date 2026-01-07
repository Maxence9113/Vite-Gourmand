<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des catégories
 */
#[Route('/admin/categories')]
final class CategoryController extends AbstractCrudController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    protected function getEntityClass(): string
    {
        return Category::class;
    }

    protected function getFormTypeClass(): string
    {
        return CategoryType::class;
    }

    protected function getRoutePrefix(): string
    {
        return 'app_admin_categories';
    }

    protected function getIndexTemplate(): string
    {
        return 'admin/categories/index.html.twig';
    }

    protected function getFormTemplate(): string
    {
        return 'admin/categories/form.html.twig';
    }

    protected function getEntityTemplateVariable(): string
    {
        return 'category';
    }

    protected function getEntityListTemplateVariable(): string
    {
        return 'categories';
    }

    protected function getEntityDisplayName(): string
    {
        return 'La catégorie';
    }

    protected function getRepositoryFindAllMethod(): string
    {
        return 'findAllWithRecipeCount';
    }

    protected function canDelete(object $entity): ?string
    {
        /** @var Category $entity */
        if ($entity->getRecipes()->count() > 0) {
            return 'Impossible de supprimer la catégorie "' . $entity->getName() . '" car elle est utilisée par ' . $entity->getRecipes()->count() . ' recette(s).';
        }
        return null;
    }

    #[Route('', name: 'app_admin_categories')]
    public function index(): Response
    {
        return $this->indexAction();
    }

    #[Route('/new', name: 'app_admin_categories_new')]
    public function new(Request $request): Response
    {
        return $this->newAction($request);
    }

    #[Route('/{id}/edit', name: 'app_admin_categories_edit')]
    public function edit(Category $category, Request $request): Response
    {
        return $this->editAction($category, $request);
    }

    #[Route('/{id}/delete', name: 'app_admin_categories_delete', methods: ['POST'])]
    public function delete(Category $category): Response
    {
        return $this->deleteAction($category);
    }
}