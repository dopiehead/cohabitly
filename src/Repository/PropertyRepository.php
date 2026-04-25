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
    // ACTIVE LISTINGS (paginated)
    // ============================
    public function findActive(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', PropertyList::STATUS_ACTIVE)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :status')
            ->setParameter('status', PropertyList::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // ============================
    // OWNER LISTINGS (paginated)
    // ============================
    public function findByOwnerPaginated(User $user, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('p')
            ->andWhere('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByOwner(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // ============================
    // SEARCH LISTINGS
    // ============================
    public function searchListings(?array $filters, int $page = 1, int $limit = 10): array
    {
        $qb = $this->buildSearchQuery($filters);
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit)->orderBy('p.createdAt', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function countSearch(?array $filters): int
    {
        $qb = $this->buildSearchQuery($filters);
        $qb->select('COUNT(p.id)');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function buildSearchQuery(?array $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :active')
            ->setParameter('active', PropertyList::STATUS_ACTIVE);

        if (!empty($filters['q'])) {
            $qb->andWhere('p.title LIKE :q OR p.description LIKE :q OR p.location LIKE :q')
               ->setParameter('q', '%' . $filters['q'] . '%');
        }
        if (!empty($filters['state'])) {
            $qb->andWhere('p.state = :state')->setParameter('state', $filters['state']);
        }
        if (!empty($filters['lga'])) {
            $qb->andWhere('p.lga = :lga')->setParameter('lga', $filters['lga']);
        }
        if (!empty($filters['type'])) {
            $qb->andWhere('p.type = :type')->setParameter('type', $filters['type']);
        }
        if (!empty($filters['min_price'])) {
            $qb->andWhere('p.price >= :min')->setParameter('min', (float) $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $qb->andWhere('p.price <= :max')->setParameter('max', (float) $filters['max_price']);
        }
        if (!empty($filters['rooms'])) {
            $qb->andWhere('p.rooms = :rooms')->setParameter('rooms', (int) $filters['rooms']);
        }
        if (!empty($filters['toilets'])) {
            $qb->andWhere('p.toilets = :toilets')->setParameter('toilets', (int) $filters['toilets']);
        }
        if (isset($filters['parking_space']) && $filters['parking_space'] !== '') {
            $qb->andWhere('p.parkingSpace = :ps')->setParameter('ps', filter_var($filters['parking_space'], FILTER_VALIDATE_BOOLEAN));
        }

        return $qb;
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
    // FIND PROPERTIES BY OWNER (legacy)
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
    // SEARCH + FILTER (legacy)
    // ============================
    public function searchProperties(?array $filters, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p');

        if (!empty($filters['q'])) {
            $qb->andWhere('p.title LIKE :q OR p.description LIKE :q OR p.location LIKE :q')
               ->setParameter('q', '%' . $filters['q'] . '%');
        }
        if (!empty($filters['lga'])) {
            $qb->andWhere('p.lga = :lga')->setParameter('lga', $filters['lga']);
        }
        if (!empty($filters['priceFrom'])) {
            $qb->andWhere('p.price >= :priceFrom')->setParameter('priceFrom', $filters['priceFrom']);
        }
        if (!empty($filters['priceTo'])) {
            $qb->andWhere('p.price <= :priceTo')->setParameter('priceTo', $filters['priceTo']);
        }
        if (!empty($filters['featured'])) {
            $qb->andWhere('p.featured = :featured')->setParameter('featured', (bool) $filters['featured']);
        }

        $orderByMap = [
            'created_at_DESC' => ['p.createdAt', 'DESC'],
            'created_at_ASC'  => ['p.createdAt', 'ASC'],
            'price_ASC'       => ['p.price', 'ASC'],
            'price_DESC'      => ['p.price', 'DESC'],
        ];
        [$field, $dir] = $orderByMap[$filters['orderBy'] ?? 'created_at_DESC'];
        $qb->orderBy($field, $dir);

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getAllLgas(): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('DISTINCT p.lga')
            ->getQuery()
            ->getScalarResult();

        return array_map(fn($r) => $r['lga'], $results);
    }
}
