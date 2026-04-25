<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $message, bool $flush = true): void
    {
        $this->getEntityManager()->persist($message);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByConversation(Conversation $conversation, int $page = 1, int $limit = 30): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conv')
            ->setParameter('conv', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByConversation(Conversation $conversation): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.conversation = :conv')
            ->setParameter('conv', $conversation)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markConversationRead(Conversation $conversation, User $reader): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.read', ':true')
            ->andWhere('m.conversation = :conv')
            ->andWhere('m.sender != :reader')
            ->andWhere('m.read = false')
            ->setParameter('true', true)
            ->setParameter('conv', $conversation)
            ->setParameter('reader', $reader)
            ->getQuery()
            ->execute();
    }
}
