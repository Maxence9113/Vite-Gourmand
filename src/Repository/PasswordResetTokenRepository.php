<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Trouve un token valide par sa valeur
     */
    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->where('prt.token = :token')
            ->andWhere('prt.isUsed = :isUsed')
            ->andWhere('prt.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('isUsed', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime tous les tokens expirés ou utilisés pour un utilisateur
     */
    public function deleteExpiredOrUsedTokensForUser(User $user): void
    {
        $this->createQueryBuilder('prt')
            ->delete()
            ->where('prt.user = :user')
            ->andWhere('prt.isUsed = :isUsed OR prt.expiresAt < :now')
            ->setParameter('user', $user)
            ->setParameter('isUsed', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime tous les tokens existants pour un utilisateur
     */
    public function deleteAllTokensForUser(User $user): void
    {
        $this->createQueryBuilder('prt')
            ->delete()
            ->where('prt.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}