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

    /**
     * Filtre les menus selon plusieurs critères
     * @return Menu[]
     */
    public function findByFilters(
        ?string $themeId = null,
        array $dietetaryIds = [],
        array $allergenIds = [],
        ?float $priceMin = null,
        ?float $priceMax = null,
        ?int $nbPersonMin = null
    ): array {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->addSelect('t')
            ->leftJoin('m.dietetary', 'd')
            ->addSelect('d')
            ->leftJoin('m.recipes', 'r')
            ->addSelect('r')
            ->leftJoin('r.allergen', 'a')
            ->addSelect('a');

        if ($themeId) {
            $qb->andWhere('t.id = :themeId')
                ->setParameter('themeId', $themeId);
        }

        if (!empty($dietetaryIds)) {
            $qb->andWhere('d.id IN (:dietetaryIds)')
                ->setParameter('dietetaryIds', $dietetaryIds);
        }

        if (!empty($allergenIds)) {
            $subQuery = $this->createQueryBuilder('m2')
                ->select('m2.id')
                ->leftJoin('m2.recipes', 'r2')
                ->leftJoin('r2.allergen', 'a2')
                ->where('a2.id IN (:allergenIds)')
                ->getDQL();

            $qb->andWhere($qb->expr()->notIn('m.id', $subQuery))
                ->setParameter('allergenIds', $allergenIds);
        }

        if ($priceMin) {
            $qb->andWhere('m.price_per_person >= :priceMin')
                ->setParameter('priceMin', $priceMin);
        }

        if ($priceMax) {
            $qb->andWhere('m.price_per_person <= :priceMax')
                ->setParameter('priceMax', $priceMax);
        }

        if ($nbPersonMin) {
            $qb->andWhere('m.nb_person_min <= :nbPersonMin')
                ->setParameter('nbPersonMin', $nbPersonMin);
        }

        return $qb->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un menu avec toutes ses relations chargées (EAGER loading)
     * pour éviter le problème N+1
     */
    public function findOneWithRelations(int $id): ?Menu
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')->addSelect('t')
            ->leftJoin('m.dietetary', 'd')->addSelect('d')
            ->leftJoin('m.recipes', 'r')->addSelect('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->leftJoin('r.allergen', 'a')->addSelect('a')
            ->leftJoin('r.recipeIllustrations', 'ri')->addSelect('ri')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
