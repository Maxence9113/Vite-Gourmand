<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /**
     * Récupère tous les menus avec leurs relations (optimisé N+1)
     * @return Menu[]
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->addSelect('t')
            ->leftJoin('m.dietetary', 'd')
            ->addSelect('d')
            ->leftJoin('m.recipes', 'r')
            ->addSelect('r')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
