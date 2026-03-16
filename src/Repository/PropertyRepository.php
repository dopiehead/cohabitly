<?php

namespace App\Repository;

use App\Entity\PropertyList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyList>
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyList::class);
    }

    // ============================
    // SAVE PROPERTY
    // ============================
    public function save(PropertyList $property, bool $flush = true): void
    {
        $this->getEntityManager()->persist($property);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // ============================
    // LATEST PROPERTIES
    // ============================
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // ============================
    // FIND PROPERTIES BY OWNER
    // ============================
    public function findByOwner(User $user, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    // ============================
    // SEARCH + FILTER + PAGINATION
    // ============================
    public function searchProperties(?array $filters, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p');

        // SEARCH BY TITLE OR DESCRIPTION OR LOCATION
        if (!empty($filters['q'])) {
            $qb->andWhere(
                'p.title LIKE :q OR p.description LIKE :q OR p.location LIKE :q'
            )
            ->setParameter('q', '%' . $filters['q'] . '%');
        }

        // FILTER BY LGA
        if (!empty($filters['lga'])) {
            $qb->andWhere('p.lga = :lga')
               ->setParameter('lga', $filters['lga']);
        }

        // FILTER BY PRICE RANGE
        if (!empty($filters['priceFrom'])) {
            $qb->andWhere('p.price >= :priceFrom')
               ->setParameter('priceFrom', $filters['priceFrom']);
        }
        if (!empty($filters['priceTo'])) {
            $qb->andWhere('p.price <= :priceTo')
               ->setParameter('priceTo', $filters['priceTo']);
        }

        // FILTER FEATURED
        if (!empty($filters['featured'])) {
            $qb->andWhere('p.featured = :featured')
               ->setParameter('featured', (bool)$filters['featured']);
        }

        // ============================
        // ORDERING
        // ============================
        $orderByMap = [
            'created_at_DESC' => ['p.createdAt', 'DESC'],
            'created_at_ASC'  => ['p.createdAt', 'ASC'],
            'price_ASC'       => ['p.price', 'ASC'],
            'price_DESC'      => ['p.price', 'DESC'],
            'title_ASC'       => ['p.title', 'ASC'],
            'title_DESC'      => ['p.title', 'DESC'],
        ];

        [$field, $dir] = $orderByMap[$filters['orderBy'] ?? 'created_at_DESC'];
        $qb->orderBy($field, $dir);

        // ============================
        // PAGINATION
        // ============================
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    // ============================
    // GET DISTINCT LOCATIONS / LGAs
    // ============================
    public function getAllLgas(): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('DISTINCT p.lga')
            ->getQuery()
            ->getScalarResult();

        return array_map(fn($r) => $r['lga'], $results);
    }
}