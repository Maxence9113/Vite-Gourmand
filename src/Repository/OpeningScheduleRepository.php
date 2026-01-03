<?php

namespace App\Repository;

use App\Entity\OpeningSchedule;
use App\Enum\DayOfWeek;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpeningSchedule>
 */
class OpeningScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpeningSchedule::class);
    }

    public function findByDayOfWeek(DayOfWeek $dayOfWeek): ?OpeningSchedule
    {
        return $this->createQueryBuilder('os')
            ->andWhere('os.dayOfWeek = :day')
            ->setParameter('day', $dayOfWeek)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('os')
            ->orderBy('os.dayOfWeek', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOpenDays(): array
    {
        return $this->createQueryBuilder('os')
            ->andWhere('os.isOpen = :isOpen')
            ->setParameter('isOpen', true)
            ->orderBy('os.dayOfWeek', 'ASC')
            ->getQuery()
            ->getResult();
    }
}