<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve les utilisateurs selon les filtres fournis
     *
     * @param string $search Recherche par email, nom ou prénom
     * @param string $role Filtre par rôle
     * @param string $status Filtre par statut (enabled/disabled)
     * @param bool $isEmployee Si l'utilisateur connecté est un employé (et pas admin)
     * @return User[]
     */
    public function findWithFilters(
        string $search = '',
        string $role = '',
        string $status = '',
        bool $isEmployee = false
    ): array {
        $qb = $this->createQueryBuilder('u');

        // Si l'utilisateur est EMPLOYEE (mais pas ADMIN), ne montrer que les utilisateurs ROLE_USER
        if ($isEmployee) {
            $qb->andWhere('u.roles LIKE :roleUser')
               ->andWhere('u.roles NOT LIKE :roleEmployee')
               ->andWhere('u.roles NOT LIKE :roleAdmin')
               ->setParameter('roleUser', '%ROLE_USER%')
               ->setParameter('roleEmployee', '%ROLE_EMPLOYEE%')
               ->setParameter('roleAdmin', '%ROLE_ADMIN%');
        }

        // Filtre de recherche (email, nom ou prénom)
        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.lastname LIKE :search OR u.firstname LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par rôle
        if ($role) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%' . $role . '%');
        }

        // Filtre par statut
        if ($status !== '') {
            $qb->andWhere('u.isEnabled = :status')
               ->setParameter('status', (bool) $status);
        }

        return $qb->getQuery()->getResult();
    }
}
