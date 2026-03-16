<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Fetch inbox conversations (latest message per sender)
     */
    public function getInbox(string $receiverEmail, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        // Total conversations
        $total = (int) $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.senderEmail)')
            ->where('m.receiverEmail = :email')
            ->andWhere('m.isReceiverDeleted = false')
            ->setParameter('email', $receiverEmail)
            ->getQuery()
            ->getSingleScalarResult();

        // Unread conversations
        $unread = (int) $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.senderEmail)')
            ->where('m.receiverEmail = :email')
            ->andWhere('m.hasRead = false')
            ->andWhere('m.isReceiverDeleted = false')
            ->setParameter('email', $receiverEmail)
            ->getQuery()
            ->getSingleScalarResult();

        // Latest message per sender (correlated subquery)
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.receiverEmail = :email')
           ->andWhere('m.isReceiverDeleted = false')
           ->andWhere(
               'm.createdAt = (
                   SELECT MAX(m2.createdAt)
                   FROM App\Entity\Message m2
                   WHERE m2.senderEmail = m.senderEmail
                     AND m2.receiverEmail = :email
                     AND m2.isReceiverDeleted = false
               )'
           )
           ->setParameter('email', $receiverEmail)
           ->orderBy('m.hasRead', 'ASC')
           ->addOrderBy('m.createdAt', 'DESC')
           ->setFirstResult($offset)
           ->setMaxResults($limit);

        $messages = $qb->getQuery()->getResult();

        return [
            'messages'     => $messages,
            'unread_count' => $unread,
            'total_pages'  => (int) ceil($total / $limit),
            'page'         => $page,
        ];
    }

    /**
     * Count unread messages from a sender
     */
    public function countUnreadFromSender(string $receiverEmail, string $senderEmail): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.receiverEmail = :receiver')
            ->andWhere('m.senderEmail = :sender')
            ->andWhere('m.hasRead = false')
            ->andWhere('m.isReceiverDeleted = false')
            ->setParameter('receiver', $receiverEmail)
            ->setParameter('sender', $senderEmail)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get conversation between two users
     */
    public function getConversation(string $userEmail, string $otherEmail): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.senderEmail = :user AND m.receiverEmail = :other) OR (m.senderEmail = :other AND m.receiverEmail = :user)')
            ->andWhere('(m.senderEmail = :user AND m.isSenderDeleted = false) OR (m.senderEmail = :other AND m.isReceiverDeleted = false)')
            ->setParameter('user', $userEmail)
            ->setParameter('other', $otherEmail)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(string $userEmail, string $otherEmail): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.hasRead', ':read')
            ->where('m.receiverEmail = :user')
            ->andWhere('m.senderEmail = :other')
            ->andWhere('m.hasRead = false')
            ->setParameter('read', true)
            ->setParameter('user', $userEmail)
            ->setParameter('other', $otherEmail)
            ->getQuery()
            ->execute();
    }


    public function saveMessage(
        string $senderEmail,
        string $receiverEmail,
        string $subject,
        string $content
    ): Message {
        $message = new Message();
        $message->setSenderEmail($senderEmail)
                ->setReceiverEmail($receiverEmail)
                ->setSubject($subject)
                ->setContent($content);
    
        // Defaults already set in entity: hasRead = false, isSenderDeleted = false, isReceiverDeleted = false
        $em = $this->getEntityManager();
        $em->persist($message);
        $em->flush();
    
        return $message;
    }


    
}
