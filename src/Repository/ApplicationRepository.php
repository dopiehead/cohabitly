<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\PropertyList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function save(Application $application, bool $flush = true): void
    {
        $this->getEntityManager()->persist($application);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByApplicant(User $user, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('a')
            ->andWhere('a.applicant = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByListing(PropertyList $listing, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('a')
            ->andWhere('a.listing = :listing')
            ->setParameter('listing', $listing)
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findExisting(PropertyList $listing, User $applicant): ?Application
    {
        return $this->findOneBy(['listing' => $listing, 'applicant' => $applicant]);
    }

    public function countByApplicant(User $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.applicant = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByListing(PropertyList $listing): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.listing = :listing')
            ->setParameter('listing', $listing)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
