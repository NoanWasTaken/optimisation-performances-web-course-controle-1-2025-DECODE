<?php

namespace App\Repository;

use App\Entity\Galaxy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Galaxy>
 */
class GalaxyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Galaxy::class);
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.modele', 'm')
            ->addSelect('m')
            ->leftJoin('m.files', 'f')
            ->addSelect('f')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Galaxy[] Returns an array of Galaxy objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Galaxy
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
