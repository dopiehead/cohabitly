<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepository extends ServiceEntityRepository
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(ManagerRegistry $registry, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($registry, User::class);
        $this->passwordHasher = $passwordHasher;
    }

    // ========================
    // Update Helpers
    // ========================

    public function updateProfileImage(User $user, string $imageUrl): void
    {
        $user->setUserImage($imageUrl);
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
    }

    // ========================
    // Persist User (already hashed password)
    // ========================

    public function createUser(array $data): User
    {
        $user = new User();
        
        $user->setUserName($data['user_name']);
        $user->setUserEmail($data['user_email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['user_password']));
        $user->setVerified(false);
        $user->setVkey($data['vkey'] ?? bin2hex(random_bytes(32)));
        $user->setPublicId($data['public_id'] ?? bin2hex(random_bytes(16)));

        // Optional fields
        $user->setUserPhone($data['user_phone'] ?? null);
        $user->setUserLocation($data['user_location'] ?? null);
        $user->setLga($data['lga'] ?? null);
        $user->setUserAddress($data['user_address'] ?? null);
        $user->setUserGender($data['user_gender'] ?? null);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findOneBy(['user_email' => $email]) !== null;
    }

    // ========================
    // Basic Queries (unchanged)
    // ========================
    public function findAllUsers(): array
    {
        return $this->createQueryBuilder('u')->getQuery()->getResult();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.user_email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findById(int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findVendors(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.user_type = :type')
            ->setParameter('type', 'vendor')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}