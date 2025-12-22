<?php

namespace App\Repository;

use App\Entity\Dietetary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dietetary>
 */
class DietetaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dietetary::class);
    }

    /**
     * Récupère tous les régimes alimentaires avec le nombre de menus (optimisé N+1)
     * @return Dietetary[]
     */
    public function findAllWithMenuCount(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.menus', 'm')
            ->addSelect('m')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
