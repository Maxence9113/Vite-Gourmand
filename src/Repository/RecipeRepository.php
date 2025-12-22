<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * Récupère les recettes par nom de catégorie
     * @param string $categoryName
     * @return Recipe[]
     */
    public function findByCategoryName(string $categoryName): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->andWhere('c.name = :categoryName')
            ->setParameter('categoryName', $categoryName)
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Récupère toutes les recettes groupées par catégorie en une seule requête
     * Optimisé pour éviter le problème N+1
     * @return array<string, Recipe[]>
     */
    public function findAllGroupedByCategory(): array
    {
        $recipes = $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->addSelect('c')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($recipes as $recipe) {
            $categoryName = $recipe->getCategory()?->getName() ?? 'Sans catégorie';
            if (!isset($grouped[$categoryName])) {
                $grouped[$categoryName] = [];
            }
            $grouped[$categoryName][] = $recipe;
        }

        return $grouped;
    }
}
