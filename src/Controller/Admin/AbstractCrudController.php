<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur abstrait pour les opérations CRUD standards dans l'admin
 * Factorise le code commun entre les différents contrôleurs admin
 */
abstract class AbstractCrudController extends AbstractController
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Retourne la classe de l'entité gérée par ce contrôleur
     * Ex: Allergen::class, Category::class, etc.
     */
    abstract protected function getEntityClass(): string;

    /**
     * Retourne la classe du FormType associé
     * Ex: AllergenType::class, CategoryType::class, etc.
     */
    abstract protected function getFormTypeClass(): string;

    /**
     * Retourne le préfixe des routes pour ce contrôleur
     * Ex: 'app_admin_allergens', 'app_admin_categories', etc.
     */
    abstract protected function getRoutePrefix(): string;

    /**
     * Retourne le chemin du template pour la vue index
     * Ex: 'admin/allergens/index.html.twig'
     */
    abstract protected function getIndexTemplate(): string;

    /**
     * Retourne le chemin du template pour les formulaires (new/edit)
     * Ex: 'admin/allergens/form.html.twig'
     */
    abstract protected function getFormTemplate(): string;

    /**
     * Retourne le nom de la variable utilisée dans le template
     * Ex: 'allergen', 'category', etc.
     */
    abstract protected function getEntityTemplateVariable(): string;

    /**
     * Retourne le nom de la variable plurielle pour la liste
     * Ex: 'allergens', 'categories', etc.
     */
    abstract protected function getEntityListTemplateVariable(): string;

    /**
     * Retourne le nom affiché de l'entité (genre masculin ou féminin)
     * Ex: "L'allergène", "La catégorie", "Le régime alimentaire", "Le thème"
     */
    abstract protected function getEntityDisplayName(): string;

    /**
     * Retourne le participe passé "créé" accordé selon le genre de l'entité
     * Ex: "créé" pour masculin, "créée" pour féminin
     * Peut être surchargé si nécessaire
     */
    protected function getCreatedVerb(): string
    {
        return 'créé';
    }

    /**
     * Retourne le participe passé "modifié" accordé selon le genre de l'entité
     * Ex: "modifié" pour masculin, "modifiée" pour féminin
     * Peut être surchargé si nécessaire
     */
    protected function getModifiedVerb(): string
    {
        return 'modifié';
    }

    /**
     * Retourne le participe passé "supprimé" accordé selon le genre de l'entité
     * Ex: "supprimé" pour masculin, "supprimée" pour féminin
     * Peut être surchargé si nécessaire
     */
    protected function getDeletedVerb(): string
    {
        return 'supprimé';
    }

    /**
     * Retourne la méthode du repository pour récupérer toutes les entités
     * Par défaut utilise findAll(), peut être surchargé pour utiliser des méthodes optimisées
     * Ex: 'findAllWithRecipeCount', 'findAllWithMenuCount'
     */
    protected function getRepositoryFindAllMethod(): string
    {
        return 'findAll';
    }

    /**
     * Vérifie si l'entité peut être supprimée
     * Retourne null si ok, ou un message d'erreur si la suppression n'est pas possible
     */
    protected function canDelete(object $entity): ?string
    {
        // Par défaut, pas de vérification spécifique
        return null;
    }

    /**
     * Retourne le nom de l'entité pour l'affichage
     * Par défaut utilise getName(), peut être surchargé
     */
    protected function getEntityName(object $entity): string
    {
        if (method_exists($entity, 'getName')) {
            return $entity->getName();
        }
        return (string) $entity;
    }

    /**
     * Action index : liste toutes les entités
     */
    protected function indexAction(): Response
    {
        $repository = $this->entityManager->getRepository($this->getEntityClass());
        $method = $this->getRepositoryFindAllMethod();
        $entities = $repository->$method();

        return $this->render($this->getIndexTemplate(), [
            $this->getEntityListTemplateVariable() => $entities,
        ]);
    }

    /**
     * Action new : crée une nouvelle entité
     */
    protected function newAction(Request $request): Response
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass();

        $form = $this->createForm($this->getFormTypeClass(), $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->getEntityDisplayName() . ' "' . $this->getEntityName($entity) . '" a été ' . $this->getCreatedVerb() . ' avec succès !'
            );

            return $this->redirectToRoute($this->getRoutePrefix());
        }

        return $this->render($this->getFormTemplate(), [
            'form' => $form,
            $this->getEntityTemplateVariable() => $entity,
            'isEdit' => false,
        ]);
    }

    /**
     * Action edit : modifie une entité existante
     */
    protected function editAction(object $entity, Request $request): Response
    {
        $form = $this->createForm($this->getFormTypeClass(), $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->getEntityDisplayName() . ' "' . $this->getEntityName($entity) . '" a été ' . $this->getModifiedVerb() . ' avec succès !'
            );

            return $this->redirectToRoute($this->getRoutePrefix());
        }

        return $this->render($this->getFormTemplate(), [
            'form' => $form,
            $this->getEntityTemplateVariable() => $entity,
            'isEdit' => true,
        ]);
    }

    /**
     * Action delete : supprime une entité
     */
    protected function deleteAction(object $entity): Response
    {
        $entityName = $this->getEntityName($entity);

        // Vérifier si l'entité peut être supprimée
        $errorMessage = $this->canDelete($entity);
        if ($errorMessage !== null) {
            $this->addFlash('error', $errorMessage);
            return $this->redirectToRoute($this->getRoutePrefix());
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            $this->getEntityDisplayName() . ' "' . $entityName . '" a été ' . $this->getDeletedVerb() . ' avec succès !'
        );

        return $this->redirectToRoute($this->getRoutePrefix());
    }
}