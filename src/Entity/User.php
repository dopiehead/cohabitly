<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $public_id = null;

    #[ORM\Column(length: 255)]
    private ?string $user_name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $user_email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?string $user_location = null;

    #[ORM\Column(nullable: true)]
    private ?string $user_address = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $user_image = null;

    #[ORM\Column(type: 'boolean')]
    private bool $verified = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $vkey = null;



    // ========================
    // 🔥 NEW FIELDS
    // ========================

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $user_dob = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $user_phone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lga = null;

    #[ORM\Column(type: 'float')]
    private float $user_rating = 0;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $user_gender = null;

    #[ORM\Column(type: 'integer')]
    private int $user_likes = 0;

    #[ORM\Column(type: 'integer')]
    private int $user_shares = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $userRole = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    // ========================
    // CONSTRUCTOR
    // ========================

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }

    // ========================
    // SECURITY METHODS
    // ========================

    public function getUserIdentifier(): string
    {
        return $this->user_email;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    // ========================
    // GETTERS & SETTERS
    // ========================

    public function getId(): ?int { return $this->id; }

    public function getPublicId(): ?string { return $this->public_id; }
    public function setPublicId(string $public_id): static { $this->public_id = $public_id; return $this; }

    public function getUserName(): ?string { return $this->user_name; }
    public function setUserName(string $user_name): static { $this->user_name = $user_name; return $this; }

    public function getUserEmail(): ?string { return $this->user_email; }
    public function setUserEmail(string $user_email): static { $this->user_email = $user_email; return $this; }

    public function getUserLocation(): ?string { return $this->user_location; }
    public function setUserLocation(?string $user_location): static { $this->user_location = $user_location; return $this; }

    public function getUserAddress(): ?string { return $this->user_address; }
    public function setUserAddress(?string $user_address): static { $this->user_address = $user_address; return $this; }

    public function getUserImage(): ?string { return $this->user_image; }
    public function setUserImage(?string $user_image): static { $this->user_image = $user_image; return $this; }

    public function isVerified(): bool { return $this->verified; }
    public function setVerified(bool $verified): static { $this->verified = $verified; return $this; }

    public function getVkey(): ?string { return $this->vkey; }
    public function setVkey(?string $vkey): static { $this->vkey = $vkey; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // ========================
    // NEW GETTERS & SETTERS
    // ========================

    public function getUserDob(): ?\DateTimeInterface { return $this->user_dob; }
    public function setUserDob(?\DateTimeInterface $dob): static { $this->user_dob = $dob; return $this; }

    public function getUserPhone(): ?string { return $this->user_phone; }
    public function setUserPhone(?string $phone): static { $this->user_phone = $phone; return $this; }

    public function getLga(): ?string { return $this->lga; }
    public function setLga(?string $lga): static { $this->lga = $lga; return $this; }

    public function getUserRating(): float { return $this->user_rating; }
    public function setUserRating(float $rating): static { $this->user_rating = $rating; return $this; }

    public function getUserGender(): ?string { return $this->user_gender; }
    public function setUserGender(?string $gender): static { $this->user_gender = $gender; return $this; }

    public function getUserLikes(): int { return $this->user_likes; }
    public function setUserLikes(int $likes): static { $this->user_likes = $likes; return $this; }

    public function getRefreshToken(): ?string { return $this->refreshToken; }
    public function setRefreshToken(?string $refreshToken): static { $this->refreshToken = $refreshToken; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeImmutable $dt): static { $this->resetTokenExpiresAt = $dt; return $this; }

    public function getUserRole(): ?string { return $this->userRole; }
    public function setUserRole(?string $userRole): static { $this->userRole = $userRole; return $this; }

    public function getUserShares(): int { return $this->user_shares; }
    public function setUserShares(int $shares): static { $this->user_shares = $shares; return $this; }
}