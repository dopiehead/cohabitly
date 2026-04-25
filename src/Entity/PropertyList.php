<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PropertyListRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyListRepository::class)]
#[ORM\Table(name: "property_lists")]
class PropertyList
{
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_CLOSED   = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank]
    private string $location;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: "json", nullable: true)]
    private array $property_images = [];
    
    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $lga = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    #[Assert\NotBlank]
    private float $price;

    #[ORM\Column(type: "integer")]
    private int $rooms = 1;

    #[ORM\Column(type: "integer")]
    private int $bathrooms = 1;

    #[ORM\Column(type: "integer")]
    private int $toilets = 1;

    #[ORM\Column(type: "boolean")]
    private bool $parkingSpace = false;

    #[ORM\Column(type: "boolean")]
    private bool $featured = false;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $availableFrom = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: "App\Entity\User")]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // -----------------------
    // Getters and Setters
    // -----------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setPropertyImages(array $images): self
    {
        $this->property_images = $images;
        return $this;
    }
    
    public function getPropertyImages(): array
    {
        return $this->property_images;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getLga(): ?string
    {
        return $this->lga;
    }

    public function setLga(?string $lga): self
    {
        $this->lga = $lga;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getRooms(): int
    {
        return $this->rooms;
    }

    public function setRooms(int $rooms): self
    {
        $this->rooms = $rooms;
        return $this;
    }

    public function getBathrooms(): int
    {
        return $this->bathrooms;
    }

    public function setBathrooms(int $bathrooms): self
    {
        $this->bathrooms = $bathrooms;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): self
    {
        $this->featured = $featured;
        return $this;
    }

    public function getState(): ?string { return $this->state; }
    public function setState(?string $state): self { $this->state = $state; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): self { $this->type = $type; return $this; }

    public function getToilets(): int { return $this->toilets; }
    public function setToilets(int $toilets): self { $this->toilets = $toilets; return $this; }

    public function hasParkingSpace(): bool { return $this->parkingSpace; }
    public function setParkingSpace(bool $parkingSpace): self { $this->parkingSpace = $parkingSpace; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getAvailableFrom(): ?\DateTimeInterface { return $this->availableFrom; }
    public function setAvailableFrom(?\DateTimeInterface $availableFrom): self { $this->availableFrom = $availableFrom; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function touch(): void { $this->updatedAt = new \DateTime(); }

    public function getOwner(): User { return $this->owner; }
    public function setOwner(User $owner): self { $this->owner = $owner; return $this; }
}