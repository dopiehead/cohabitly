<?php

namespace App\Repository;

use App\Entity\Features;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeaturesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Features::class);
    }

    /**
     * Save Features
     */
    public function save(Features $features, bool $flush = true): void
    {
        $this->_em->persist($features);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remove Features
     */
    public function remove(Features $features, bool $flush = true): void
    {
        $this->_em->remove($features);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Find Features by Property ID
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.property', 'p')
            ->andWhere('p.id = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find One Feature by Property
     */
    public function findOneByProperty(int $propertyId): ?Features
    {
        return $this->createQueryBuilder('f')
            ->join('f.property', 'p')
            ->andWhere('p.id = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}