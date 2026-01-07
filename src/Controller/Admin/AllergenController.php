<?php

namespace App\Controller\Admin;

use App\Entity\Allergen;
use App\Form\AllergenType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des allergènes
 */
#[Route('/admin/allergens')]
final class AllergenController extends AbstractCrudController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    protected function getEntityClass(): string
    {
        return Allergen::class;
    }

    protected function getFormTypeClass(): string
    {
        return AllergenType::class;
    }

    protected function getRoutePrefix(): string
    {
        return 'app_admin_allergens';
    }

    protected function getIndexTemplate(): string
    {
        return 'admin/allergens/index.html.twig';
    }

    protected function getFormTemplate(): string
    {
        return 'admin/allergens/form.html.twig';
    }

    protected function getEntityTemplateVariable(): string
    {
        return 'allergen';
    }

    protected function getEntityListTemplateVariable(): string
    {
        return 'allergens';
    }

    protected function getEntityDisplayName(): string
    {
        return "L'allergène";
    }

    protected function getRepositoryFindAllMethod(): string
    {
        return 'findAllWithRecipeCount';
    }

    protected function canDelete(object $entity): ?string
    {
        /** @var Allergen $entity */
        if ($entity->getRecipes()->count() > 0) {
            return 'Impossible de supprimer l\'allergène "' . $entity->getName() . '" car il est utilisé par ' . $entity->getRecipes()->count() . ' recette(s).';
        }
        return null;
    }

    #[Route('', name: 'app_admin_allergens')]
    public function index(): Response
    {
        return $this->indexAction();
    }

    #[Route('/new', name: 'app_admin_allergens_new')]
    public function new(Request $request): Response
    {
        return $this->newAction($request);
    }

    #[Route('/{id}/edit', name: 'app_admin_allergens_edit')]
    public function edit(Allergen $allergen, Request $request): Response
    {
        return $this->editAction($allergen, $request);
    }

    #[Route('/{id}/delete', name: 'app_admin_allergens_delete', methods: ['POST'])]
    public function delete(Allergen $allergen): Response
    {
        return $this->deleteAction($allergen);
    }
}