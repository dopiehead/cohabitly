<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: 'profiles')]
class Profile
{
    public const GENDER_MALE            = 'male';
    public const GENDER_FEMALE          = 'female';
    public const GENDER_PREFER_NOT      = 'prefer_not_to_say';

    public const EMPLOYMENT_EMPLOYED    = 'employed';
    public const EMPLOYMENT_SELF        = 'self_employed';
    public const EMPLOYMENT_STUDENT     = 'student';
    public const EMPLOYMENT_UNEMPLOYED  = 'unemployed';

    public const INCOME_BELOW_100K      = 'below_100k';
    public const INCOME_100K_300K       = '100k_300k';
    public const INCOME_300K_500K       = '300k_500k';
    public const INCOME_ABOVE_500K      = 'above_500k';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $occupation = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $employmentStatus = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $monthlyIncomeRange = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isComplete = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function computeCompleteness(): void
    {
        $this->isComplete = !empty($this->fullName)
            && !empty($this->phoneNumber)
            && !empty($this->occupation)
            && !empty($this->employmentStatus)
            && !empty($this->monthlyIncomeRange)
            && !empty($this->photoUrl);
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(?string $fullName): static { $this->fullName = $fullName; return $this; }

    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(?string $phoneNumber): static { $this->phoneNumber = $phoneNumber; return $this; }

    public function getDateOfBirth(): ?\DateTimeInterface { return $this->dateOfBirth; }
    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): static { $this->dateOfBirth = $dateOfBirth; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): static { $this->gender = $gender; return $this; }

    public function getOccupation(): ?string { return $this->occupation; }
    public function setOccupation(?string $occupation): static { $this->occupation = $occupation; return $this; }

    public function getEmploymentStatus(): ?string { return $this->employmentStatus; }
    public function setEmploymentStatus(?string $employmentStatus): static { $this->employmentStatus = $employmentStatus; return $this; }

    public function getMonthlyIncomeRange(): ?string { return $this->monthlyIncomeRange; }
    public function setMonthlyIncomeRange(?string $monthlyIncomeRange): static { $this->monthlyIncomeRange = $monthlyIncomeRange; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): static { $this->bio = $bio; return $this; }

    public function getPhotoUrl(): ?string { return $this->photoUrl; }
    public function setPhotoUrl(?string $photoUrl): static { $this->photoUrl = $photoUrl; return $this; }

    public function isComplete(): bool { return $this->isComplete; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
