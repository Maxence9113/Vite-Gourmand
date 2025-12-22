<?php

namespace App\Repository;

use App\Entity\Allergen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Allergen>
 */
class AllergenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Allergen::class);
    }

    /**
     * Récupère tous les allergènes avec le nombre de recettes (optimisé N+1)
     * @return Allergen[]
     */
    public function findAllWithRecipeCount(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.recipes', 'r')
            ->addSelect('r')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
