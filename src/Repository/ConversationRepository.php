<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\PropertyList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function save(Conversation $conversation, bool $flush = true): void
    {
        $this->getEntityManager()->persist($conversation);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByParticipant(User $user, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('c')
            ->andWhere('c.owner = :user OR c.applicant = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findExisting(PropertyList $listing, User $owner, User $applicant): ?Conversation
    {
        return $this->findOneBy(['listing' => $listing, 'owner' => $owner, 'applicant' => $applicant]);
    }
}
