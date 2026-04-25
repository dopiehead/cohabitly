<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profile::class);
    }

    public function findByUser(User $user): ?Profile
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function save(Profile $profile, bool $flush = true): void
    {
        $this->getEntityManager()->persist($profile);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
