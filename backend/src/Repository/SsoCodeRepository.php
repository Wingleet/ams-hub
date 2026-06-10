<?php

namespace App\Repository;

use App\Entity\SsoCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SsoCode>
 */
class SsoCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SsoCode::class);
    }

    // Find a valid SSO code
    public function findValidCode(string $code): ?SsoCode
    {
        return $this->createQueryBuilder('s')
            ->where('s.code = :code')
            ->andWhere('s.usedAt IS NULL')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('code', $code)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Delete an SSO coe by its code string
    public function deleteUsedCode(string $code): void
    {
        $this->createQueryBuilder('s')
            ->delete()
            ->where('s.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->execute();
    }

    // Double check
    public function deleteExpiredCodes(): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresAt <= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
